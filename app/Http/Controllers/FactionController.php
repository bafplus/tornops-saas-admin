<?php

namespace App\Http\Controllers;

use App\Models\Faction;
use App\Services\FactionService;
use Illuminate\Http\Request;

class FactionController extends Controller
{
    protected FactionService $factionService;

    public function __construct(FactionService $factionService)
    {
        $this->factionService = $factionService;
    }

    public function index()
    {
        $factions = Faction::orderBy('created_at', 'desc')->get();
        return view('admin.factions', compact('factions'));
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

        $faction = Faction::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'torn_faction_id' => $request->torn_faction_id,
            'master_key' => $masterKey,
            'status' => 'creating',
            'is_trial' => $request->boolean('is_trial'),
            'payment' => $request->payment ?? 'Due',
            'amount' => $request->amount ?? 0,
        ]);

        $result = $this->factionService->createFactionInstance($faction);

        $faction->update([
            'port' => $result['port'],
            'status' => 'active',
            'log' => $result['output'],
        ]);

        return redirect()->route('admin.factions')->with('success', 'Faction created successfully');
    }

    public function start(Faction $faction)
    {
        $this->factionService->startContainer($faction->slug);
        return back()->with('success', 'Faction started');
    }

    public function stop(Faction $faction)
    {
        $this->factionService->stopContainer($faction->slug);
        return back()->with('success', 'Faction stopped');
    }

    public function destroy(Faction $faction)
    {
        $this->factionService->deleteContainer($faction->slug);
        $faction->delete();
        return back()->with('success', 'Faction deleted');
    }

    public function loginAs(Faction $faction)
    {
        $url = $this->factionService->loginAs($faction);
        return redirect()->away($url);
    }

    public function edit(Faction $faction)
    {
        return view('admin.factions.edit', compact('faction'));
    }

    public function update(Request $request, Faction $faction)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|alpha_dash|unique:factions,slug,' . $faction->id,
            'torn_faction_id' => 'required|integer',
        ]);

        $oldSlug = $faction->slug;
        $newSlug = $request->slug;

        $faction->fill([
            'name' => $request->name,
            'slug' => $newSlug,
            'torn_faction_id' => $request->torn_faction_id,
            'is_trial' => $request->boolean('is_trial'),
            'payment' => $request->payment ?? 'Due',
            'amount' => $request->amount ?? 0,
        ]);

        if ($oldSlug !== $newSlug) {
            $this->factionService->updateInstance($faction, $oldSlug);
        } else {
            $faction->save();
        }

        return redirect()->route('admin.factions')->with('success', 'Faction updated successfully');
    }

    public function regenerateKey(Faction $faction)
    {
        $this->factionService->regenerateKey($faction);
        return back()->with('success', 'Master key regenerated');
    }

    public function checkUpdate()
    {
        $updateInfo = $this->factionService->checkForImageUpdate();
        return back()->with('update_info', $updateInfo);
    }

    public function updateAll()
    {
        $results = $this->factionService->updateAllInstances();

        if (!empty($results['errors'])) {
            return back()->with('error', 'Update completed with errors: ' . implode(', ', $results['errors']));
        }

        $message = 'Update completed. ';
        $message .= 'Stopped: ' . implode(', ', $results['stopped']) . '. ';
        $message .= 'Started: ' . implode(', ', $results['started']) . '.';
        $message .= $results['image_removed'] ? ' Image removed.' : ' Image removal failed.';
        $message .= $results['image_pulled'] ? ' New image pulled.' : ' Image pull failed.';

        return back()->with('success', $message);
    }
}
