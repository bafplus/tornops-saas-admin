<?php

namespace App\Http\Controllers;

use App\Models\Faction;
use App\Services\FactionService;
use Illuminate\Http\Request;

class FactionController extends Controller
{
    protected FactionService $faction;

    public function __construct(FactionService $faction)
    {
        $this->faction = $faction;
    }

    public function index()
    {
        $factions = Faction::orderBy('created_at', 'desc')->get();
        $mk = request('master_key', config('app.master_key'));
        return view('admin.factions', compact('factions', 'mk'));
    }

    public function create()
    {
        return view('admin.factions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:factions,slug|alpha_dash',
            'torn_faction_id' => 'required|integer',
        ]);

        $masterKey = bin2hex(random_bytes(16));
        $slug = $request->slug;
        $tornApiKey = $request->input('torn_api_key', '');
        
        // Save to DB first
        $faction = Faction::create([
            'name' => $request->name,
            'slug' => $slug,
            'torn_faction_id' => $request->torn_faction_id,
            'status' => 'creating',
            'master_key' => $masterKey,
            'is_trial' => $request->boolean('is_trial', false),
            'monthly_cost' => $request->integer('monthly_cost', 0),
            'log' => 'Starting...',
        ]);

        // Run the creation script
        $scriptPath = '/home/server/tornops-saas-admin/scripts/create_faction.php';
        $cmd = "php {$scriptPath} {$slug} {$masterKey} " . escapeshellarg($tornApiKey) . " 2>&1";
        
        $output = shell_exec($cmd);
        
        if ($output && strpos($output, '[DONE]') !== false) {
            // Extract port from output
            preg_match('/PORT: (\d+)/', $output, $m);
            $port = $m[1] ?? null;
            
            $faction->update([
                'status' => 'active',
                'port' => $port,
                'log' => $output,
            ]);
            
            return response()->json([
                'success' => true,
                'output' => $output,
            ]);
        }

        $faction->update([
            'status' => 'error',
            'log' => $output,
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $output ?: 'Unknown error',
        ]);
    }

    public function show(Faction $faction)
    {
        $status = $this->faction->getContainerStatus($faction->slug);
        return view('admin.factions.show', compact('faction', 'status'));
    }

    public function start(Faction $faction)
    {
        $this->faction->startContainer($faction->slug);
        return back()->with('success', 'Container started');
    }

    public function stop(Faction $faction)
    {
        $this->faction->stopContainer($faction->slug);
        return back()->with('success', 'Container stopped');
    }

    public function destroy(Request $request, Faction $faction)
    {
        $slug = $faction->slug;
        
        try {
            // Stop container
            $this->faction->stopContainer($slug);
            
            // Remove Cloudflare route
            $this->faction->removeCloudflareRoute($slug);
            
            // Delete instance directory
            $instancePath = config('app.tornops_base_path', '/home/server/tornops-instances') . '/' . $slug;
            if (is_dir($instancePath)) {
                exec("rm -rf " . escapeshellarg($instancePath));
            }
            
            // Delete from DB last (only if earlier steps didn't throw)
            $faction->delete();
            
            return redirect()->route('admin.factions')->with('success', 'Faction deleted');
        } catch (\Exception $e) {
            // Don't delete from DB if any step failed
            return redirect()->route('admin.factions')->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function destroyWithKey(Request $request, Faction $faction)
    {
        $key = $request->query('master_key');
        if ($key !== config('app.master_key')) {
            return response()->json(['error' => 'Invalid master key'], 403);
        }
        return $this->destroy($request, $faction);
    }

    public function regenerateKey(Faction $faction)
    {
        $newKey = bin2hex(random_bytes(16));
        $faction->update([
            'master_key' => $newKey,
            'master_key_generated_at' => now(),
        ]);
        return back()->with('success', 'Master key regenerated');
    }

    public function loginAs(Faction $faction)
    {
        $url = config('app.tornops_url', 'https://tornops.net');
        $loginUrl = $url . '/' . $faction->slug . '/master-login?master_key=' . $faction->master_key;
        return redirect()->away($loginUrl);
    }

    public function toggleTrial(Faction $faction)
    {
        $faction->update(['is_trial' => !$faction->is_trial]);
        return back()->with('success', $faction->is_trial ? 'Marked as trial' : 'Removed trial status');
    }
}