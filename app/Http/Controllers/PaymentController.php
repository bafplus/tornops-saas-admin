<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\Faction;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected \App\Services\FactionService $factionService;

    public function __construct(\App\Services\FactionService $factionService)
    {
        $this->factionService = $factionService;
    }

    public function index(Request $request)
    {
        $query = PaymentHistory::orderBy('created_at', 'desc');

        // Search filters
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('payer_name', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_id', 'like', "%{$search}%");
            });
        }
        if ($factionId = $request->input('faction_id')) {
            $query->where('faction_id', $factionId);
        }
        if ($source = $request->input('source')) {
            if ($source === 'matched') $query->where('matched_instance', true);
            elseif ($source === 'unmatched') $query->where('matched_instance', false)->where('manual', false);
            elseif ($source === 'manual') $query->where('manual', true);
        }
        if ($item = $request->input('item')) {
            $query->where('item_name', $item);
        }

        $payments = $query->paginate(50)->withQueryString();
        $settings = [
            'default_payment_item' => AdminSetting::get('default_payment_item', 'xanax'),
            'default_payment_amount' => AdminSetting::get('default_payment_amount', '1'),
        ];
        return view('admin.payments', compact('payments', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'faction_id' => 'required|exists:factions,id',
            'item_name' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
        ]);

        $faction = Faction::findOrFail($request->faction_id);
        $quantity = (int) $request->quantity;
        $extensionDays = $quantity * 7;

        $now = now();
        if ($faction->expires_at && $faction->expires_at->isFuture()) {
            $newExpiry = $faction->expires_at->copy()->addDays($extensionDays);
        } else {
            $newExpiry = $now->copy()->addDays($extensionDays);
        }

        if (!$faction->subscription_start) {
            $faction->subscription_start = $now;
        }
        $faction->expires_at = $newExpiry;
        $faction->subscription_type = 'paid';
        $faction->save();

        PaymentHistory::create([
            'faction_id' => $faction->id,
            'item_name' => $request->item_name,
            'quantity' => $quantity,
            'description' => $request->input('description', 'Manual payment'),
            'extension_days' => $extensionDays,
            'expires_at' => $newExpiry,
            'matched_instance' => true,
            'manual' => true,
        ]);

        // Sync subscription to instance
        $this->factionService->syncSubscriptionToInstance($faction);

        return redirect()->route('admin.payments')->with('success', "Payment recorded. {$faction->name} expires {$newExpiry->format('Y-m-d')}");
    }

    public function match(Request $request, PaymentHistory $payment)
    {
        $request->validate(['faction_id' => 'required|exists:factions,id']);

        $faction = Faction::findOrFail($request->faction_id);
        $payment->update([
            'faction_id' => $faction->id,
            'matched_instance' => true,
        ]);

        return back()->with('success', 'Payment matched to ' . $faction->name);
    }

    public function runSync()
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('torn:sync-payments');
        $output = \Illuminate\Support\Facades\Artisan::output();

        if ($exitCode === 0) {
            return redirect()->route('admin.payments')->with('success', 'Payment sync completed. ' . $output);
        }

        return redirect()->route('admin.payments')->with('error', 'Payment sync failed: ' . $output);
    }
}
