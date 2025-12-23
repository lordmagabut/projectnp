<?php

namespace App\Imports;

use App\Models\RabHeader;
use App\Models\RabDetail;
use App\Models\AhspHeader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RABImport implements WithMultipleSheets
{
    protected $proyek_id;

    public function __construct($proyek_id)
    {
        $this->proyek_id = $proyek_id;
    }

    public function sheets(): array
    {
        $ctx = new RABImportContext($this->proyek_id);
        return [
            'RAB_Header' => new RABHeaderSheetImport($ctx),
            'RAB_Detail' => new RABDetailSheetImport($ctx),
            0            => new LegacySingleSheetImport($ctx), // fallback template lama
        ];
    }
}

/* =============== Shared Context =============== */
class RABImportContext
{
    public int $proyek_id;
    public array $headerMap = [];        // map[kode] = id
    public array $pendingParents = [];   // [[childId, parent_kode], ...]
    public array $headerTotals = [];     // map[header_id] = total

    public function __construct(int $proyek_id)
    {
        $this->proyek_id = $proyek_id;
    }

    public static function kodeSort(string $kode): string
    {
        $parts = explode('.', trim($kode));
        $pad = array_map(fn($p) => str_pad($p, 4, '0', STR_PAD_LEFT), $parts);
        return implode('.', $pad);
    }

    public static function dec($v): float
    {
        if ($v === null || $v === '') return 0.0;
        $s = trim((string)$v);
        $s = str_replace([' ', "\xC2\xA0"], '', $s);
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }
        return is_numeric($s) ? (float)$s : 0.0;
    }
}

/* =============== Sheet RAB_Header (template baru) =============== */
class RABHeaderSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private RABImportContext $ctx) {}

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            // 1) Create header tanpa parent dulu
            foreach ($rows as $row) {
                if (!isset($row['kode']) || trim((string)$row['kode']) === '') continue;

                $kode        = trim((string)$row['kode']);
                $deskripsi   = trim((string)($row['deskripsi'] ?? ''));
                $kategori_id = isset($row['kategori_id']) && $row['kategori_id'] !== '' ? (int)$row['kategori_id'] : null;
                $parent_kode = trim((string)($row['parent_kode'] ?? ''));

                $h = RabHeader::create([
                    'proyek_id'   => $this->ctx->proyek_id,
                    'kategori_id' => $kategori_id,
                    'parent_id'   => null,
                    'kode'        => $kode,
                    'kode_sort'   => RABImportContext::kodeSort($kode),
                    'deskripsi'   => $deskripsi,
                    'nilai'       => 0,
                    'bobot'       => 0,
                ]);

                $this->ctx->headerMap[$kode] = $h->id;
                if ($parent_kode !== '') {
                    $this->ctx->pendingParents[] = [$h->id, $parent_kode];
                }
            }

            // 2) Set parent + wariskan kategori bila anak kosong
            foreach ($this->ctx->pendingParents as [$childId, $parent_kode]) {
                if ($parent_kode !== '' && isset($this->ctx->headerMap[$parent_kode])) {
                    $parentId = $this->ctx->headerMap[$parent_kode];
                    RabHeader::whereKey($childId)->update(['parent_id' => $parentId]);

                    $child  = RabHeader::find($childId);
                    $parent = RabHeader::find($parentId);
                    if ($child && $parent && is_null($child->kategori_id) && !is_null($parent->kategori_id)) {
                        $child->kategori_id = $parent->kategori_id;
                        $child->save();
                    }
                }
            }
        });
    }
}

