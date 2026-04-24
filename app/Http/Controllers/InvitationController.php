<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    public function showInviteForm(string $token)
    {
        $user = User::where('invitation_token', $token)->first();

        if (!$user) {
            return redirect('/login')->with('error', 'Invalid or expired invitation.');
        }

        if ($user->isActive()) {
            return redirect('/login')->with('error', 'This invitation has already been used.');
        }

        if ($user->isDisabled()) {
            return redirect('/login')->with('error', 'This account has been disabled.');
        }

        return view('auth.invite', ['token' => $token, 'user' => $user]);
    }

    public function acceptInvite(Request $request, string $token)
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::where('invitation_token', $token)->first();

        if (!$user) {
            return back()->with('error', 'Invalid or expired invitation.');
        }

        if ($user->isActive()) {
            return redirect('/login')->with('error', 'This invitation has already been used.');
        }

        if ($user->isDisabled()) {
            return back()->with('error', 'This account has been disabled.');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'status' => User::STATUS_ACTIVE,
            'invitation_token' => null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/dashboard')->with('status', 'Welcome! Your account has been set up.');
    }
}