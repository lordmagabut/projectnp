<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogMiddleware
{
    /**
     * Pakai: ->middleware('activity:Create PO')
     */
    public function handle(Request $request, Closure $next, string $event = 'activity')
    {
        $response = $next($request);

        ActivityLog::create([
            'user_id'    => Auth::id(),
            'event'      => $event,
            'description'=> sprintf('[%s] %s', $request->method(), $request->path()),
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->userAgent(),
            'device_name'=> null, // bisa isi dengan helper dari subscriber jika mau
        ]);

        return $response;
    }
}
