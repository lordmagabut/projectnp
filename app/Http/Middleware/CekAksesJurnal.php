<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CekAksesJurnal
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->akses_jurnal != 1) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        return $next($request);
    }
}
