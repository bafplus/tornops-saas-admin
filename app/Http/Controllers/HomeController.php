<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function order(Request $request)
    {
        // Handle order form submission
        $validated = $request->validate([
            'faction_name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|alpha_dash|unique:factions,slug',
            'torn_faction_id' => 'required|integer',
            'email' => 'required|email',
        ]);

        // Create pending faction
        \App\Models\Faction::create([
            'name' => $validated['faction_name'],
            'slug' => $validated['slug'],
            'torn_faction_id' => $validated['torn_faction_id'],
            'status' => 'pending',
            'master_key' => bin2hex(random_bytes(16)),
        ]);

        return redirect()->route('home')->with('success', 'Order received! We will contact you shortly.');
    }
}