/* =============== Sheet RAB_Detail (template baru) =============== */
class RABDetailSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private RABImportContext $ctx) {}

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            foreach ($rows as $row) {
                if (!isset($row['header_kode']) || trim((string)$row['header_kode']) === '') continue;

                $header_kode = trim((string)$row['header_kode']);
                $header_id   = $this->ctx->headerMap[$header_kode] ?? null;
                if (!$header_id) continue;

                $kode        = trim((string)($row['kode'] ?? ''));
                $deskripsi   = trim((string)($row['deskripsi'] ?? ''));
                $area        = trim((string)($row['area'] ?? '')) ?: null;
                $spesifikasi = trim((string)($row['spesifikasi'] ?? '')) ?: null;
                $satuan      = trim((string)($row['satuan'] ?? '')) ?: null;

                $volume         = RABImportContext::dec($row['volume'] ?? 0);
                $harga_material = RABImportContext::dec($row['harga_material'] ?? 0);
                $harga_upah     = RABImportContext::dec($row['harga_upah'] ?? 0);
                $harga_satuan   = RABImportContext::dec($row['harga_satuan'] ?? 0);

                $ahsp_id   = isset($row['ahsp_id']) && $row['ahsp_id'] !== '' ? (int)$row['ahsp_id'] : null;
                $ahsp_kode = trim((string)($row['ahsp_kode'] ?? ''));

                // fallback ke AHSP bila semua harga kosong (atau bila AHSP tersedia dan ingin mengisi harga)
                if (($harga_material + $harga_upah + $harga_satuan) == 0 && ($ahsp_id || $ahsp_kode)) {
                    $q = AhspHeader::query();
                    if ($ahsp_id)   $q->where('id', $ahsp_id);
                    if ($ahsp_kode) $q->orWhere('kode_pekerjaan', $ahsp_kode);
                    if ($ahsp = $q->first()) {
                        $satuan = $satuan ?: $ahsp->satuan;
                        // Isi harga material & upah dari AHSP jika tersedia
                        $harga_material = (float)($ahsp->total_material ?? 0);
                        $harga_upah     = (float)($ahsp->total_upah ?? 0);
                        // Harga satuan berasal dari total AHSP jika ada, fallback ke penjumlahan material+upah
                        $harga_satuan = (float)($ahsp->total_harga_pembulatan ?? $ahsp->total_harga ?? ($harga_material + $harga_upah));
                    }
                }

                if ($harga_satuan == 0) {
                    $harga_satuan = $harga_material + $harga_upah;
                }

                $total_material = RABImportContext::dec($row['total_material'] ?? 0);
                $total_upah     = RABImportContext::dec($row['total_upah'] ?? 0);
                $total          = RABImportContext::dec($row['total'] ?? 0);

                if ($total == 0) {
                    $total_material = $total_material ?: ($harga_material * $volume);
                    $total_upah     = $total_upah     ?: ($harga_upah * $volume);
                    $total          = $total ?: ($harga_satuan * $volume);
                }

                if ($kode === '') {
                    $existing = RabDetail::where('rab_header_id', $header_id)->count();
                    $kode     = $header_kode . '.' . ($existing + 1);
                }

                RabDetail::create([
                    'proyek_id'      => $this->ctx->proyek_id,
                    'rab_header_id'  => $header_id,
                    'ahsp_id'        => $ahsp_id ?: null,
                    'kode'           => $kode,
                    'kode_sort'      => RABImportContext::kodeSort($kode),
                    'deskripsi'      => $deskripsi,
                    'area'           => $area,
                    'spesifikasi'    => $spesifikasi,
                    'satuan'         => $satuan,
                    'volume'         => $volume,
                    'harga_material' => $harga_material,
                    'harga_upah'     => $harga_upah,
                    'harga_satuan'   => $harga_satuan,
                    'total_material' => $total_material,
                    'total_upah'     => $total_upah,
                    'total'          => $total,
                    'bobot'          => 0,
                ]);

                $this->ctx->headerTotals[$header_id] = ($this->ctx->headerTotals[$header_id] ?? 0) + $total;
            }

            $this->updateAllHeaderTotals();
        });
    }

    private function updateAllHeaderTotals(): void
    {
        $headers = RabHeader::where('proyek_id', $this->ctx->proyek_id)
            ->orderBy('kode_sort')->get()->keyBy('id');

        foreach ($headers as $h) {
            $h->nilai = (float)($this->ctx->headerTotals[$h->id] ?? 0);
        }
        $ordered = $headers->sortByDesc(fn($h) => substr_count($h->kode, '.'));
        foreach ($ordered as $h) {
            if ($h->parent_id && isset($headers[$h->parent_id])) {
                $headers[$h->parent_id]->nilai += $h->nilai;
            }
        }
        foreach ($headers as $h) $h->save();
    }
}

