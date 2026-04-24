<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $travelMethod = FactionSettings::value('travel_method', 1);
        return view('settings.index', compact('travelMethod'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }

    public function updateApiKey(Request $request)
    {
        $request->validate([
            'torn_api_key' => ['nullable', 'string', 'max:100'],
        ]);

        $user = auth()->user();
        $user->torn_api_key = $request->torn_api_key;
        $user->save();

        return back()->with('status', 'API key updated successfully.');
    }

    public function updateTravelMethod(Request $request)
    {
        $request->validate([
            'travel_method' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        $settings = FactionSettings::first();
        $settings->travel_method = $request->travel_method;
        $settings->save();

        return back()->with('status', 'Travel method updated successfully.');
    }

    public function updateDiscord(Request $request)
    {
        $request->validate([
            'discord_user_id' => ['nullable', 'string', 'max:50'],
        ]);

        $user = auth()->user();
        $user->discord_user_id = $request->input('discord_user_id') ?: null;
        
        if (!$request->input('discord_user_id')) {
            $user->discord_verified_at = null;
        }
        
        $user->save();

        return back()->with('status', 'Discord ID updated successfully.');
    }
}
