<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Setting;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $setting = Setting::first();
        // dd($setting);
        return view('auth.login', compact('setting'));
    }

    /**
     * Handle an incoming authentication request.
     */
    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();

    //     $request->session()->regenerate();

    //     return redirect()->intended(route('dashboard', absolute: false));
    // }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Check if user is admin
        // if (Auth::user() && Auth::user()->is_admin == 1) {
        //     return redirect()->intended(route('admin.dashboard', absolute: false))
        //         ->with('success', 'Login successful! Welcome to admin panel.');
        // }

        // // Logout non-admin users
        // Auth::logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        // // Redirect back with error message using SweetAlert
        // return redirect()->route('login')->with('error', 'You are not authorized to access the admin panel.');
        return redirect()->route('admin.dashboard')->with('success', 'Login successful!');

    }



    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'You have been logged out successfully.');
    }
}
