<?php

namespace App\Http\Controllers;

use App\Models\Faction;
use App\Services\DockerService;
use Illuminate\Http\Request;

class FactionController extends Controller
{
    protected DockerService $docker;

    public function __construct(DockerService $docker)
    {
        $this->docker = $docker;
    }

    public function index()
    {
        $factions = Faction::orderBy('created_at', 'desc')->get();
        return view('admin.factions.index', compact('factions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:factions,slug|alpha_dash',
            'torn_faction_id' => 'required|integer',
        ]);

        $faction = Faction::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'torn_faction_id' => $request->torn_faction_id,
            'status' => Faction::STATUS_PENDING,
            'master_key' => bin2hex(random_bytes(16)),
            'is_trial' => $request->boolean('is_trial', false),
            'monthly_cost' => $request->integer('monthly_cost', 0),
        ]);

        // If marked as active immediately, create container
        if ($request->boolean('auto_create')) {
            $this->docker->createFactionContainer($faction->slug, $faction->master_key);
            $faction->update(['status' => Faction::STATUS_ACTIVE]);
        }

        return redirect()->route('admin.factions.show', $faction)->with('success', 'Faction created');
    }

    public function show(Faction $faction)
    {
        $status = $this->docker->getContainerStatus($faction->slug);
        return view('admin.factions.show', compact('faction', 'status'));
    }

    public function start(Faction $faction)
    {
        $this->docker->startContainer($faction->slug);
        return back()->with('success', 'Container started');
    }

    public function stop(Faction $faction)
    {
        $this->docker->stopContainer($faction->slug);
        return back()->with('success', 'Container stopped');
    }

    public function destroy(Request $request, Faction $faction)
    {
        // Stop and remove container
        $this->docker->stopContainer($faction->slug);
        $this->docker->deleteContainer($faction->slug);
        
        // Delete database files
        $dbPath = config('app.data_volume_path') . '/' . $faction->slug;
        if (is_dir($dbPath)) {
            exec("rm -rf " . escapeshellarg($dbPath));
        }
        
        $faction->delete();
        return redirect()->route('admin.factions')->with('success', 'Faction deleted');
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