<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectGetLogout
{
    public function handle(Request $request, Closure $next)
    {
        // Check if this is a GET request to the logout route
        if ($request->isMethod('get') && str_contains($request->path(), 'logout')) {
            // If user is authenticated, log them out properly
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            
            // Redirect to the home page or admin panel login
            if (str_contains($request->path(), 'admin/logout')) {
                return redirect()->route('filament.admin.auth.login');
            }
            
            return redirect('/');
        }

        return $next($request);
    }
}
