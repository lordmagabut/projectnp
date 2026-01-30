<?php

namespace App\Imports;

use App\Models\AhspHeader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AhspHeaderSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private RABImportContext $ctx) {}

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Skip empty rows
                if (!isset($row['kode_pekerjaan']) || trim((string)$row['kode_pekerjaan']) === '') {
                    continue;
                }

                $kode_pekerjaan = trim((string)$row['kode_pekerjaan']);
                $nama_pekerjaan = trim((string)($row['nama_pekerjaan'] ?? ''));
                $satuan         = trim((string)($row['satuan'] ?? '')) ?: 'LS';
                $kategori_id    = isset($row['kategori_id']) && !empty($row['kategori_id']) ? (int)$row['kategori_id'] : null;
                $catatan        = trim((string)($row['catatan'] ?? '')) ?: null;

                // Check if AHSP with same code exists
                $existing = AhspHeader::where('kode_pekerjaan', $kode_pekerjaan)->first();

                if ($existing) {
                    // Update existing
                    $existing->update([
                        'nama_pekerjaan' => $nama_pekerjaan,
                        'satuan'         => $satuan,
                        'kategori_id'    => $kategori_id,
                    ]);
                } else {
                    // Create new
                    AhspHeader::create([
                        'kode_pekerjaan'   => $kode_pekerjaan,
                        'nama_pekerjaan'   => $nama_pekerjaan,
                        'satuan'           => $satuan,
                        'kategori_id'      => $kategori_id,
                        'total_harga'      => 0,
                        'total_harga_pembulatan' => 0,
                    ]);
                }
            }
        });
    }
}
