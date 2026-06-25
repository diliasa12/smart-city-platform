<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    
    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        
        if (Auth::attempt($credentials) && Auth::user()->role === 'admin') {
            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        
        Auth::logout();

        return back()->withErrors([
            'email' => 'Email atau password salah, atau akun ini bukan admin.',
        ])->onlyInput('email');
    }

    
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}