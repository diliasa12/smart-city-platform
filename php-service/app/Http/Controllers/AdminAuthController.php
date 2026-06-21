<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    /**
     * Menampilkan formulir login khusus untuk akun administrator.
     * (GET /admin/login, Guest)
     */
    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    /**
     * Memproses verifikasi kredensial admin dan membuat sesi session baru.
     * (POST /admin/login, Guest)
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Cek login dengan role harus 'admin'
        if (Auth::attempt($credentials) && Auth::user()->role === 'admin') {
            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        // Kalau role bukan admin, paksa logout lagi
        Auth::logout();

        return back()->withErrors([
            'email' => 'Email atau password salah, atau akun ini bukan admin.',
        ])->onlyInput('email');
    }

    /**
     * Logout admin dan hapus session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}