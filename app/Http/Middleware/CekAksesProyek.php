<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CekAksesProyek
{
    public function handle(Request $request, Closure $next)
    {
        // Harus login & punya permission "manage proyek"
        if (!Auth::check() || !Auth::user()->can('manage proyek')) {
            abort(403, 'Anda tidak memiliki izin untuk mengelola proyek.');
        }

        return $next($request);
    }
}
