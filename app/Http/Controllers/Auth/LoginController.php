<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // If already logged in, bounce to dashboard
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $creds = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        // optional: remember me
        $remember = $request->boolean('remember');

        // throttle middleware recommendation: add 'throttle:login' alias in route if you want
        if (! Auth::attempt($creds, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        // Redirect by role
        $u = $request->user();
        if ($u->hasRole('admin'))   return redirect()->route('admin.dashboard');
        if ($u->hasRole('cashier')) return redirect()->route('cashier.pos');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
