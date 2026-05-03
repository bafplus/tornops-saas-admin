<?php

namespace App\Services;

use App\Models\FactionSettings;
use App\Models\User;
use App\Models\OrganizedCrime;
use App\Models\FactionMember;
use App\Models\RankedWar;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\Member;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DiscordBotService
{
    private ?Discord $discord = null;
    private ?FactionSettings $settings = null;
    private bool $isRunning = false;

    private array $trackedMessages = [];

    public function __construct()
    {
        $this->settings = FactionSettings::first();
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function start(): void
    {
        if (!$this->settings || !$this->settings->discord_enabled || !$this->settings->discord_bot_token) {
            Log::info('Discord bot not configured, skipping start');
            return;
        }

        if ($this->isRunning) {
            Log::info('Discord bot already running');
            return;
        }

        try {
            $discord = new Discord([
                'token' => $this->settings->discord_bot_token,
                'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
            ]);

            $discord->on('ready', function (Discord $discord) {
                $this->isRunning = true;
                Log::info('Discord bot connected as: ' . $discord->user->username);
                $this->handleCommands($discord);
            });

            $discord->on('message', function (Message $message, Discord $discord) {
                $this->handleMessage($message, $discord);
            });

            $discord->run();
            $this->discord = $discord;

        } catch (\Exception $e) {
            Log::error('Failed to start Discord bot: ' . $e->getMessage());
            $this->isRunning = false;
        }
    }

    public function stop(): void
    {
        if ($this->discord) {
            $this->discord->close();
            $this->discord = null;
        }
        $this->isRunning = false;
        Log::info('Discord bot stopped');
    }

    public function restart(): void
    {
        $this->stop();
        sleep(2);
        $this->start();
    }

    private function handleCommands(Discord $discord): void
    {
        $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($discord) {
            $this->handleMessage($message, $discord);
        });
    }

    private function handleMessage(Message $message, Discord $discord): void
    {
        if ($message->author->bot) {
            return;
        }

        $content = trim($message->content);
        
        if (str_starts_with($content, '!')) {
            $parts = explode(' ', $content);
            $command = strtolower($parts[0]);
            $args = array_slice($parts, 1);

            switch ($command) {
                case '!verify':
                    $this->handleVerify($message, $args);
                    break;
                case '!oc':
                    $this->handleOC($message);
                    break;
                case '!members':
                    $this->handleMembers($message);
                    break;
                case '!wars':
                    $this->handleWars($message);
                    break;
                case '!mystatus':
                    $this->handleMyStatus($message);
                    break;
                case '!help':
                    $this->handleHelp($message);
                    break;
            }
        }
    }

    private function handleVerify(Message $message, array $args): void
    {
        if (empty($args[0])) {
            $message->channel->sendMessage('Usage: `!verify [torn_id]`');
            return;
        }

        $tornId = (int) $args[0];
        $discordUserId = $message->author->id;

        $user = User::where('discord_user_id', $discordUserId)->first();
        
        if (!$user) {
            $message->channel->sendMessage("Your Discord account is not linked. Go to TornOps Settings and add your Discord ID first, then try again.");
            return;
        }

        if ($user->discord_verified_at) {
            $message->channel->sendMessage("You're already verified!");
            return;
        }

        $user->discord_verified_at = now();
        $user->save();

        $message->channel->sendMessage("✅ You're now verified as faction member! You can use TornOps commands.");
    }

    private function handleOC(Message $message): void
    {
        if (!$this->isVerifiedUser($message->author->id)) {
            $message->channel->sendMessage('Please verify first with `!verify [torn_id]`');
            return;
        }

        $ocs = OrganizedCrime::where('faction_id', $this->settings->faction_id)
            ->whereIn('status', ['planning', 'recruiting', 'ready'])
            ->orderBy('ready_at')
            ->limit(5)
            ->get();

        if ($ocs->isEmpty()) {
            $message->channel->sendMessage('No active OCs found.');
            return;
        }

        $embed = [
            'title' => '🎯 Active Organized Crimes',
            'color' => 0xffa500,
            'fields' => [],
        ];

        foreach ($ocs as $oc) {
            $status = match($oc->status) {
                'planning' => '📋 Planning',
                'recruiting' => '🔧 Recruiting',
                'ready' => '✅ Ready',
                default => $oc->status,
            };

            $readyTime = $oc->ready_at ? \Carbon\Carbon::createFromTimestamp($oc->ready_at)->diffForHumans() : 'N/A';
            
            $embed['fields'][] = [
                'name' => $oc->name . " (Diff: {$oc->difficulty}/10)",
                'value' => "{$status} | Ready: {$readyTime}",
                'inline' => false,
            ];
        }

        $message->channel->sendMessage('', false, $embed);
    }

    private function handleMembers(Message $message): void
    {
        if (!$this->isVerifiedUser($message->author->id)) {
            $message->channel->sendMessage('Please verify first with `!verify [torn_id]`');
            return;
        }

        $members = FactionMember::where('faction_id', $this->settings->faction_id)
            ->orderBy('name')
            ->limit(10)
            ->get();

        $online = $members->where('status_color', 'green')->count();
        $away = $members->where('status_color', 'red')->count();
        $hospital = $members->where('status_color', 'blue')->count();

        $embed = [
            'title' => '👥 Faction Members',
            'color' => 0x3498db,
            'fields' => [
                [
                    'name' => 'Status Summary',
                    'value' => "🟢 Online: {$online}\n🔴 Away: {$away}\n🏥 Hospital: {$hospital}\n📊 Total: " . $members->count(),
                    'inline' => false,
                ],
            ],
        ];

        $message->channel->sendMessage('', false, $embed);
    }

    private function handleWars(Message $message): void
    {
        if (!$this->isVerifiedUser($message->author->id)) {
            $message->channel->sendMessage('Please verify first with `!verify [torn_id]`');
            return;
        }

        $wars = RankedWar::where('faction_id', $this->settings->faction_id)
            ->whereNull('winner')
            ->orWhere('winner', 0)
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        if ($wars->isEmpty()) {
            $message->channel->sendMessage('No active wars.');
            return;
        }

        $embed = [
            'title' => '⚔️ Active Wars',
            'color' => 0xe74c3c,
            'fields' => [],
        ];

        foreach ($wars as $war) {
            $embed['fields'][] = [
                'name' => $war->opponent_faction_name ?? 'Unknown',
                'value' => "Score: {$war->score_ours} - {$war->score_them}\nStarted: " . ($war->start_date ? $war->start_date->format('M j, H:i') : 'N/A'),
                'inline' => false,
            ];
        }

        $message->channel->sendMessage('', false, $embed);
    }

    private function handleMyStatus(Message $message): void
    {
        $user = User::where('discord_user_id', $message->author->id)->first();
        
        if (!$user || !$user->discord_verified_at) {
            $message->channel->sendMessage('Please verify first with `!verify [torn_id]`');
            return;
        }

        $member = FactionMember::where('faction_id', $this->settings->faction_id)
            ->where('player_id', $user->torn_player_id)
            ->first();

        if (!$member) {
            $message->channel->sendMessage('Could not find your faction member data.');
            return;
        }

        $status = match($member->status_color) {
            'green' => '🟢 Online',
            'red' => '🔴 Away',
            'blue' => '🏥 Hospital',
            default => '⚪ Offline',
        };

        $life = $member->life_current ?? 'N/A';
        $energy = $member->energy ?? 'N/A';
        $lifeMax = $member->life_max ?? 'N/A';
        $energyMax = $member->energy_max ?? 'N/A';
        $level = $member->level ?? 'N/A';

        $message->channel->sendMessage("**Your Status**
{$status}
Life: {$life} / {$lifeMax}
Energy: {$energy} / {$energyMax}
Level: {$level}");
    }

    private function handleHelp(Message $message): void
    {
        $message->channel->sendMessage("**TornOps Commands**

`!verify [torn_id]` - Link your Discord to Torn account
`!oc` - Show active organized crimes
`!members` - Show faction member status
`!wars` - Show active wars
`!mystatus` - Show your personal stats
`!help` - Show this help message

Note: You must verify first with `!verify [torn_id]`");
    }

    private function isVerifiedUser(string $discordUserId): bool
    {
        $user = User::where('discord_user_id', $discordUserId)->first();
        return $user && $user->discord_verified_at;
    }

    public function sendMessage(int $channelId, string $content, ?array $embed = null): ?Message
    {
        if (!$this->discord || !$this->isRunning) {
            return null;
        }

        try {
            $channel = $this->discord->getChannel($channelId);
            if (!$channel) {
                Log::warning("Discord channel {$channelId} not found");
                return null;
            }

            return $channel->sendMessage($content, false, $embed);
        } catch (\Exception $e) {
            Log::error("Failed to send Discord message: " . $e->getMessage());
            return null;
        }
    }

    public function editMessage(int $channelId, string $messageId, string $content, ?array $embed = null): bool
    {
        if (!$this->discord || !$this->isRunning) {
            return false;
        }

        try {
            $channel = $this->discord->getChannel($channelId);
            $message = $channel->getMessage($messageId);
            $message->content = $content;
            if ($embed) {
                $message->embeds = $embed;
            }
            $message->save();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to edit Discord message: " . $e->getMessage());
            return false;
        }
    }

    public function deleteMessage(int $channelId, string $messageId): bool
    {
        if (!$this->discord || !$this->isRunning) {
            return false;
        }

        try {
            $channel = $this->discord->getChannel($channelId);
            $message = $channel->getMessage($messageId);
            $message->delete();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete Discord message: " . $e->getMessage());
            return false;
        }
    }

    public function sendAlert(string $type, string $title, string $message, int $color = 0xffa500): ?Message
    {
        if (!$this->settings || !$this->settings->discord_channel_id) {
            return null;
        }

        $embed = [
            'title' => $title,
            'description' => $message,
            'color' => $color,
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->sendMessage($this->settings->discord_channel_id, '', $embed);
    }

    public function trackMessage(string $key, int $channelId, string $messageId): void
    {
        $this->trackedMessages[$key] = [
            'channel_id' => $channelId,
            'message_id' => $messageId,
        ];
    }

    public function getTrackedMessage(string $key): ?array
    {
        return $this->trackedMessages[$key] ?? null;
    }

    public function clearTrackedMessage(string $key): void
    {
        unset($this->trackedMessages[$key]);
    }
}
