<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'torn_api_key' => AdminSetting::get('torn_api_key'),
            'default_payment_item' => AdminSetting::get('default_payment_item', 'xanax'),
            'default_payment_amount' => AdminSetting::get('default_payment_amount', '1'),
        ];
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'torn_api_key' => 'nullable|string',
            'default_payment_item' => 'required|string|max:50',
            'default_payment_amount' => 'required|integer|min:1',
        ]);

        AdminSetting::set('torn_api_key', $request->input('torn_api_key'));
        AdminSetting::set('default_payment_item', $request->input('default_payment_item'));
        AdminSetting::set('default_payment_amount', (string) $request->input('default_payment_amount'));

        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully');
    }
}
