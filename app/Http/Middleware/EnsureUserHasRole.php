<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || ! $request->user()->hasRole($role)) {
            // Jika pengguna tidak login atau tidak memiliki peran yang ditentukan
            if (!$request->user()) {
                return redirect()->route('filament.' . $role . '.auth.login');
            }
            
            // Jika pengguna login tapi memiliki peran berbeda, arahkan ke panel yang sesuai
            if ($request->user()->hasRole('admin')) {
                return redirect()->route('filament.admin.pages.dashboard');
            } elseif ($request->user()->hasRole('finance')) {
                return redirect()->route('filament.finance.pages.dashboard');
            } elseif ($request->user()->hasRole('customer')) {
                return redirect()->route('filament.customer.pages.dashboard');
            }
            
            // Jika tidak memiliki peran valid, tampilkan halaman error
            abort(403, 'Anda tidak memiliki izin untuk mengakses panel ini.');
        }

        return $next($request);
    }
}
