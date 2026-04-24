<?php

namespace App\Services;

use App\Models\OrganizedCrime;
use App\Models\FactionMember;

class OCService
{
    public static function getActiveOCs(): array
    {
        $factionId = \App\Models\FactionSettings::value('faction_id');
        
        if (!$factionId) {
            return [];
        }

        $ocs = OrganizedCrime::where('faction_id', $factionId)
            ->whereIn('status', ['planning', 'recruiting', 'ready'])
            ->with('slots')
            ->orderBy('ready_at')
            ->get();

        $alerts = [];
        
        foreach ($ocs as $oc) {
            $openSlots = $oc->slots->filter(fn($s) => !$s->user_id);
            $filledSlots = $oc->slots->filter(fn($s) => $s->user_id);
            
            // Check for open slots when ready or recruiting
            if (in_array($oc->status, ['ready', 'recruiting']) && $openSlots->isNotEmpty()) {
                $alerts[] = [
                    'type' => 'open_slots',
                    'severity' => $oc->status === 'ready' ? 'warning' : 'info',
                    'oc' => $oc,
                    'message' => "{$oc->name} has {$openSlots->count()} open slot(s)",
                    'open_count' => $openSlots->count(),
                ];
            }
            
            // Check for delayer members
            if ($oc->status === 'ready') {
                foreach ($filledSlots as $slot) {
                    $member = FactionMember::where('player_id', $slot->user_id)->first();
                    if ($member && $member->status_color !== 'green') {
                        $alerts[] = [
                            'type' => 'delayer',
                            'severity' => 'danger',
                            'oc' => $oc,
                            'message' => "{$member->name} is {$member->status_color} for {$oc->name}",
                            'member' => $member,
                        ];
                    }
                }
            }
            
            // OC ready soon
            if ($oc->status === 'recruiting' && $oc->ready_at) {
                $readyTime = \Carbon\Carbon::createFromTimestamp($oc->ready_at);
                $minutesUntil = now()->diffInMinutes($readyTime, false);
                
                if ($minutesUntil > 0 && $minutesUntil <= 30) {
                    $alerts[] = [
                        'type' => 'ready_soon',
                        'severity' => 'info',
                        'oc' => $oc,
                        'message' => "{$oc->name} ready in ~{$minutesUntil} min",
                        'minutes_until' => $minutesUntil,
                    ];
                }
            }
        }

        return $alerts;
    }
}
