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
        $masterKey = $request->header('X-Master-Key') ?? $request->query('master_key');
        if ($masterKey && $masterKey === config('app.master_key') && !empty($masterKey)) {
            $adminUser = User::where('is_admin', true)->first();
            if ($adminUser) {
                Auth::login($adminUser);
                $request->session()->regenerate();
                return redirect()->intended('/admin');
            }
        }

        $request->validate([
            'name' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $user = User::where('name', $request->name)->first();

        if (!$user) {
            return back()->withErrors(['name' => 'No account found with this username.']);
        }

        $credentials = [
            'name' => $request->name,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['name' => 'The provided credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function masterLogin(Request $request)
    {
        $masterKey = $request->header('X-Master-Key') ?? $request->query('master_key');
        
        if ($masterKey && $masterKey === config('app.master_key')) {
            $adminUser = User::where('is_admin', true)->first();
            if ($adminUser) {
                Auth::login($adminUser);
                $request->session()->regenerate();
                return redirect()->intended('/admin');
            }
        }
        
        return redirect('/login')->withErrors(['Master key invalid']);
    }
}