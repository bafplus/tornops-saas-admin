<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\User;
use App\Models\DataRefreshLog;
use App\Models\ScheduledJob;
use App\Services\TornApiService;
use App\Services\WarService;
use App\Services\DiscordBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $users = User::orderBy('is_admin', 'desc')->orderBy('name')->get();
        $warActive = WarService::hasActiveWar();
        
        // Get last run times for each API endpoint
        $apiSchedule = $this->getApiSchedule($warActive);
        
        // Get ACTUAL API calls in last minute
        $apiCallsLastMinute = TornApiService::getApiCallsLastMinute();
        
        // Calculate EXPECTED API calls per minute based on schedules
        $expectedCallsPerMinute = $this->calculateExpectedApiCalls($warActive);
        
        return view('admin.index', compact('settings', 'users', 'apiSchedule', 'warActive', 'apiCallsLastMinute', 'expectedCallsPerMinute'));
    }
    
    private function calculateExpectedApiCalls(bool $isWarActive): int
    {
        $settings = FactionSettings::first();
        $warModeEnabled = $settings?->war_mode_enabled ?? false;
        $isWarMode = $warModeEnabled || $isWarActive;
        
        $totalExpected = 0;
        $jobs = ScheduledJob::all();
        
        foreach ($jobs as $job) {
            $cron = $isWarMode && $job->war_cron ? $job->war_cron : $job->cron_expression;
            if (!$cron || $cron === '') continue;
            
            // Calculate runs per minute based on cron
            $runsPerMinute = 1;
            if (preg_match('/^\*\/(\d+)\s+/', $cron, $m)) {
                $runsPerMinute = 1 / (int)$m[1];
            } elseif ($cron === '0 * * * *') {
                $runsPerMinute = 1 / 60;
            } elseif (str_starts_with($cron, '0 0')) {
                $runsPerMinute = 1 / 1440;
            }
            
            // Extract number from api_est (e.g., "1 call" -> 1, "5 calls" -> 5)
            $apiCalls = (int) preg_replace('/[^0-9]/', '', $job->api_est ?? '1');
            $apiCalls = $apiCalls ?: 1;
            
            $totalExpected += $runsPerMinute * $apiCalls;
        }
        
        return (int)$totalExpected; // Per minute
    }

    private function getApiSchedule(): array
    {
        $settings = FactionSettings::first();
        $warModeEnabled = $settings?->war_mode_enabled ?? false;
        
        $jobs = ScheduledJob::orderBy('command')->get();
        $schedule = [];
        
        foreach ($jobs as $job) {
            $key = match($job->command) {
                'torn:sync-faction' => 'faction_sync',
                'torn:sync-ffstats' => 'member_stats',
                'torn:sync-wars' => 'ranked_wars',
                'torn:sync-active' => 'active_wars',
                'torn:sync-attacks' => 'war_attacks',
                'torn:sync-chains' => 'war_chains',
                'torn:sync-ocs' => 'organized_crimes',
                'torn:sync-stocks' => 'stocks',
                'torn:sync-items' => 'items',
                'torn:sync-profiles' => 'player_profiles',
                default => null,
            };
            if (!$key) continue;
            
            $schedule[$key] = [
                'name' => $job->command,
                'schedule' => $this->formatCron($job->cron_expression ?? ''),
                'schedule_raw' => $job->cron_expression ?? '',
                'war_schedule' => $job->war_cron ? $this->formatCron($job->war_cron) : '—',
                'war_schedule_raw' => $job->war_cron ?? '',
                'description' => $job->description,
                'api_calls' => $job->api_est ?? '1 call',
                'essential' => in_array($job->command, ['torn:sync-active', 'torn:sync-attacks', 'torn:sync-chains']),
            ];
            
            $lastRun = DataRefreshLog::where('data_type', $key)->where('status', 'completed')->latest('completed_at')->first();
            $schedule[$key]['last_run'] = $lastRun?->completed_at?->diffForHumans() ?? 'Never';
            $schedule[$key]['last_run_at'] = $lastRun?->completed_at;
        }

        return $schedule;
    }

    public function toggleWarMode(Request $request)
    {
        $settings = FactionSettings::first();
        $settings->war_mode_enabled = $request->boolean('enabled');
        $settings->save();
        return back()->with('status', 'War mode ' . ($settings->war_mode_enabled ? 'enabled' : 'disabled'));
    }

    public function updateJobSchedule(Request $request, $job)
    {
        $jobModel = ScheduledJob::where('command', 'torn:' . $job)->firstOrFail();
        
        $schedule = $request->input('schedule');
        $warSchedule = $request->input('war_schedule');
        
        \Illuminate\Support\Facades\Log::info('updateJobSchedule', [
            'job' => $job,
            'schedule' => $schedule,
            'schedule_type' => gettype($schedule),
            'warSchedule' => $warSchedule,
        ]);
        
        // Allow empty string to save (means "Never"/disable)
        if ($schedule !== null) {
            $jobModel->cron_expression = $schedule;
        }
        if ($warSchedule !== null) {
            $jobModel->war_cron = $warSchedule;
        }
        
        $jobModel->save();
        return back()->with('status', 'Schedule updated');
    }