/* =============== Template lama (1 sheet) =============== */
class LegacySingleSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private RABImportContext $ctx) {}

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            foreach ($rows as $row) {
                if (!isset($row['wbs']) || trim((string)$row['wbs']) === '') continue;

                $kode   = trim((string)$row['wbs']);
                $uraian = trim((string)($row['uraian_pekerjaan'] ?? ''));
                $area   = trim((string)($row['area'] ?? '')) ?: null;
                $spes   = trim((string)($row['spesifikasi'] ?? '')) ?: null;
                $satuan = trim((string)($row['satuan'] ?? '')) ?: null;

                $volume         = RABImportContext::dec($row['volume'] ?? 0);
                $harga_satuan   = RABImportContext::dec($row['harga_satuan'] ?? 0);
                // baca jika template lama juga menyediakan split (opsional)
                $harga_material = RABImportContext::dec($row['harga_material'] ?? 0);
                $harga_upah     = RABImportContext::dec($row['harga_upah'] ?? 0);

                if ($harga_satuan == 0) $harga_satuan = $harga_material + $harga_upah;

                $parts     = explode('.', $kode);
                $level     = count($parts);
                $kode_sort = RABImportContext::kodeSort($kode);

                if ($level <= 2) {
                    $kategori_id = is_numeric($parts[0]) ? (int)$parts[0] : null;
                    $parent_kode = $level == 2 ? $parts[0] : null;

                    $h = RabHeader::create([
                        'proyek_id'   => $this->ctx->proyek_id,
                        'kategori_id' => $kategori_id,
                        'parent_id'   => null,
                        'kode'        => $kode,
                        'kode_sort'   => $kode_sort,
                        'deskripsi'   => $uraian,
                        'nilai'       => 0,
                        'bobot'       => 0,
                    ]);

                    $this->ctx->headerMap[$kode] = $h->id;
                    if ($parent_kode) $this->ctx->pendingParents[] = [$h->id, $parent_kode];

                } else {
                    $parentKode = implode('.', array_slice($parts, 0, 2));
                    $header_id  = $this->ctx->headerMap[$parentKode] ?? null;
                    if (!$header_id) continue;

                    $total_material = $harga_material * $volume;
                    $total_upah     = $harga_upah * $volume;
                    $total          = $harga_satuan * $volume;

                    RabDetail::create([
                        'proyek_id'      => $this->ctx->proyek_id,
                        'rab_header_id'  => $header_id,
                        'kode'           => $kode,
                        'kode_sort'      => $kode_sort,
                        'deskripsi'      => $uraian,
                        'area'           => $area,
                        'spesifikasi'    => $spes,
                        'satuan'         => $satuan,
                        'volume'         => $volume,
                        'harga_material' => $harga_material,
                        'harga_upah'     => $harga_upah,
                        'harga_satuan'   => $harga_satuan,
                        'total_material' => $total_material,
                        'total_upah'     => $total_upah,
                        'total'          => $total,
                        'bobot'          => 0,
                    ]);

                    $this->ctx->headerTotals[$header_id] = ($this->ctx->headerTotals[$header_id] ?? 0) + $total;
                }
            }

            // set parent + wariskan kategori
            foreach ($this->ctx->pendingParents as [$childId, $parent_kode]) {
                if ($parent_kode !== '' && isset($this->ctx->headerMap[$parent_kode])) {
                    $parentId = $this->ctx->headerMap[$parent_kode];
                    RabHeader::whereKey($childId)->update(['parent_id' => $parentId]);

                    $child  = RabHeader::find($childId);
                    $parent = RabHeader::find($parentId);
                    if ($child && $parent && is_null($child->kategori_id) && !is_null($parent->kategori_id)) {
                        $child->kategori_id = $parent->kategori_id;
                        $child->save();
                    }
                }
            }

            $this->updateAllHeaderTotals();
        });
    }

    private function updateAllHeaderTotals(): void
    {
        $headers = RabHeader::where('proyek_id', $this->ctx->proyek_id)
            ->orderBy('kode_sort')->get()->keyBy('id');

        foreach ($headers as $h) {
            $h->nilai = (float)($this->ctx->headerTotals[$h->id] ?? 0);
        }
        $ordered = $headers->sortByDesc(fn($h) => substr_count($h->kode, '.'));
        foreach ($ordered as $h) {
            if ($h->parent_id && isset($headers[$h->parent_id])) {
                $headers[$h->parent_id]->nilai += $h->nilai;
            }
        }
        foreach ($headers as $h) $h->save();
    }
}
