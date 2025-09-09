<?php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\DataTables;
use App\Models\Proyek;
use App\Models\RabHeader;
use App\Models\RabDetail;
use App\Models\AhspHeader;
use App\Models\AhspDetail;
use App\Models\RabPenawaranHeader;
use App\Models\RabPenawaranSection;
use App\Models\RabPenawaranItem;
use App\Models\RabSchedule;
use App\Models\RabScheduleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RabPenawaranController extends Controller
{
    // Menampilkan daftar penawaran untuk sebuah proyek
    public function index(Proyek $proyek)
    {
        $penawarans = $proyek->penawarans()->orderByDesc('created_at')->get();
        return view('rab_penawaran.index', compact('proyek', 'penawarans'));
    }

    public function data(Proyek $proyek)
    {
        $q = RabPenawaranHeader::with(['proyek:id,nama_proyek'])
            ->where('proyek_id', $proyek->id)
            ->select([
                'id',
                'proyek_id',
                'nama_penawaran',          // ✔ ada di DB
                'tanggal_penawaran',       // ✔ ada di DB
                'final_total_penawaran',   // ✔ ada di DB
                'status',
            ]);
    
        return DataTables::eloquent($q)
            ->addIndexColumn() // DT_RowIndex
            ->addColumn('proyek_nama', fn($row) => $row->proyek->nama_proyek ?? '-')
            // Biar kunci-kunci yang dipakai di Blade ada semua:
            ->addColumn('kode', fn($row) => $row->nama_penawaran) // alias "kode" ke nama_penawaran
            ->addColumn('total', fn($row) => $row->final_total_penawaran)
            ->toJson();
    }

    // Menampilkan form untuk membuat penawaran baru
    public function create(Request $request, Proyek $proyek)
    {
        $rabHeaders = RabHeader::where('proyek_id', $proyek->id)
                               ->whereNull('parent_id')
                               ->orderBy('kode_sort')
                               ->get();
    
        $flatRabHeaders = $this->generateFlatHeadersForDropdown($rabHeaders);
    
        $preloadedRabData = [];
        $preloadedArea = null; // Inisialisasi variabel untuk area yang dimuat
        $preloadedSpesifikasi = null; // Inisialisasi variabel untuk spesifikasi yang dimuat
    
        if ($request->has('load_rab_header_id')) {
            $loadRabHeaderId = $request->input('load_rab_header_id');
            // Muat RabHeader beserta semua anak dan detailnya
            $rabHeaderToLoad = RabHeader::with(['children.rabDetails', 'rabDetails'])
                                        ->where('proyek_id', $proyek->id)
                                        ->find($loadRabHeaderId);
    
            if ($rabHeaderToLoad) {
                // Cari RabDetail pertama di RabHeader ini atau anak-anaknya
                $firstRabDetail = null;
                if ($rabHeaderToLoad->rabDetails->isNotEmpty()) {
                    $firstRabDetail = $rabHeaderToLoad->rabDetails->first();
                } else {
                    // Jika RabHeader tidak memiliki detail, cari di anak-anaknya
                    foreach ($rabHeaderToLoad->children as $childHeader) {
                        if ($childHeader->rabDetails->isNotEmpty()) {
                            $firstRabDetail = $childHeader->rabDetails->first();
                            break; // Ambil yang pertama ditemukan
                        }
                    }
                }

                if ($firstRabDetail) {
                    $preloadedArea = $firstRabDetail->area;
                    $preloadedSpesifikasi = $firstRabDetail->spesifikasi;
                }

                $section = $this->buildPreloadedRabStructure($rabHeaderToLoad);
                if ($section !== null) {
                    $preloadedRabData[] = $section;
                }
            }
        } elseif ($request->has('load_all_rab')) {
            $topLevelRabHeaders = RabHeader::with(['children.rabDetails.ahsp', 'rabDetails.ahsp'])
                                            ->where('proyek_id', $proyek->id)
                                            ->whereNull('parent_id')
                                            ->orderBy('kode_sort')
                                            ->get();
    
            foreach ($topLevelRabHeaders as $header) {
                $section = $this->buildPreloadedRabStructure($header);
                if ($section !== null) {
                    $preloadedRabData[] = $section;
                }
            }
            // Untuk 'load_all_rab', area dan spesifikasi dibiarkan null
            // karena tidak ada satu RabDetail tunggal yang mewakili seluruh proyek.
        }
    
        return view('rab_penawaran.create', compact('proyek', 'rabHeaders', 'flatRabHeaders', 'preloadedRabData', 'preloadedArea', 'preloadedSpesifikasi'));
    }    
    public function store(Request $request, Proyek $proyek)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nama_penawaran' => 'required|string|max:255',
                'tanggal_penawaran' => 'required|date',
                'sections' => 'required|array|min:1',
                'sections.*.rab_header_id' => 'required|exists:rab_header,id',
                'sections.*.profit_percentage' => 'required|numeric|min:0|max:100',
                'sections.*.overhead_percentage' => 'required|numeric|min:0|max:100',
                'sections.*.items' => 'nullable|array',
                'sections.*.items.*.rab_detail_id' => 'nullable|exists:rab_detail,id',
                'sections.*.items.*.kode' => 'nullable|string|max:255',
                'sections.*.items.*.deskripsi' => 'nullable|string|max:255',
                'sections.*.items.*.volume' => 'nullable|numeric|min:0.0001',
                'sections.*.items.*.satuan' => 'nullable|string|max:20',
    
                // ⇩⇩ area & spesifikasi hanya di item
                'sections.*.items.*.area' => 'nullable|string|max:255',
                'sections.*.items.*.spesifikasi' => 'nullable|string',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
            ]);
    
            $totalPenawaranBruto = 0;
    
            $penawaranHeader = RabPenawaranHeader::create([
                'proyek_id' => $proyek->id,
                'nama_penawaran' => $request->nama_penawaran,
                'tanggal_penawaran' => $request->tanggal_penawaran,
                'versi' => 1,
                'total_penawaran_bruto' => 0,
                'discount_percentage' => $request->discount_percentage,
                'discount_amount' => 0,
                'final_total_penawaran' => 0,
                'status' => 'draft',
                // ❌ HAPUS: 'area' dan 'spesifikasi' di header
            ]);
    
            foreach ($request->sections as $sectionData) {
                $profitPercentage = (float) $sectionData['profit_percentage'];
                $overheadPercentage = (float) $sectionData['overhead_percentage'];
                $totalSectionPenawaran = 0;
    
                $newSection = RabPenawaranSection::create([
                    'rab_penawaran_header_id' => $penawaranHeader->id,
                    'rab_header_id' => $sectionData['rab_header_id'],
                    'profit_percentage' => $profitPercentage,
                    'overhead_percentage' => $overheadPercentage,
                    'total_section_penawaran' => 0,
                ]);
    
                if (isset($sectionData['items']) && is_array($sectionData['items'])) {
                    foreach ($sectionData['items'] as $itemData) {
                        if (empty($itemData['rab_detail_id'])) continue;
    
                        $rabDetail = RabDetail::with('ahsp.details')->find($itemData['rab_detail_id']);
                        $hargaSatuanDasar = $rabDetail->harga_satuan ?? 0;
                        $ahsp = $rabDetail->ahsp;
    
                        $hargaMaterialDasarItem = $ahsp ? $ahsp->details->where('tipe','material')->sum(fn($d)=>$d->koefisien*$d->harga_satuan) : null;
                        $hargaUpahDasarItem     = $ahsp ? $ahsp->details->where('tipe','upah')->sum(fn($d)=>$d->koefisien*$d->harga_satuan) : null;
    
                        $koef = 1 + ($profitPercentage/100) + (($overheadPercentage ?? 0)/100);
                        $hargaSatuanCalculated = $hargaSatuanDasar * $koef;
                        $hargaSatuanPenawaran  = $hargaSatuanCalculated;
    
                        $volume = (float) ($itemData['volume'] ?? 0);
                        $totalPenawaranItem = $hargaSatuanPenawaran * $volume;
    
                        $hargaMaterialCalculatedItem = $hargaMaterialDasarItem ? $hargaMaterialDasarItem * $koef : null;
                        $hargaUpahCalculatedItem     = $hargaUpahDasarItem     ? $hargaUpahDasarItem     * $koef : null;
    
                        RabPenawaranItem::create([
                            'rab_penawaran_section_id' => $newSection->id,
                            'rab_detail_id'            => $itemData['rab_detail_id'],
                            'kode'                     => $itemData['kode'] ?? null,
                            'deskripsi'                => $itemData['deskripsi'] ?? null,
                            'volume'                   => $volume,
                            'satuan'                   => $itemData['satuan'] ?? null,
    
                            'harga_satuan_dasar'      => $hargaSatuanDasar,
                            'harga_satuan_calculated' => $hargaSatuanCalculated,
                            'harga_satuan_penawaran'  => $hargaSatuanPenawaran,
                            'total_penawaran_item'    => $totalPenawaranItem,
    
                            'harga_material_dasar_item'      => $hargaMaterialDasarItem,
                            'harga_upah_dasar_item'          => $hargaUpahDasarItem,
                            'harga_material_calculated_item' => $hargaMaterialCalculatedItem,
                            'harga_upah_calculated_item'     => $hargaUpahCalculatedItem,
                            'harga_material_penawaran_item'  => $hargaMaterialCalculatedItem,
                            'harga_upah_penawaran_item'      => $hargaUpahCalculatedItem,
    
                            // ✅ SIMPAN di ITEM
                            'area'        => $itemData['area'] ?? null,
                            'spesifikasi' => $itemData['spesifikasi'] ?? null,
                        ]);
    
                        $totalSectionPenawaran += $totalPenawaranItem;
                    }
                }
    
                $newSection->total_section_penawaran = $totalSectionPenawaran;
                $newSection->save();
    
                $totalPenawaranBruto += $totalSectionPenawaran;
            }
    
            $discountPercentage = (float) $request->discount_percentage;
            $discountAmount = ($totalPenawaranBruto * $discountPercentage) / 100;
            $finalTotalPenawaran = $totalPenawaranBruto - $discountAmount;
    
            $penawaranHeader->update([
                'total_penawaran_bruto' => $totalPenawaranBruto,
                'discount_amount' => $discountAmount,
                'final_total_penawaran' => $finalTotalPenawaran,
            ]);
    
            DB::commit();
            return redirect()
                ->route('proyek.penawaran.show', ['proyek' => $proyek->id, 'penawaran' => $penawaranHeader->id])
                ->with('success', 'Penawaran berhasil dibuat!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan penawaran: '.$e->getMessage());
        }
    }    

    // Menampilkan detail penawaran
    public function show(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        // Eager load semua relasi yang diperlukan untuk tampilan
        $penawaran->load([
            'sections' => function($query) {
                $query->with([
                    'rabHeader', // Untuk mendapatkan deskripsi RAB Header asli
                    'items' => function($query) {
                        $query->with('rabDetail'); // Untuk mendapatkan detail RAB asli jika diperlukan
                    }
                ])->orderBy('id'); // Urutkan section berdasarkan ID atau urutan logis lainnya
            }
        ]);

        return view('rab_penawaran.show', compact('proyek', 'penawaran'));
    }

    public function showGab(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        // Eager load semua relasi yang diperlukan untuk tampilan
        $penawaran->load([
            'sections' => function($query) {
                $query->with([
                    'rabHeader', // Untuk mendapatkan deskripsi RAB Header asli
                    'items' => function($query) {
                        $query->with('rabDetail'); // Untuk mendapatkan detail RAB asli jika diperlukan
                    }
                ])->orderBy('id'); // Urutkan section berdasarkan ID atau urutan logis lainnya
            }
        ]);
        return view('rab_penawaran.show-gab', compact('proyek', 'penawaran'));
    }
    // Menampilkan form untuk mengedit penawaran
    public function edit(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        // Logika edit akan lebih kompleks karena melibatkan update nested data
        // Anda perlu memuat data penawaran yang ada ke form
        $rabHeaders = RabHeader::where('proyek_id', $proyek->id)
                               ->whereNull('parent_id')
                               ->orderBy('kode_sort')
                               ->get();
        $flatRabHeaders = $this->generateFlatHeadersForDropdown($rabHeaders);

        $penawaran->load([
            'sections' => function($q) {
                $q->whereNull('parent_id')->with(['children.rabHeader', 'items']);
            },
            'sections.rabHeader',
        ]);

        return view('rab_penawaran.edit', compact('proyek', 'penawaran', 'rabHeaders', 'flatRabHeaders'));
    }

    // Memperbarui penawaran
    public function update(Request $request, Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        DB::beginTransaction();
        try {
            // Validasi header + (opsional) sections
            $request->validate([
                'nama_penawaran'        => 'required|string|max:255',
                'tanggal_penawaran'     => 'required|date',
                'discount_percentage'   => 'nullable|numeric|min:0|max:100',
            
                'sections'                                  => 'nullable|array|min:1',
                'sections.*.rab_header_id'                  => 'required_with:sections|exists:rab_header,id',
                'sections.*.profit_percentage'              => 'required_with:sections|numeric|min:0|max:100',
                'sections.*.overhead_percentage'            => 'required_with:sections|numeric|min:0|max:100',
                'sections.*.items'                          => 'nullable|array',
                'sections.*.items.*.rab_detail_id'          => 'nullable|exists:rab_detail,id',
                'sections.*.items.*.kode'                   => 'nullable|string|max:255',
                'sections.*.items.*.deskripsi'              => 'nullable|string|max:255',
                'sections.*.items.*.volume'                 => 'nullable|numeric|min:0.0001',
                'sections.*.items.*.satuan'                 => 'nullable|string|max:20',
                'sections.*.items.*.area'                   => 'nullable|string|max:255',
                'sections.*.items.*.spesifikasi'            => 'nullable|string',
            ]);
            
            $penawaran->update([
                'nama_penawaran'       => $request->nama_penawaran,
                'tanggal_penawaran'    => $request->tanggal_penawaran,
                'discount_percentage'  => $request->discount_percentage,
            ]);
            

            $totalPenawaranBruto = 0;

            // Jika ada sections dikirim dari form edit → rebuild semua section & item
            if ($request->filled('sections')) {
                // Hapus items & sections lama (pastikan urutan: items dulu, lalu sections)
                $penawaran->loadMissing('sections.items');
                foreach ($penawaran->sections as $sec) {
                    $sec->items()->delete();
                }
                $penawaran->sections()->delete();

                // Bangun ulang dari request
                foreach ($request->sections as $sectionData) {
                    $profitPercentage   = (float) ($sectionData['profit_percentage']   ?? 0);
                    $overheadPercentage = (float) ($sectionData['overhead_percentage'] ?? 0);
                    $totalSectionPenawaran = 0;

                    $newSection = RabPenawaranSection::create([
                        'rab_penawaran_header_id' => $penawaran->id,
                        'rab_header_id'           => $sectionData['rab_header_id'],
                        'profit_percentage'       => $profitPercentage,
                        'overhead_percentage'     => $overheadPercentage,
                        'total_section_penawaran' => 0,
                    ]);

                    if (!empty($sectionData['items']) && is_array($sectionData['items'])) {
                        foreach ($sectionData['items'] as $itemData) {
                            if (empty($itemData['rab_detail_id'])) {
                                continue;
                            }

                            // Ambil RAB Detail + AHSP untuk harga dasar
                            $rabDetail = RabDetail::with('ahsp.details')->find($itemData['rab_detail_id']);
                            if (!$rabDetail) {
                                continue;
                            }

                            $hargaSatuanDasar = (float) ($rabDetail->harga_satuan ?? 0);
                            $ahsp             = $rabDetail->ahsp;

                            // Hitung harga dasar material & upah dari AHSP (jika ada)
                            $hargaMaterialDasarItem = $ahsp
                                ? $ahsp->details->where('tipe', 'material')->sum(fn($d) => (float)$d->koefisien * (float)$d->harga_satuan)
                                : null;

                            $hargaUpahDasarItem = $ahsp
                                ? $ahsp->details->where('tipe', 'upah')->sum(fn($d) => (float)$d->koefisien * (float)$d->harga_satuan)
                                : null;

                            $koef = 1 + ($profitPercentage / 100) + ($overheadPercentage / 100);

                            $hargaSatuanCalculated = $hargaSatuanDasar * $koef;
                            $hargaSatuanPenawaran  = $hargaSatuanCalculated;

                            $volume = (float) ($itemData['volume'] ?? 0);
                            $totalPenawaranItem = $hargaSatuanPenawaran * $volume;

                            $hargaMaterialCalculatedItem = $hargaMaterialDasarItem !== null ? ($hargaMaterialDasarItem * $koef) : null;
                            $hargaUpahCalculatedItem     = $hargaUpahDasarItem     !== null ? ($hargaUpahDasarItem     * $koef) : null;

                            $hargaMaterialPenawaranItem  = $hargaMaterialCalculatedItem;
                            $hargaUpahPenawaranItem      = $hargaUpahCalculatedItem;

                            RabPenawaranItem::create([
                                'rab_penawaran_section_id'     => $newSection->id,
                                'rab_detail_id'                => $itemData['rab_detail_id'],
                                'kode'                         => $itemData['kode']        ?? null,
                                'deskripsi'                    => $itemData['deskripsi']   ?? null,
                                'volume'                       => $volume,
                                'satuan'                       => $itemData['satuan']      ?? null,

                                'harga_satuan_dasar'           => $hargaSatuanDasar,
                                'harga_satuan_calculated'      => $hargaSatuanCalculated,
                                'harga_satuan_penawaran'       => $hargaSatuanPenawaran,
                                'total_penawaran_item'         => $totalPenawaranItem,

                                'harga_material_dasar_item'    => $hargaMaterialDasarItem,
                                'harga_upah_dasar_item'        => $hargaUpahDasarItem,
                                'harga_material_calculated_item'=> $hargaMaterialCalculatedItem,
                                'harga_upah_calculated_item'   => $hargaUpahCalculatedItem,
                                'harga_material_penawaran_item'=> $hargaMaterialPenawaranItem,
                                'harga_upah_penawaran_item'    => $hargaUpahPenawaranItem,

                                // Field tambahan dari form (opsional)
                                'area'                         => $itemData['area']        ?? null,
                                'spesifikasi'                  => $itemData['spesifikasi'] ?? null,
                            ]);

                            $totalSectionPenawaran += $totalPenawaranItem;
                        }
                    }

                    $newSection->update(['total_section_penawaran' => $totalSectionPenawaran]);
                    $totalPenawaranBruto += $totalSectionPenawaran;
                }

                // Setelah rebuild sections → hitung ulang total header & diskon
                $discountPercentage = (float) ($request->discount_percentage ?? 0);
                $discountAmount     = ($totalPenawaranBruto * $discountPercentage) / 100;
                $finalTotalPenawaran= $totalPenawaranBruto - $discountAmount;

                $penawaran->update([
                    'total_penawaran_bruto' => $totalPenawaranBruto,
                    'discount_amount'       => $discountAmount,
                    'final_total_penawaran' => $finalTotalPenawaran,
                ]);
            } else {
                // Tidak ada perubahan sections dari form → hanya hitung ulang berdasarkan data yang sudah ada
                $this->recalculatePenawaranTotals($penawaran);
            }

            DB::commit();

            return redirect()
                ->route('proyek.penawaran.show', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id])
                ->with('success', 'Penawaran berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('VALIDATION FAILED:');
            \Log::error('Input:', $request->all());
            \Log::error('Validation Errors:', $e->errors());

            return back()->withErrors($e->errors())
                        ->withInput()
                        ->with('error', 'Terjadi kesalahan validasi. Silakan periksa kembali input.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('GAGAL UPDATE PENAWARAN: ' . $e->getMessage());
            return back()->withInput()
                        ->with('error', 'Gagal memperbarui penawaran: ' . $e->getMessage());
        }
    }


    // Menghapus penawaran
        public function destroy(Proyek $proyek, RabPenawaranHeader $penawaran)
        {
            // (opsional) pastikan penawaran milik proyek yang sama
            if ($penawaran->proyek_id !== $proyek->id) {
                abort(404);
            }
        
            $penawaran->delete();
        
            // Kembali ke proyek.show dengan tab RAB Penawaran aktif
            return redirect()
                ->to(route('proyek.show', ['id' => $proyek->id, 'tab' => 'rabpenawaran']) . '#rabpenawaranContent')
                ->with('success', 'Penawaran berhasil dihapus.');
        }
    
    /**
     * Membangun struktur data RAB yang dimuat sebelumnya untuk form penawaran.
     * Ini akan secara rekursif memuat RAB Details dari RabHeader dan anak-anaknya.
     *
     * @param RabHeader $header
     * @param int $level
     * @return array|null
     */
    private function buildPreloadedRabStructure(RabHeader $header, $level = 0)
    {
        // Muat relasi rabDetails dan children secara eager
        $header->loadMissing(['rabDetails.ahsp', 'children.rabDetails.ahsp']);

        $hasItems = $header->rabDetails->isNotEmpty();
        $hasChildren = $header->children->isNotEmpty();

        // Jika header tidak memiliki item dan tidak memiliki anak, jangan sertakan
        if (! $hasItems && ! $hasChildren) {
            return null;
        }

        $sectionData = [
            'rab_header_id' => $header->id,
            'deskripsi' => $header->deskripsi,
            'profit_percentage' => 0,
            'overhead_percentage' => 0,
            'items' => [],
            'children_sections' => [], // Untuk struktur hirarkis di sisi klien
        ];

        // Tambahkan item RAB Detail langsung di bawah header ini
        foreach ($header->rabDetails->sortBy('kode_sort') as $detail) {
            $sectionData['items'][] = [
                'rab_detail_id' => $detail->id,
                'kode' => $detail->kode,
                'deskripsi' => $detail->deskripsi,
                'volume' => $detail->volume,
                'satuan' => $detail->satuan,
                'harga_satuan_dasar' => $detail->harga_satuan,
                'harga_material_dasar_item' => $detail->ahsp->total_material ?? null,
                'harga_upah_dasar_item' => $detail->ahsp->total_upah ?? null,
                'area' => $detail->area, // MENAMBAHKAN INI
                'spesifikasi' => $detail->spesifikasi, // MENAMBAHKAN INI
            ];
        }

        // Rekursif untuk anak-anak
        foreach ($header->children->sortBy('kode_sort') as $childHeader) {
            $childSection = $this->buildPreloadedRabStructure($childHeader, $level + 1);
            if ($childSection !== null) {
                $sectionData['children_sections'][] = $childSection;
            }
        }

        return $sectionData;
    }


    // Helper untuk dropdown RabHeader (bisa dipindahkan ke trait jika digunakan di banyak tempat)
    private function generateFlatHeadersForDropdown($headers, $level = 0)
    {
        $flatList = [];
        foreach ($headers as $header) {
            $indent = str_repeat('-- ', $level);
            $flatList[] = [
                'id' => $header->id,
                'text' => $indent . $header->kode . ' - ' . $header->deskripsi,
                'kategori_id' => $header->kategori_id, // Sertakan kategori_id jika perlu filter AHSP
            ];

            if ($header->children->isNotEmpty()) {
                $flatList = array_merge($flatList, $this->generateFlatHeadersForDropdown($header->children, $level + 1));
            }
        }
        return $flatList;
    }

    // Endpoint AJAX untuk pencarian RAB Headers (untuk Select2/dynamic dropdown)
    public function searchRabHeaders(Request $request, Proyek $proyek)
    {
        $search = $request->input('search');

        $query = RabHeader::where('proyek_id', $proyek->id)
                            ->whereNull('parent_id') // Hanya header utama
                            ->orderBy('kode_sort');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', '%' . $search . '%')
                  ->orWhere('deskripsi', 'like', '%' . $search . '%');
            });
        }

        $headers = $query->limit(20)->get();

        return response()->json($headers->map(function ($header) {
            return [
                'id' => $header->id,
                'text' => $header->kode . ' - ' . $header->deskripsi,
                'kategori_id' => $header->kategori_id, // Sertakan kategori_id jika perlu filter AHSP
            ];
        }));
    }

    // Endpoint AJAX untuk pencarian RAB Details (untuk Select2/dynamic dropdown)
    public function searchRabDetails(Request $request, $proyekId)
    {
        $search = $request->input('search');
        $rabHeaderId = $request->input('rab_header_id');
    
        $query = \App\Models\RabDetail::query()
            ->where('proyek_id', $proyekId)
            ->when($rabHeaderId, fn($q) => $q->where('rab_header_id', $rabHeaderId))
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            }))
            ->with('ahsp');
    
        $results = $query->limit(20)->get();
    
        return response()->json($results->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => "{$item->kode} - {$item->deskripsi} ({$item->satuan})",
                'harga_satuan' => $item->harga_satuan,
                'volume' => $item->volume,
                'ahsp_id' => $item->ahsp_id,
                'area' => $item->area, // Sertakan area
                'spesifikasi' => $item->spesifikasi, // Sertakan spesifikasi
            ];
        }));
    }

   public function generatePdf(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        // Eager load semua relasi yang diperlukan untuk tampilan PDF
        $penawaran->load([
            'sections' => function($query) {
                $query->with([
                    'rabHeader',
                    'items' => function($query) {
                        $query->with('rabDetail');
                    }
                ]);
            }
        ]);

        // Sort sections by rabHeader->kode_sort for correct hierarchical display
        // Urutkan bagian berdasarkan rabHeader->kode_sort untuk tampilan hierarki yang benar
        $penawaran->sections = $penawaran->sections->sortBy(function($section) {
            return $section->rabHeader->kode_sort ?? '';
        })->values(); // Re-index the collection after sorting

        // Muat tampilan Blade untuk PDF
        $pdf = Pdf::loadView('rab_penawaran.pdf_template', compact('proyek', 'penawaran'));

        // Atur ukuran kertas dan orientasi (opsional)
        // $pdf->setPaper('A4', 'portrait');

        // Unduh PDF
        $filename = 'Penawaran_' . str_replace(' ', '_', $penawaran->nama_penawaran) . '_' . $penawaran->versi . '.pdf';
        return $pdf->download($filename);
    }
    
    // Helper method for recalculating totals
    protected function recalculatePenawaranTotals(RabPenawaranHeader $penawaran)
    {
        // Ensure sections are loaded to calculate totalBruto
        $penawaran->loadMissing('sections.items');

        // Sum the total_section_penawaran from all sections
        $totalBruto = $penawaran->sections->sum('total_section_penawaran');
        
        // Calculate discount amount based on the current discount_percentage
        $discountAmount = $totalBruto * ($penawaran->discount_percentage / 100);
        
        // Calculate the final total after discount
        $finalTotal = $totalBruto - $discountAmount;

        // Update the penawaran header with the new calculated totals
        $penawaran->update([
            'total_penawaran_bruto' => $totalBruto,
            'discount_amount' => $discountAmount,
            'final_total_penawaran' => $finalTotal,
        ]);
    }

    public function generatePdfSplit(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        // Eager load relasi yang diperlukan
        $penawaran->load([
            'sections' => function($query) {
                $query->with([
                    'rabHeader',
                    'items' => function($query) {
                        $query->with('rabDetail');
                    }
                ]);
            }
        ]);

        // Urutkan sections berdasarkan kode_sort agar urut rapi
        $penawaran->sections = $penawaran->sections->sortBy(function($section) {
            return $section->rabHeader->kode_sort ?? '';
        })->values();

        // Pakai template split
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'rab_penawaran.pdf_template_split',
            compact('proyek', 'penawaran')
        );

        $filename = 'Penawaran_' . str_replace(' ', '_', $penawaran->nama_penawaran) . '_' . $penawaran->versi . '_SPLIT.pdf';
        return $pdf->download($filename);
    }

        /**
     * Setujui penawaran (status=final) & buat snapshot bobot dari penawaran.
     * Dipicu dari tombol: Setujui & Snapshot Bobot
     */
    public function approveAndSnapshot(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        DB::transaction(function () use ($penawaran) {
            if ($penawaran->status !== 'final') {
                $penawaran->update(['status' => 'final']);
            }
            $this->snapshotWeightsFromOffer($penawaran);
        });

        return redirect()
            ->route('proyek.penawaran.show', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id])
            ->with('success', 'Penawaran disetujui & snapshot bobot tersimpan.');
    }

    /**
     * Buat snapshot bobot dari penawaran (tanpa mengubah status).
     * Dipicu dari tombol: Snapshot Bobot
     */
    public function snapshotWeights(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $this->snapshotWeightsFromOffer($penawaran);

        return redirect()
            ->route('proyek.penawaran.show', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id])
            ->with('success', 'Snapshot bobot berhasil dibuat dari penawaran.');
    }

    /**
     * Generate rab_schedule_detail dari snapshot bobot (untuk penawaran ini).
     * Wajib: sudah ada setup rab_schedule (minggu_ke & durasi) dengan penawaran_id ini.
     * Dipicu dari tombol: Generate Schedule
     */
    public function generateScheduleFromSnapshot(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $hasSetup = RabSchedule::where('proyek_id', $proyek->id)
            ->where('penawaran_id', $penawaran->id)
            ->exists();

        if (!$hasSetup) {
            return back()->with('error', 'Belum ada setup Rab Schedule (minggu mulai & durasi) untuk penawaran ini.');
        }

        $this->buildScheduleFromSnapshot($proyek, $penawaran);

        return redirect()
            ->route('proyek.penawaran.show', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id])
            ->with('success', 'Schedule detail berhasil digenerate dari snapshot bobot.');
    }

    /* ===================== HELPER ===================== */

    /**
     * Snapshot bobot dari penawaran yang disetujui:
     * - Hitung gross item = (harga_material_penawaran_item + harga_upah_penawaran_item) * volume
     * - Simpan ke rab_penawaran_weight (level=item)
     * - Agregasi per header top-level (level=header)
     * Catatan: Diskon tidak disebar (sesuai kebutuhan penagihan/progress).
     */
    protected function snapshotWeightsFromOffer(RabPenawaranHeader $penawaran): void
    {
        // Pastikan relasi tersedia
        $penawaran->loadMissing('sections.rabHeader.parent','sections.rabHeader','sections.items');

        // Total gross proyek (tanpa diskon)
        $totalGross = 0.0;
        foreach ($penawaran->sections as $sec) {
            foreach ($sec->items as $it) {
                $v = (float)($it->volume ?? 0);
                $m = (float)($it->harga_material_penawaran_item ?? 0);
                $j = (float)($it->harga_upah_penawaran_item ?? 0);
                $totalGross += ($m + $j) * $v;
            }
        }

        // Bersihkan snapshot lama untuk penawaran ini
        DB::table('rab_penawaran_weight')->where('penawaran_id', $penawaran->id)->delete();

        if ($totalGross <= 0) {
            // Tidak ada nilai; selesai.
            return;
        }

        DB::transaction(function() use ($penawaran, $totalGross) {
            $grossPerTop = []; // [top_header_id => gross]

            foreach ($penawaran->sections as $sec) {
                $hdr = $sec->rabHeader; if (!$hdr) continue;

                // Normalisasi ke header top-level
                $top = $hdr;
                // Jika relasi parent() tersedia, loop hingga parent null
                while ($top && property_exists($top, 'parent_id') && $top->parent_id) {
                    // Jangan trigger query berulang kalau relasi belum di-load
                    if (method_exists($top, 'relationLoaded') && !$top->relationLoaded('parent')) {
                        $top->load('parent');
                    }
                    $top = $top->parent ?: null;
                }
                $topId = $top ? $top->id : $hdr->id;

                foreach ($sec->items as $it) {
                    $v = (float)($it->volume ?? 0);
                    $m = (float)($it->harga_material_penawaran_item ?? 0);
                    $j = (float)($it->harga_upah_penawaran_item ?? 0);
                    $gross = ($m + $j) * $v;

                    DB::table('rab_penawaran_weight')->insert([
                        'proyek_id'                => $penawaran->proyek_id,
                        'penawaran_id'             => $penawaran->id,
                        'rab_header_id'            => $topId,
                        'rab_penawaran_section_id' => $sec->id,
                        'rab_penawaran_item_id'    => $it->id,
                        'level'                    => 'item',
                        'gross_value'              => $gross,
                        'weight_pct_project'       => $gross > 0 ? round(($gross / $totalGross) * 100, 4) : 0,
                        'weight_pct_in_header'     => null,
                        'computed_at'              => now(),
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);

                    $grossPerTop[$topId] = ($grossPerTop[$topId] ?? 0) + $gross;
                }
            }

            // Baris level=header + lengkapi persentase item dalam header
            foreach ($grossPerTop as $topId => $gTop) {
                DB::table('rab_penawaran_weight')->insert([
                    'proyek_id'                => $penawaran->proyek_id,
                    'penawaran_id'             => $penawaran->id,
                    'rab_header_id'            => $topId,
                    'rab_penawaran_section_id' => null,
                    'rab_penawaran_item_id'    => null,
                    'level'                    => 'header',
                    'gross_value'              => $gTop,
                    'weight_pct_project'       => round(($gTop / $totalGross) * 100, 4),
                    'weight_pct_in_header'     => null,
                    'computed_at'              => now(),
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);

                if ($gTop > 0) {
                    DB::table('rab_penawaran_weight')
                        ->where('penawaran_id', $penawaran->id)
                        ->where('level', 'item')
                        ->where('rab_header_id', $topId)
                        ->update([
                            // Persentase item terhadap header top-nya
                            'weight_pct_in_header' => DB::raw('ROUND((gross_value / '.($gTop).') * 100, 4)')
                        ]);
                }
            }
        });
    }

    /**
     * Generate rab_schedule_detail dari snapshot bobot untuk penawaran tertentu.
     * Aturan: sebar rata per minggu berdasarkan durasi; minggu terakhir dikoreksi agar total presisi.
     */
    protected function buildScheduleFromSnapshot(Proyek $proyek, RabPenawaranHeader $penawaran): void
    {
        // Ambil bobot header (top-level) dari snapshot penawaran ini
        $rows = DB::table('rab_penawaran_weight')
            ->select('rab_header_id', DB::raw('ROUND(SUM(weight_pct_project),4) as pct'))
            ->where('penawaran_id', $penawaran->id)
            ->where('level', 'header')
            ->groupBy('rab_header_id')
            ->get()
            ->keyBy('rab_header_id');

        // Ambil setup schedule (start minggu & durasi) untuk penawaran ini
        $sched = RabSchedule::where('proyek_id', $proyek->id)
            ->where('penawaran_id', $penawaran->id)
            ->get()
            ->keyBy('rab_header_id');

        DB::transaction(function() use ($proyek, $penawaran, $rows, $sched) {
            // Hapus detail lama hanya untuk penawaran ini
            RabScheduleDetail::where('proyek_id', $proyek->id)
                ->where('penawaran_id', $penawaran->id)
                ->delete();

            foreach ($sched as $headerId => $sch) {
                $pct   = (float)($rows[$headerId]->pct ?? 0.0);   // % proyek untuk header ini
                $dur   = max((int)$sch->durasi, 0);
                $start = (int)$sch->minggu_ke;

                if ($dur <= 0 || $pct <= 0) continue;

                $perWeek = round($pct / $dur, 4);
                $sum = 0.0;

                for ($i=0; $i<$dur; $i++) {
                    $isLast = ($i === $dur - 1);
                    $val = $isLast ? round($pct - $sum, 4) : $perWeek; // koreksi rounding di akhir
                    $sum += $val;

                    RabScheduleDetail::create([
                        'proyek_id'      => $proyek->id,
                        'penawaran_id'   => $penawaran->id,
                        'rab_header_id'  => $headerId,
                        'minggu_ke'      => $start + $i,
                        'bobot_mingguan' => $val,
                    ]);
                }
            }
        });
    }

    public function approve(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        if ($penawaran->proyek_id !== $proyek->id) abort(404);

        $penawaran->update(['status' => 'final']);
        return back()->with('success', 'Penawaran disetujui (FINAL).');
    }



}