private function formatCron(string $cron): string
    {
        if ($cron === '' || $cron === null) return 'Never';
        
        $c = preg_replace('/\s+/', '', trim($cron));
        if ($c === '*/1*****') return 'Every 1 min';
        if ($c === '*/5*****') return 'Every 5 min';
        if ($c === '*/10***') return 'Every 5 min';
        if ($c === '*/30***') return 'Every 30 min';
        if ($c === '0****') return 'Hourly';
        if ($c === '00***') return 'Daily';
        if ($c === '010***') return 'Daily 01:00';
        if ($c === '020***') return 'Daily 02:00';
        return $cron ?: 'Not set';
    }

    public function updateFactionSettings(Request $request)
    {
        $request->validate([
            'faction_id' => ['required', 'integer'],
            'torn_api_key' => ['required', 'string', 'max:100'],
            'ffscouter_api_key' => ['nullable', 'string', 'max:100'],
            'auto_sync_enabled' => ['boolean'],
            'base_domain' => ['nullable', 'string', 'max:255'],
            'discord_enabled' => ['boolean'],
            'discord_bot_token' => ['nullable', 'string', 'max:100'],
            'discord_server_id' => ['nullable', 'integer'],
            'discord_channel_id' => ['nullable', 'integer'],
        ]);

        $settings = FactionSettings::first();
        $settings->update([
            'faction_id' => $request->faction_id,
            'torn_api_key' => $request->torn_api_key,
            'ffscouter_api_key' => $request->ffscouter_api_key,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
            'base_domain' => $request->input('base_domain') ?: null,
            'discord_enabled' => $request->boolean('discord_enabled'),
            'discord_bot_token' => $request->input('discord_bot_token') ?: null,
            'discord_server_id' => $request->input('discord_server_id') ?: null,
            'discord_channel_id' => $request->input('discord_channel_id') ?: null,
        ]);

        if ($request->boolean('discord_enabled') && $request->input('discord_bot_token')) {
            try {
                $discord = app(DiscordBotService::class);
                $discord->restart();
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to restart Discord bot: ' . $e->getMessage());
            }
        }

        return back()->with('status', 'Faction settings updated successfully.');
    }

    public function restartDiscordBot(Request $request)
    {
        try {
            $discord = app(DiscordBotService::class);
            $discord->restart();
            return back()->with('status', 'Discord bot restarted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to restart Discord bot: ' . $e->getMessage());
        }
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'torn_player_id' => ['required', 'integer', 'unique:users'],
            'is_admin' => ['boolean'],
        ]);

        $settings = FactionSettings::first();
        
        if (!$settings || !$settings->torn_api_key) {
            return back()->with('error', 'Torn API key not configured.');
        }

        $tornApi = new TornApiService();
        $playerData = $tornApi->getPlayer($request->torn_player_id, 'profile');

        if (!$playerData || !isset($playerData['faction'])) {
            return back()->with('error', 'Player not found or has no faction.');
        }

        if (!isset($playerData['faction']['faction_id']) || 
            $playerData['faction']['faction_id'] != $settings->faction_id) {
            return back()->with('error', 'Player is not a member of the faction.');
        }

        $user = User::create([
            'name' => $request->name,
            'torn_player_id' => $request->torn_player_id,
            'is_admin' => $request->boolean('is_admin'),
            'invited_by' => auth()->id(),
            'status' => User::STATUS_INVITED,
        ]);

        $token = $user->regenerateInvitationToken();

        $inviteUrl = null;
        $warning = null;
        
        if ($settings->base_domain) {
            $inviteUrl = rtrim($settings->base_domain, '/') . '/invite/' . $token;
        } else {
            $warning = 'Base Domain not configured. Full invite link not generated. Configure in Settings to enable automatic link generation.';
        }

        return back()->with([
            'status' => 'User created successfully.',
            'invite_url' => $inviteUrl,
            'invite_token' => $token,
            'invited_user_name' => $user->name,
            'base_domain_warning' => $warning,
        ]);
    }

    public function regenerateInvite(User $user)
    {
        $settings = FactionSettings::first();
        $token = $user->regenerateInvitationToken();

        $inviteUrl = null;
        if ($settings->base_domain) {
            $inviteUrl = rtrim($settings->base_domain, '/') . '/invite/' . $token;
        }

        return back()->with([
            'status' => 'Invitation regenerated.',
            'invite_url' => $inviteUrl,
            'invited_user_name' => $user->name,
        ]);
    }

    public function toggleAdmin(User $user)
    {
        $user->update(['is_admin' => !$user->is_admin]);
        return back()->with('status', 'Admin status toggled.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();
        return back()->with('status', 'User deleted.');
    }

    public function checkForUpdates()
    {
        $currentVersion = config('tornops.version');
        $currentCommit = config('tornops.commit', '');
        $repo = config('tornops.github_repo');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'TornOps'])
                ->get("https://api.github.com/repos/{$repo}/releases/latest");
            
            if ($response->successful()) {
                $latestVersion = ltrim($response->json()['tag_name'] ?? 'v1.0.0', 'v');
                $releaseUrl = $response->json()['html_url'] ?? null;

                $updateAvailable = version_compare($latestVersion, $currentVersion, '>');

                return back()->with([
                    'update_check' => true,
                    'current_version' => $currentVersion,
                    'current_commit' => $currentCommit,
                    'latest_version' => $latestVersion,
                    'update_available' => $updateAvailable,
                    'release_url' => $releaseUrl,
                ]);
            }

            if ($response->status() === 404) {
                $commitResponse = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'TornOps'])
                    ->get("https://api.github.com/repos/{$repo}/commits?per_page=1");
                
                if ($commitResponse->successful()) {
                    $latestCommit = $commitResponse->json()[0]['sha'] ?? null;
                    $commitUrl = $commitResponse->json()[0]['html_url'] ?? null;

                    $updateAvailable = $latestCommit !== null && $latestCommit !== $currentCommit;

                    return back()->with([
                        'update_check' => true,
                        'current_version' => $currentVersion,
                        'current_commit' => $currentCommit,
                        'latest_version' => 'commit: ' . substr($latestCommit, 0, 7),
                        'update_available' => $updateAvailable,
                        'release_url' => $commitUrl,
                        'no_releases' => true,
                    ]);
                }
            }

            $status = $response->status();
            $body = $response->body();
            return back()->with('error', "GitHub API error (HTTP {$status}): {$body}");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to connect to GitHub: ' . $e->getMessage());
        }
    }

    public function upgrade()
    {
        $repo = config('tornops.github_repo');
        
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'TornOps'])
                ->get("https://api.github.com/repos/{$repo}/commits?per_page=1");
            
            if (!$response->successful()) {
                return back()->with('error', 'Could not fetch latest commit from GitHub.');
            }

            $latestCommit = $response->json()[0]['sha'] ?? null;
            $latestCommitShort = $latestCommit ? substr($latestCommit, 0, 7) : null;
            
            if (!$latestCommit) {
                return back()->with('error', 'Could not determine latest commit.');
            }

            $currentCommit = config('tornops.commit');
            
            if ($currentCommit === $latestCommit) {
                return back()->with('status', 'Already up to date.');
            }

            $output = [];
            $returnCode = 0;
            
            $gitPullCmd = 'mkdir -p /tmp/git-home && HOME=/tmp/git-home GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0="safe.directory" GIT_CONFIG_VALUE_0="/var/www/html" /usr/bin/git -C /var/www/html remote set-url origin https://github.com/bafplus/tornops.git 2>&1; HOME=/tmp/git-home GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0="safe.directory" GIT_CONFIG_VALUE_0="/var/www/html" /usr/bin/git -C /var/www/html pull 2>&1';
            exec('sudo -n /bin/sh -c "chown -R www-data:www-data /var/www/html/.git"', $chownOut, $chownRet);
            exec($gitPullCmd, $output, $returnCode);
            
            if ($returnCode === 0) {
                $configPath = base_path('config/tornops.php');
                $configContent = file_get_contents($configPath);
                $newContent = preg_replace(
                    "/'commit' => '[^']*'/",
                    "'commit' => '{$latestCommit}'",
                    $configContent
                );
                file_put_contents($configPath, $newContent);

                exec('composer install --no-dev --quiet 2>&1', $composerOutput, $composerReturn);
                
                $message = 'Application upgraded to ' . $latestCommitShort;
                if ($composerReturn === 0) {
                    $message .= '. Composer dependencies updated.';
                }
                
                return back()->with('status', $message);
            }
            
            return back()->with('error', 'Git pull failed: ' . implode("\n", $output));
        } catch (\Exception $e) {
            return back()->with('error', 'Upgrade failed: ' . $e->getMessage());
        }
    }

    public function scheduledJobs()
    {
        $jobs = ScheduledJob::orderBy('command')->get();
        return view('admin.scheduled-jobs', compact('jobs'));
    }

    public function updateScheduledJob(Request $request, $id)
    {
        $job = ScheduledJob::findOrFail($id);
        
        $cronPreset = $request->input('cron_custom_preset');
        $cronValue = $request->input('cron_custom_value');
        $cronUnit = $request->input('cron_custom_unit', 'minutes');
        $cronHour = $request->input('cron_custom_hour');
        $cronMinute = $request->input('cron_custom_minute');
        $cronExpression = $this->buildCronFromPreset($cronPreset, $cronValue, $cronUnit, $cronHour, $cronMinute);
        
        $warPreset = $request->input('war_custom_preset');
        $warValue = $request->input('war_custom_value');
        $warUnit = $request->input('war_custom_unit', 'minutes');
        $warCron = $request->boolean('war_mode_only') 
            ? $this->buildCronFromPreset($warPreset, $warValue, $warUnit)
            : null;
        
        $job->update([
            'enabled' => $request->boolean('enabled'),
            'cron_expression' => $cronExpression,
            'war_mode_only' => $request->boolean('war_mode_only'),
            'war_cron' => $warCron,
        ]);

        return back()->with('status', 'Job updated successfully.');
    }
    
    private function buildCronFromPreset(?string $preset, ?string $value, string $unit, ?string $hour = null, ?string $minute = null): string
    {
        if ($preset && $preset !== 'custom') {
            if ($preset === 'every_minute') return '* * * * *';
            if ($preset === 'every_odd_minute') return '1-59/2 * * * *';
            if ($preset === 'every_even_minute') return '2-58/2 * * * *';
            if (str_ends_with($preset, '_min')) {
                $val = str_replace(['every_', '_min'], '', $preset);
                return '*/' . $val . ' * * * *';
            }
            if (str_ends_with($preset, '_hour')) {
                $val = str_replace(['every_', '_hour'], '', $preset);
                return '0 */' . $val . ' * * *';
            }
            if (str_starts_with($preset, 'every_day_')) {
                $time = str_replace('every_day_', '', $preset);
                return '0 ' . $time . ' * * *';
            }
        }
        
        if ($value && is_numeric($value)) {
            if ($unit === 'minutes') return '*/' . $value . ' * * * *';
            if ($unit === 'hours') return '0 */' . $value . ' * * *';
            if ($unit === 'days') {
                $h = $hour ?? '0';
                $m = $minute ?? '0';
                return $m . ' ' . $h . ' * * *';
            }
        }
        
        return '*/10 * * * *';
    }

    public function seedScheduledJobs()
    {
        $definitions = [
            'torn:sync-faction' => [
                'description' => 'Sync faction members from Torn API',
                'war_mode_only' => false,
                'default_cron' => '*/5 * * * *',
                'api_info' => '1 bulk call',
                'api_est' => '1',
            ],
            'torn:sync-ffstats' => [
                'description' => 'Sync FF stats via FF Scouter API',
                'war_mode_only' => false,
                'default_cron' => '*/10 * * * *',
                'api_info' => '1 batch call',
                'api_est' => '15-20',
            ],
            'torn:sync-wars' => [
                'description' => 'Sync ranked wars list',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/5 * * * *',
                'api_info' => '1 bulk call',
                'api_est' => '1',
            ],
            'torn:sync-active' => [
                'description' => 'Sync active war details and scores',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/1 * * * *',
                'api_info' => '1 call per opponent faction',
                'api_est' => '2-5',
            ],
            'torn:sync-attacks' => [
                'description' => 'Sync war attacks data',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/1 * * * *',
                'api_info' => '1 call per active war',
                'api_est' => '10-50',
            ],
            'torn:sync-chains' => [
                'description' => 'Sync war chain data',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/1 * * * *',
                'api_info' => '1 call per faction',
                'api_est' => '2',
            ],
            'torn:check-faction-membership' => [
                'description' => 'Check faction membership and sync new members',
                'war_mode_only' => false,
                'default_cron' => '0 */6 * * *',
                'api_info' => '1 bulk call',
                'api_est' => '1',
            ],
            'torn:sync-profiles' => [
                'description' => 'Sync player profile data from Torn API',
                'war_mode_only' => false,
                'default_cron' => '0 * * * *',
                'api_info' => '1 call per player (faction + opponents)',
                'api_est' => '50-100',
            ],
            'torn:sync-stocks' => [
                'description' => 'Sync market stocks data',
                'war_mode_only' => false,
                'default_cron' => '0 1 * * *',
                'api_info' => '1 bulk call',
                'api_est' => '1',
            ],
            'torn:sync-items' => [
                'description' => 'Sync item market data',
                'war_mode_only' => false,
                'default_cron' => '0 2 * * *',
                'api_info' => '1 bulk call',
                'api_est' => '1',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($definitions as $command => $config) {
            $exists = ScheduledJob::where('command', $command)->exists();

            $data = [
                'description' => $config['description'],
                'enabled' => true,
                'cron_expression' => $config['default_cron'],
                'war_mode_only' => $config['war_mode_only'],
                'war_cron' => $config['war_cron'] ?? null,
                'api_info' => $config['api_info'] ?? null,
                'api_est' => $config['api_est'] ?? null,
            ];

            if ($exists) {
                ScheduledJob::where('command', $command)->update($data);
                $updated++;
            } else {
                ScheduledJob::create(array_merge(['command' => $command], $data));
                $created++;
            }
        }

        return back()->with('status', "Seeded: {$created} created, {$updated} updated.");
    }

    public function resetApiCalls()
    {
        TornApiService::resetApiCallsCounter();
        return back()->with('status', 'API calls counter reset.');
    }
}