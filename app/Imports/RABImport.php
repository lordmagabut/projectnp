<?php

namespace App\Imports;

use App\Models\RabHeader;
use App\Models\RabDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RABImport implements ToCollection, WithHeadingRow
{
    protected $proyek_id;
    protected $headerMap = [];
    protected $headerValues = [];
    protected $totalProyek = 0;
    protected $rowsDetail = [];

    public function __construct($proyek_id)
    {
        $this->proyek_id = $proyek_id;
    }

    public function collection(Collection $rows)
    {
        // Step 1: Siapkan header dan kumpulkan detail
        foreach ($rows as $row) {
            $kode = trim($row['wbs']);
            if (!$kode) continue;

            $kodeParts = explode('.', $kode);
            $level = count($kodeParts);
            $kode_sort = implode('.', array_map(fn($k) => str_pad($k, 4, '0', STR_PAD_LEFT), $kodeParts));

            $volume = floatval(str_replace(',', '.', $row['volume']));
            $harga = floatval(str_replace(',', '.', $row['harga_satuan']));
            $total = $volume * $harga;

            // Level 1 & 2 → Header
            if ($level <= 2) {
                $header = RabHeader::create([
                    'proyek_id' => $this->proyek_id,
                    'kode' => $kode,
                    'kode_sort' => $kode_sort,
                    'deskripsi' => $row['uraian_pekerjaan'],
                    'nilai' => 0,
                    'bobot' => 0,
                ]);
                $this->headerMap[$kode] = $header->id;
                $this->headerValues[$kode] = 0;
            }

            // Level 3+ → Kumpulkan detail, tapi belum insert
            if ($level >= 3) {
                $parentKode = implode('.', array_slice($kodeParts, 0, 2));
                $header_id = $this->headerMap[$parentKode] ?? null;

                // Tambah nilai ke header
                $this->headerValues[$parentKode] = ($this->headerValues[$parentKode] ?? 0) + $total;
                $this->totalProyek += $total;

                $this->rowsDetail[] = [
                    'kode' => $kode,
                    'kode_sort' => $kode_sort,
                    'header_id' => $header_id,
                    'uraian' => trim($row['uraian_pekerjaan'] ?? ''),
                    'area' => trim($row['area'] ?? '') ?: null,
                    'spesifikasi' => $row['spesifikasi'] ?? null,
                    'satuan' => $row['satuan'] ?? null,
                    'volume' => $volume,
                    'harga' => $harga,
                    'total' => $total,
                    'raw' => $row,
                ];
            }
        }

        // Step 2: Hitung nilai dan bobot header (level 1 dan 2)
        foreach ($this->headerMap as $kodeInduk => $idInduk) {
            $childHeaders = array_filter(array_keys($this->headerValues), fn($k) => str_starts_with($k, $kodeInduk . '.') && substr_count($k, '.') == 1);

            if (count($childHeaders)) {
                $total = 0;
                foreach ($childHeaders as $childKode) {
                    $total += $this->headerValues[$childKode] ?? 0;
                }

                $header = RabHeader::find($idInduk);
                $header->nilai = $total;
                $header->bobot = $this->totalProyek > 0 ? ($total / $this->totalProyek) * 100 : 0;
                $header->save();

                $this->headerValues[$kodeInduk] = $total;
            }
        }

        // Hitung ulang bobot untuk header level 2
        foreach ($this->headerValues as $kode => $nilai) {
            if (isset($this->headerMap[$kode])) {
                $header = RabHeader::find($this->headerMap[$kode]);
                $header->nilai = $nilai;
                $header->bobot = $this->totalProyek > 0 ? ($nilai / $this->totalProyek) * 100 : 0;
                $header->save();
            }
        }

        // Step 3: Hitung bobot detail & koreksi agar total = 100
        $details = [];
        $totalBobot = 0;

        foreach ($this->rowsDetail as $d) {
            $bobot = $this->totalProyek > 0
                ? round(($d['total'] / $this->totalProyek) * 100, 4)
                : 0;

            $totalBobot += $bobot;
            $d['bobot'] = $bobot;
            $details[] = $d;
        }

        $selisih = round(100 - $totalBobot, 4);

        // Tambahkan selisih ke item terbesar
        $maxIndex = 0;
        $maxTotal = 0;
        foreach ($details as $i => $d) {
            if ($d['total'] > $maxTotal) {
                $maxTotal = $d['total'];
                $maxIndex = $i;
            }
        }
        $details[$maxIndex]['bobot'] += $selisih;

        // Step 4: Simpan ke DB
        foreach ($details as $d) {
            RabDetail::create([
                'proyek_id' => $this->proyek_id,
                'rab_header_id' => $d['header_id'],
                'kode' => $d['kode'],
                'kode_sort' => $d['kode_sort'],
                'deskripsi' => $d['uraian'],
                'area' => $d['area'],
                'spesifikasi' => $d['spesifikasi'],
                'satuan' => $d['satuan'],
                'volume' => $d['volume'],
                'harga_satuan' => $d['harga'],
                'total' => $d['total'],
                'bobot' => $d['bobot'],
            ]);
        }
    }
}
