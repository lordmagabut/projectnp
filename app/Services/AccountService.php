<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\Perusahaan;
use App\Models\Coa;

class AccountService
{
    /**
     * Get Hutang Usaha COA ID
     * Priority: Perusahaan setting > Global mapping > Hardcoded default
     */
    public static function getHutangUsaha(?int $perusahaanId = null): int
    {
        // Try perusahaan-specific
        if ($perusahaanId) {
            $perusahaan = Perusahaan::find($perusahaanId);
            if ($perusahaan?->coa_hutang_usaha_id) {
                return $perusahaan->coa_hutang_usaha_id;
            }
        }

        // Try global mapping
        $coaId = AccountMapping::getCoaId('hutang_usaha');
        if ($coaId) {
            return $coaId;
        }

        // Fallback: find by account number or name
        $coa = Coa::where('no_akun', '2-110')
            ->orWhere('nama_akun', 'like', '%Hutang Usaha%')
            ->first();

        return $coa?->id ?? 158; // Last resort hardcoded
    }

    /**
     * Get PPN Masukan COA ID
     */
    public static function getPpnMasukan(?int $perusahaanId = null): int
    {
        if ($perusahaanId) {
            $perusahaan = Perusahaan::find($perusahaanId);
            if ($perusahaan?->coa_ppn_masukan_id) {
                return $perusahaan->coa_ppn_masukan_id;
            }
        }

        $coaId = AccountMapping::getCoaId('ppn_masukan');
        if ($coaId) {
            return $coaId;
        }

        $coa = Coa::where('no_akun', '1-170')
            ->orWhere('nama_akun', 'like', '%PPN Masukan%')
            ->first();

        return $coa?->id ?? null;
    }

    /**
     * Get Kas COA ID (for default cash account)
     */
    public static function getKas(?int $perusahaanId = null): int
    {
        if ($perusahaanId) {
            $perusahaan = Perusahaan::find($perusahaanId);
            if ($perusahaan?->coa_kas_id) {
                return $perusahaan->coa_kas_id;
            }
        }

        $coaId = AccountMapping::getCoaId('kas');
        if ($coaId) {
            return $coaId;
        }

        $coa = Coa::where('no_akun', '1-111')
            ->orWhere('nama_akun', 'like', '%Kas%')
            ->first();

        return $coa?->id ?? null;
    }

    /**
     * Get all configured account mappings
     */
    public static function getAllMappings(): array
    {
        return [
            'hutang_usaha' => self::getHutangUsaha(),
            'ppn_masukan' => self::getPpnMasukan(),
            'kas' => self::getKas(),
        ];
    }
}
