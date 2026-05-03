<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;

class TravelController extends Controller
{
    public function index()
    {
        $travelMethod = FactionSettings::value('travel_method', 1);
        return view('travel.index', compact('travelMethod'));
    }
}
