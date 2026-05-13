<?php

namespace App\Http\Controllers;

use App\Models\Bounty;
use Illuminate\Http\Request;

class BountyController extends Controller
{
    public function index(Request $request)
    {
        $query = Bounty::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('target_name', 'like', "%{$search}%")
                  ->orWhere('lister_name', 'like', "%{$search}%")
                  ->orWhere('target_id', $search)
                  ->orWhere('lister_id', $search);
            });
        }

        if ($minReward = $request->get('min_reward')) {
            $query->where('reward', '>=', (int) $minReward);
        }

        if ($maxReward = $request->get('max_reward')) {
            $query->where('reward', '<=', (int) $maxReward);
        }

        if ($minLevel = $request->get('min_level')) {
            $query->where('target_level', '>=', (int) $minLevel);
        }

        if ($maxLevel = $request->get('max_level')) {
            $query->where('target_level', '<=', (int) $maxLevel);
        }

        $bounties = $query->orderBy('reward', 'desc')
            ->orderBy('target_name')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'total' => Bounty::count(),
            'total_value' => Bounty::sum('reward'),
            'max_reward' => Bounty::max('reward'),
            'last_sync' => Bounty::max('last_synced_at'),
        ];

        return view('bounties.index', compact('bounties', 'stats'));
    }
}
