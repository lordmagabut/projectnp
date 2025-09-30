<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        // user yang sedang diakses (profil sendiri)
        $user = Auth::user();

        // log aktivitas user (paginate)
        $logs = ActivityLog::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        // last login (dari log)
        $lastLogin = ActivityLog::where('user_id', $user->id)
            ->where('event', 'login')
            ->latest()
            ->first();

        // info session saat ini (kalau sedang login)
        $ip = $request->headers->get('x-forwarded-for')
            ? trim(explode(',', $request->headers->get('x-forwarded-for'))[0])
            : $request->getClientIp();

        // "nama komputer" real dari browser itu tidak bisa didapat (security).
        // Kita coba reverse DNS; kalau gagal, kosongkan.
        $hostname = null;
        if ($ip) {
            try { $hostname = gethostbyaddr($ip); } catch (\Throwable $e) { $hostname = null; }
        }

        $ua = $request->userAgent();

        return view('profile.show', compact('user','logs','lastLogin','ip','hostname','ua'));
    }
}
