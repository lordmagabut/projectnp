<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CekAksesPemberiKerja
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->akses_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki akses ke menu pemberi kerja.');
        }

        return $next($request);
    }
}
