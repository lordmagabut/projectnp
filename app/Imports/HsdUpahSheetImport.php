<?php

namespace App\Imports;

use App\Models\HsdUpah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HsdUpahSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private RABImportContext $ctx) {}

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Skip empty rows
                if (!isset($row['kode_item']) || trim((string)$row['kode_item']) === '') {
                    continue;
                }

                $kode_item    = trim((string)$row['kode_item']);
                $nama_item    = trim((string)($row['nama_item'] ?? ''));
                $satuan       = trim((string)($row['satuan'] ?? '')) ?: null;
                $harga_satuan = RABImportContext::dec($row['harga_satuan'] ?? 0);

                if ($harga_satuan <= 0) {
                    continue;
                }

                // Check if upah with same kode exists
                $existing = HsdUpah::where('kode', $kode_item)->first();

                if ($existing) {
                    // Update existing
                    $existing->update([
                        'jenis_pekerja' => $nama_item,
                        'satuan'        => $satuan ?? $existing->satuan,
                        'harga_satuan'  => $harga_satuan,
                    ]);
                } else {
                    // Create new
                    HsdUpah::create([
                        'kode'          => $kode_item,
                        'jenis_pekerja' => $nama_item,
                        'satuan'        => $satuan,
                        'harga_satuan'  => $harga_satuan,
                    ]);
                }
            }
        });
    }
}
