<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\OrganizedCrime;
use App\Models\FactionMember;
use App\Models\Item;

class OrganizedCrimesController extends Controller
{
    public function index()
    {
        $factionId = FactionSettings::value('faction_id');
        
        $ocs = OrganizedCrime::where('faction_id', $factionId)
            ->with('slots')
            ->orderByDesc('oc_created_at')
            ->limit(50)
            ->get();
        
        $playerIds = [];
        $itemIds = [];
        foreach ($ocs as $oc) {
            foreach ($oc->slots as $slot) {
                if ($slot->user_id) {
                    $playerIds[] = $slot->user_id;
                }
                if ($slot->item_required_id) {
                    $itemIds[] = $slot->item_required_id;
                }
            }
        }
        
        $members = FactionMember::where('faction_id', $factionId)
            ->whereIn('player_id', array_unique($playerIds))
            ->get()
            ->keyBy('player_id');
        
        $items = Item::whereIn('id', array_unique($itemIds))
            ->get()
            ->keyBy('id');
        
        foreach ($ocs as $oc) {
            foreach ($oc->slots as $slot) {
                $slot->member_name = $members->get($slot->user_id)?->name ?? null;
                $slot->item_name = $items->get($slot->item_required_id)?->name ?? null;
            }
        }
        
        return view('organized-crimes.index', compact('ocs'));
    }
}
