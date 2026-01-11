<?php

namespace App\Services;

use App\Models\Bast;
use App\Models\Proyek;
use App\Models\SertifikatPembayaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BastService
{
    /**
     * Generate BAST 1 dan BAST 2 otomatis ketika sertifikat mencapai 100%.
     * - BAST 1: tanggal sama dengan sertifikat 100%
     * - BAST 2: tanggal = tanggal BAST 1 + durasi retensi (hari)
     */
    public static function ensureForSertifikat(SertifikatPembayaran $sp): void
    {
        $proyek = optional($sp->bapp)->proyek;
        if (!$proyek) return;

        $progress = (float) ($sp->persen_progress ?? 0);
        if ($progress < 100) return;

        // Cegah duplikasi BAST 1
        $existingBast1 = Bast::where('proyek_id', $proyek->id)
            ->where('jenis_bast', 'bast_1')
            ->first();
        if ($existingBast1) return;

        $retensiDays   = self::resolveRetensiDays($proyek);
        $retensiPersen = self::resolveRetensiPercent($proyek, $sp);
        $tanggalBast1  = $sp->tanggal ?? now();
        $tanggalDue    = Carbon::parse($tanggalBast1)->addDays($retensiDays);

        $retensiNominal = self::resolveRetensiNominal($proyek, $sp, $retensiPersen);

        $bast1 = Bast::create([
            'proyek_id'                 => $proyek->id,
            'sertifikat_pembayaran_id'  => $sp->id,
            'parent_bast_id'            => null,
            'nomor'                     => self::generateNomor($proyek, $sp, 'I'),
            'jenis_bast'                => 'bast_1',
            'status'                    => 'draft',
            'tanggal_bast'              => $tanggalBast1,
            'tanggal_jatuh_tempo_retensi' => $tanggalDue,
            'durasi_retensi_hari'       => $retensiDays,
            'persen_retensi'            => $retensiPersen,
            'nilai_retensi'             => $retensiNominal,
        ]);

        // BAST 2 otomatis terbuat, status scheduled
        Bast::create([
            'proyek_id'                 => $proyek->id,
            'sertifikat_pembayaran_id'  => $sp->id,
            'parent_bast_id'            => $bast1->id,
            'nomor'                     => self::generateNomor($proyek, $sp, 'II'),
            'jenis_bast'                => 'bast_2',
            'status'                    => 'scheduled',
            'tanggal_bast'              => $tanggalDue,
            'tanggal_jatuh_tempo_retensi' => $tanggalDue,
            'durasi_retensi_hari'       => $retensiDays,
            'persen_retensi'            => $retensiPersen,
            'nilai_retensi'             => $retensiNominal,
        ]);
    }

    /**
     * Kirim notifikasi H-14 (placeholder: log entry).
     */
    public static function dispatchH14Reminders(): void
    {
        $targetDate = Carbon::now()->addDays(14)->toDateString();

        $targets = Bast::where('jenis_bast', 'bast_2')
            ->whereIn('status', ['draft', 'scheduled'])
            ->whereDate('tanggal_jatuh_tempo_retensi', $targetDate)
            ->where('notifikasi_h14_sent', false)
            ->get();

        foreach ($targets as $bast) {
            Log::info('Reminder H-14 BAST 2', [
                'bast_id'   => $bast->id,
                'proyek_id' => $bast->proyek_id,
                'nomor'     => $bast->nomor,
                'due_date'  => optional($bast->tanggal_jatuh_tempo_retensi)->toDateString(),
            ]);

            $bast->update(['notifikasi_h14_sent' => true]);
        }
    }

    protected static function resolveRetensiDays(Proyek $proyek): int
    {
        // Prioritas: kolom durasi_proyek (permintaan user), fallback durasi_retensi, lalu default 90
        $fromDurasiProyek = (int) ($proyek->durasi_proyek ?? 0);
        if ($fromDurasiProyek > 0) return $fromDurasiProyek;

        $fromRetensi = (int) ($proyek->durasi_retensi ?? 0);
        if ($fromRetensi > 0) return $fromRetensi;

        return 90; // default
    }

    protected static function resolveRetensiPercent(Proyek $proyek, SertifikatPembayaran $sp): float
    {
        $fromProyek = (float) ($proyek->persen_retensi ?? 0);
        if ($fromProyek > 0) return $fromProyek;

        $fromSp = (float) ($sp->retensi_persen ?? 0);
        if ($fromSp > 0) return $fromSp;

        return 5.0; // default
    }

    protected static function resolveRetensiNominal(Proyek $proyek, SertifikatPembayaran $sp, float $retensiPersen): float
    {
        // Akumulasi retensi dari semua sertifikat pembayaran dengan penawaran_id yang sama
        $penawaranId = $sp->penawaran_id;
        if ($penawaranId) {
            $totalRetensi = \DB::table('sertifikat_pembayaran')
                ->where('penawaran_id', $penawaranId)
                ->sum('retensi_nilai');
            
            if ($totalRetensi > 0) {
                return round((float)$totalRetensi, 2);
            }
        }

        // Fallback: hitung dari nilai kontrak × persen retensi
        $nilaiKontrak = (float) ($proyek->nilai_kontrak ?? $proyek->nilai_spk ?? 0);
        if ($nilaiKontrak > 0) {
            return round($nilaiKontrak * $retensiPersen / 100, 2);
        }

        // Fallback terakhir: dari sertifikat saat ini
        return (float) ($sp->retensi_nilai ?? 0);
    }

    protected static function generateNomor(Proyek $proyek, SertifikatPembayaran $sp, string $roman): string
    {
        // Format: BAST-{roman}/{YYMM}/{alias_perusahaan}/{penawaran_id}/nnnn
        // Contoh: BAST-I/2601/RENOVASI/50/0001
        $perusahaan = $proyek->perusahaan;
        $alias = Str::upper($perusahaan?->alias ?? 'PRK');
        $penawaranId = $sp->penawaran_id ?? 0;
        $prefix = 'BAST-' . $roman . '/' . date('ym') . '/' . $alias . '/' . $penawaranId . '/';
        
        $latest = Bast::where('nomor', 'like', $prefix.'%')
            ->latest('id')
            ->value('nomor');

        $seq = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s%04d', $prefix, $seq);
    }
}
