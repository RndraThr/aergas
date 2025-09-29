<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActiveAndHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Dapatkan user yang sedang login
        $user = Auth::user();

        // Gabungkan semua syarat dalam satu pengecekan
        // 1. Apakah user sudah login? (Auth::check())
        // 2. Apakah status user TIDAK aktif? (! $user->is_active)
        // 3. Apakah user TIDAK punya role sama sekali? ($user->userRoles()->doesntExist())
        if (!Auth::check() || !$user->is_active || $user->userRoles()->doesntExist()) {
            
            // Jika salah satu syarat tidak terpenuhi, paksa logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Arahkan kembali ke halaman login dengan pesan error
            return redirect('/login')->with('error', 'Akun Anda tidak aktif atau tidak memiliki hak akses yang valid.');
        }

        // Jika semua syarat terpenuhi, izinkan akses ke halaman berikutnya
        return $next($request);
    }
}