<?php

use App\Models\AuditLog;

if (!function_exists('logAudit')) {
    function logAudit($aksi, $tabel, $id_tabel, $deskripsi = null)
    {
        \App\Models\AuditLog::create([
            'user_id' => auth()->id() ?? 0,
            'aksi' => $aksi,
            'tabel' => $tabel,
            'id_tabel' => $id_tabel,
            'deskripsi' => $deskripsi,
            'created_at' => now(),
        ]);
    }
}
