<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FactionSettings;
use App\Services\TornApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SetupController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $hasAdmin = User::where('is_admin', true)->exists();

        if ($settings && $hasAdmin) {
            return redirect('/');
        }

        return view('setup.index');
    }

    public function store(Request $request)
    {
        try {
        $validator = Validator::make($request->all(), [
            'faction_id' => 'required|numeric',
            'torn_api_key' => 'required|string|min:16',
            'ffscouter_api_key' => 'nullable|string',
            'base_domain' => 'nullable|url',
            'admin_name' => 'required|string|max:255|unique:users,name',
            'torn_player_id' => 'required|numeric|unique:users,torn_player_id',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        FactionSettings::updateOrCreate(
            ['id' => 1],
            [
                'faction_id' => $request->input('faction_id'),
                'torn_api_key' => $request->input('torn_api_key'),
                'ffscouter_api_key' => $request->input('ffscouter_api_key'),
                'auto_sync_enabled' => true,
                'base_domain' => $request->input('base_domain'),
            ]
        );

        $settings = FactionSettings::first();
        $tornApi = new TornApiService();
        $playerData = $tornApi->getPlayer($request->input('torn_player_id'), 'profile', $settings->torn_api_key);

        if (!$playerData || !isset($playerData['faction'])) {
            return back()->withErrors(['torn_player_id' => 'Player not found or has no faction.'])->withInput();
        }

        if (!isset($playerData['faction']['faction_id']) || $playerData['faction']['faction_id'] != $settings->faction_id) {
            return back()->withErrors(['torn_player_id' => 'Player is not a member of the faction.'])->withInput();
        }

        User::create([
            'name' => $request->input('admin_name'),
            'password' => Hash::make($request->input('admin_password')),
            'torn_player_id' => $request->input('torn_player_id'),
            'is_admin' => true,
            'status' => User::STATUS_ACTIVE,
        ]);

        return redirect('/')->with('success', 'Setup completed successfully!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Setup error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Setup failed: ' . $e->getMessage()])->withInput();
        }
    }
}