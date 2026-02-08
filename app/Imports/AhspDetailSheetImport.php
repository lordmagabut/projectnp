<?php

namespace App\Imports;

use App\Models\AhspHeader;
use App\Models\AhspDetail;
use App\Models\HsdMaterial;
use App\Models\HsdUpah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AhspDetailSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private RABImportContext $ctx) {}

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            $ahspTotals = [];  // Track totals per AHSP
            $clearedAhsp = [];

            foreach ($rows as $row) {
                // Skip empty rows
                if (!isset($row['ahsp_kode']) || trim((string)$row['ahsp_kode']) === '') {
                    continue;
                }

                $ahsp_kode  = trim((string)$row['ahsp_kode']);
                $tipe       = trim((string)($row['tipe'] ?? '')) ?: 'material';  // default material
                $kode_item  = trim((string)($row['kode_item'] ?? ''));
                $koefisien  = RABImportContext::dec($row['koefisien'] ?? 1);

                if ($koefisien <= 0) {
                    continue;
                }

                // Find AHSP Header
                $ahsp = AhspHeader::where('kode_pekerjaan', $ahsp_kode)->first();

                if (!$ahsp) {
                    continue;
                }

                // Clear existing details once per AHSP (overwrite)
                if (!isset($clearedAhsp[$ahsp->id])) {
                    AhspDetail::where('ahsp_id', $ahsp->id)->delete();
                    $clearedAhsp[$ahsp->id] = true;
                }

                // Find HSD Material or Upah
                $harga_satuan = 0;
                $nama_item = '';
                $satuan = '';

                if ($tipe === 'material' && !empty($kode_item)) {
                    $hsd = HsdMaterial::where('kode', $kode_item)->first();
                    if ($hsd) {
                        $harga_satuan = (float)$hsd->harga_satuan;
                        $nama_item = $hsd->nama;
                        $satuan = $hsd->satuan ?? '';
                    }
                } elseif ($tipe === 'upah' && !empty($kode_item)) {
                    $hsd = HsdUpah::where('kode', $kode_item)->first();
                    if ($hsd) {
                        $harga_satuan = (float)$hsd->harga_satuan;
                        $nama_item = $hsd->jenis_pekerja;
                        $satuan = $hsd->satuan ?? '';
                    }
                }

                $subtotal = $koefisien * $harga_satuan;

                // Create new detail
                $referensi_id = null;
                if ($tipe === 'material' && !empty($kode_item)) {
                    $hsd = HsdMaterial::where('kode', $kode_item)->first();
                    if ($hsd) $referensi_id = $hsd->id;
                } elseif ($tipe === 'upah' && !empty($kode_item)) {
                    $hsd = HsdUpah::where('kode', $kode_item)->first();
                    if ($hsd) $referensi_id = $hsd->id;
                }

                AhspDetail::create([
                    'ahsp_id'        => $ahsp->id,
                    'tipe'           => $tipe,
                    'referensi_id'   => $referensi_id,
                    'koefisien'      => $koefisien,
                    'harga_satuan'   => $harga_satuan,
                    'subtotal'       => $subtotal,
                ]);

                // Track totals
                if (!isset($ahspTotals[$ahsp->id])) {
                    $ahspTotals[$ahsp->id] = ['material' => 0, 'upah' => 0];
                }
                $ahspTotals[$ahsp->id][$tipe] += $subtotal;
            }

            // Update AHSP Header dengan total material dan upah
            foreach ($ahspTotals as $ahsp_id => $totals) {
                $ahsp = AhspHeader::find($ahsp_id);
                if ($ahsp) {
                    $total_material = (float)$totals['material'];
                    $total_upah = (float)$totals['upah'];
                    $total_harga = $total_material + $total_upah;
                    $total_harga_pembulatan = (int)ceil($total_harga / 1000) * 1000;

                    $ahsp->update([
                        'total_material'         => $total_material,
                        'total_upah'             => $total_upah,
                        'total_harga'            => $total_harga,
                        'total_harga_pembulatan' => $total_harga_pembulatan,
                    ]);
                }
            }
        });
    }
}
