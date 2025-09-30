<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;

class AuthEventSubscriber
{
    public function __construct(private Request $request) {}

    protected function ip(): ?string {
        // ambil IP asli kalau dibalik proxy
        $xff = $this->request->headers->get('x-forwarded-for');
        if ($xff) {
            return trim(explode(',', $xff)[0]);
        }
        return $this->request->getClientIp();
    }

    protected function deviceName(?string $ua): ?string {
        $ua = $ua ?? '';
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        // OS very-light detect
        if (preg_match('/Windows NT 10\.0/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT 6\./i', $ua)) $os = 'Windows 7/8';
        elseif (preg_match('/Mac OS X/i', $ua)) $os = 'macOS';
        elseif (preg_match('/Android/i', $ua)) $os = 'Android';
        elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) $os = 'iOS';
        elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';

        // Browser very-light detect
        if (preg_match('/Edg\//i', $ua)) $browser = 'Edge';
        elseif (preg_match('/Chrome\//i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/Safari\//i', $ua) && !preg_match('/Chrome\//i', $ua)) $browser = 'Safari';
        elseif (preg_match('/Firefox\//i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/OPR\//i', $ua)) $browser = 'Opera';

        return "{$os} · {$browser}";
    }

    public function onLogin(Login $event): void {
        ActivityLog::create([
            'user_id'    => $event->user->id ?? null,
            'event'      => 'login',
            'description'=> 'User login berhasil',
            'ip_address' => $this->ip(),
            'user_agent' => $this->request->userAgent(),
            'device_name'=> $this->deviceName($this->request->userAgent()),
        ]);
    }

    public function onLogout(Logout $event): void {
        ActivityLog::create([
            'user_id'    => $event->user->id ?? null,
            'event'      => 'logout',
            'description'=> 'User logout',
            'ip_address' => $this->ip(),
            'user_agent' => $this->request->userAgent(),
            'device_name'=> $this->deviceName($this->request->userAgent()),
        ]);
    }

    public function onFailed(Failed $event): void {
        ActivityLog::create([
            'user_id'    => $event->user?->id, // bisa null
            'event'      => 'login_failed',
            'description'=> 'User login gagal',
            'ip_address' => $this->ip(),
            'user_agent' => $this->request->userAgent(),
            'device_name'=> $this->deviceName($this->request->userAgent()),
        ]);
    }

    public function subscribe(Dispatcher $events): void {
        $events->listen(Login::class, [self::class, 'onLogin']);
        $events->listen(Logout::class, [self::class, 'onLogout']);
        $events->listen(Failed::class, [self::class, 'onFailed']);
    }
}
