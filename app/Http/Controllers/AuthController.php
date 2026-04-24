<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Check for master key support login FIRST (before validation)
        $masterKey = $request->header('X-Master-Key') ?? $request->query('master_key');
        if ($masterKey && $masterKey === config('app.master_key') && !empty($masterKey)) {
            $adminUser = User::where('is_admin', true)->first();
            if ($adminUser) {
                Auth::login($adminUser);
                $request->session()->regenerate();
                return redirect()->intended('/dashboard');
            }
        }

        $request->validate([
            'name' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $user = User::where('name', $request->name)->first();

        if (!$user) {
            return back()->withErrors([
                'name' => 'No account found with this username.',
            ]);
        }

        if ($user->isDisabled()) {
            return back()->withErrors([
                'name' => 'This account has been disabled.',
            ]);
        }

        if ($user->isInvited()) {
            return back()->withErrors([
                'name' => 'This account has not been activated. Use your invitation link.',
            ]);
        }

        $credentials = [
            'name' => $request->name,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            if (!$user->is_admin && empty($user->torn_api_key)) {
                return redirect('/settings')->with('warning', 'Please set your Torn API key to use the system features.');
            }
            
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'name' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}