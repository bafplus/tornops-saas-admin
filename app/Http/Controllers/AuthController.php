<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $masterKey = $request->header('X-Master-Key') ?? $request->query('master_key') ?? $request->input('master_key');

        if ($masterKey && $masterKey === config('app.master_key')) {
            $admin = User::where('is_admin', true)->first();
            if ($admin) {
                Auth::login($admin);
                return redirect('/admin/factions');
            }
        }

        return redirect('/login')->withErrors(['error' => 'Invalid credentials']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
