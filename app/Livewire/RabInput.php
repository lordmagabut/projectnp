<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RabHeader;
use App\Models\RabDetail;
use App\Models\AhspHeader;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class RabInput extends Component
{
    public $proyek_id;
    public $kategori_id;          // kategori untuk pohon header yang sedang dibuka
    public $headers;              // koleksi header (root + children eager)
    public $ahspList;             // daftar AHSP (untuk dropdown/search)
    public $projectGrandTotal = 0;

    public $newHeader = [
        'parent_id' => null,
        'deskripsi' => '',
    ];

    public $newItem = [
        'header_id'    => '',
        'ahsp_id'      => '',
        'deskripsi'    => '',
        'volume'       => 1,
        'satuan'       => '',
        'area'         => '',
        'spesifikasi'  => '',
        'harga_satuan' => 0,
        'harga_material' => 0,   // <— tambah
        'harga_upah'     => 0,   // <— tambah
      ];
      

    public $flatHeaders = [];     // untuk dropdown header (dengan indent)

    // edit detail (inline/modal)
    public $editingDetailId = null;
    public $editingDetailSpesifikasi = '';
    public $editingDetailVolume = 0;
    public $editingDetailSatuan = '';
    public $editingDetailDeskripsi = '';
    public $editingDetailAhspId = '';

    // edit header (deskripsi)
    public $editingHeaderId = null;
    public $editingHeaderDescription = '';

    // Filter AHSP
    public $ahspSearch = '';
    public $selectedHeaderCategoryId = null;

    public function mount($proyek_id, $kategori_id)
    {
        $this->proyek_id  = (int)$proyek_id;
        $this->kategori_id = (int)$kategori_id;

        $this->selectedHeaderCategoryId = $this->kategori_id;

        $this->loadData();     // muat header + hitung rekap
        $this->loadAhspList(); // muat daftar AHSP awal
    }

    // =====================
    // UPDATED hooks
    // =====================
    public function updated($propertyName)
    {
        if ($propertyName === 'newItem.header_id') {
            // reset pilihan AHSP saat header berubah
            $this->ahspSearch = '';
            $this->newItem['ahsp_id'] = '';
            $this->newItem['deskripsi'] = '';
            $this->newItem['satuan'] = '';
            $this->newItem['harga_satuan'] = 0;

            if (!empty($this->newItem['header_id'])) {
                $header = RabHeader::find($this->newItem['header_id']);
                $this->selectedHeaderCategoryId = $header?->kategori_id ?? $this->kategori_id;
            } else {
                $this->selectedHeaderCategoryId = $this->kategori_id;
            }
            $this->loadAhspList();

        } elseif ($propertyName === 'ahspSearch') {
            $this->loadAhspList();

        } elseif ($propertyName === 'newItem.ahsp_id') {
            if (!empty($this->newItem['ahsp_id'])) {
                $komp = $this->getAhspKomponen((int) $this->newItem['ahsp_id']);
                $this->newItem['deskripsi']      = $komp['nama'];
                $this->newItem['satuan']         = $komp['satuan'];
                $this->newItem['harga_material'] = (float)$komp['harga_material']; // <—
                $this->newItem['harga_upah']     = (float)$komp['harga_upah'];     // <—
                $this->newItem['harga_satuan']   = (float)$komp['harga_gabungan'];
            } else {
                $this->newItem['deskripsi'] = '';
                $this->newItem['satuan']    = '';
                $this->newItem['harga_material'] = 0; // <—
                $this->newItem['harga_upah']     = 0; // <—
                $this->newItem['harga_satuan']   = 0;
            }
        }
    }        

    // =====================
    // LOAD & LIST AHSP
    // =====================
    #[On('rabHeaderCreated')]
    #[On('rabDetailUpdated')]
    public function loadData()
    {
        $this->headers = RabHeader::with(['children.rabDetails.ahsp', 'rabDetails.ahsp'])
            ->where('proyek_id', $this->proyek_id)
            ->where('kategori_id', $this->kategori_id)
            ->whereNull('parent_id')
            ->orderBy('kode_sort')
            ->get();

        // Grand total proyek (dari detail gabungan)
        $this->projectGrandTotal = RabDetail::whereHas('header', function ($q) {
            $q->where('proyek_id', $this->proyek_id);
        })->sum('total');

        $this->dispatch('projectTotalUpdated', total: $this->projectGrandTotal);

        // Rekap tiap root header (rekursif ke children) → isi nilai_material, nilai_upah, nilai
        DB::beginTransaction();
        try {
            foreach ($this->headers as $header) {
                $this->updateHeaderAndChildrenTotals($header);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal memperbarui nilai header: ' . $e->getMessage());
        }

        $this->flatHeaders = $this->generateFlatHeadersForDropdown($this->headers);
    }

    private function loadAhspList()
    {
        $query = AhspHeader::orderBy('kode_pekerjaan');

        if ($this->selectedHeaderCategoryId) {
            $query->where('kategori_id', $this->selectedHeaderCategoryId);
        }

        if (!empty($this->ahspSearch)) {
            $term = '%' . $this->ahspSearch . '%';
            $query->where(function ($q) use ($term) {
                $q->where('kode_pekerjaan', 'like', $term)
                  ->orWhere('nama_pekerjaan', 'like', $term);
            });
        }

        $this->ahspList = $query->get();
    }

    // =====================
    // AHSP helper
    // =====================
    /**
     * Ambil komponen harga per 1 satuan analisa dari AHSP:
     * - harga material = SUM(subtotal) WHERE tipe='material'
     * - harga upah     = SUM(subtotal) WHERE tipe='upah'
     * - harga gabungan = header.total_harga_pembulatan || header.total_harga || (material+upah)
     */
    private function getAhspKomponen(int $ahspId): array
    {
        $material = (float) DB::table('ahsp_detail')->where('ahsp_id',$ahspId)->where('tipe','material')->sum('subtotal');
        $upah     = (float) DB::table('ahsp_detail')->where('ahsp_id',$ahspId)->where('tipe','upah')->sum('subtotal');
        $hdr      = DB::table('ahsp_header')->where('id',$ahspId)->first();
    
        // ❌ JANGAN: total_harga_pembulatan / ceil ke ribuan
        // ✅ YA: gabungan mentah = material + upah
        $gab = $material + $upah;
    
        return [
            'harga_material' => $material,
            'harga_upah'     => $upah,
            'harga_gabungan' => $gab,
            'satuan'         => $hdr?->satuan ?? null,
            'kode'           => $hdr?->kode_pekerjaan ?? '',
            'nama'           => $hdr?->nama_pekerjaan ?? '',
        ];
    }
    

    // =====================
    // HEADER utils
    // =====================
    /**
     * Rekursif: hitung total material/upah/total dari header ini + seluruh children.
     * Mengembalikan array ['mat'=>..., 'uph'=>..., 'tot'=>...]
     */
    private function updateHeaderAndChildrenTotals(RabHeader $header): array
    {
        $sumMat = (float)$header->rabDetails->sum('total_material');
        $sumUph = (float)$header->rabDetails->sum('total_upah');
        $sumTot = (float)$header->rabDetails->sum('total');

        foreach ($header->children as $child) {
            $childAgg = $this->updateHeaderAndChildrenTotals($child);
            $sumMat += $childAgg['mat'];
            $sumUph += $childAgg['uph'];
            $sumTot += $childAgg['tot'];
        }

        $dirty = false;
        if ((float)$header->nilai_material !== $sumMat) { $header->nilai_material = $sumMat; $dirty = true; }
        if ((float)$header->nilai_upah     !== $sumUph) { $header->nilai_upah     = $sumUph; $dirty = true; }
        if ((float)$header->nilai          !== $sumTot) { $header->nilai          = $sumTot; $dirty = true; }
        if ($dirty) { $header->save(); }

        return ['mat' => $sumMat, 'uph' => $sumUph, 'tot' => $sumTot];
    }

    private function generateFlatHeadersForDropdown($headers, $level = 0): array
    {
        $flat = [];
        foreach ($headers as $h) {
            $indent = str_repeat('-- ', $level);
            $flat[] = ['id' => $h->id, 'display_name' => $indent . $h->kode . ' - ' . $h->deskripsi];

            if ($h->children->isNotEmpty()) {
                $flat = array_merge($flat, $this->generateFlatHeadersForDropdown($h->children, $level + 1));
            }
        }
        return $flat;
    }

    // =====================
    // AKSI: header
    // =====================
    public function tambahHeader()
    {
        $this->validate([
            'newHeader.deskripsi' => 'required|string|max:255',
            'newHeader.parent_id' => 'nullable|exists:rab_header,id',
        ], [
            'newHeader.deskripsi.required' => 'Deskripsi header harus diisi.',
            'newHeader.parent_id.exists'   => 'Induk yang dipilih tidak valid.',
        ]);

        $parentId = $this->newHeader['parent_id'] ?: null;
        $newKode = '';
        $newKodeSort = '';

        if ($parentId) {
            $parent = RabHeader::find($parentId);
            if (!$parent) {
                session()->flash('error', 'Induk yang dipilih tidak ditemukan.');
                return;
            }
            $existingChildren = $parent->children()
                ->where('proyek_id', $this->proyek_id)
                ->where('kategori_id', $this->kategori_id)
                ->count();
            $seq = $existingChildren + 1;
            $newKode = $parent->kode . '.' . $seq;
            $newKodeSort = implode('.', array_map(fn($p) => str_pad($p, 4, '0', STR_PAD_LEFT), explode('.', $newKode)));
        } else {
            // Root header untuk kategori ini = kode kategori (hanya 1)
            $existsRoot = RabHeader::where('proyek_id', $this->proyek_id)
                ->where('kategori_id', $this->kategori_id)
                ->whereNull('parent_id')
                ->where('kode', (string)$this->kategori_id)
                ->first();

            if ($existsRoot) {
                session()->flash('error', 'Header utama kategori (Kode: ' . $this->kategori_id . ') sudah ada. Gunakan header tersebut sebagai induk.');
                return;
            }
            $newKode = (string)$this->kategori_id;
            $newKodeSort = str_pad($this->kategori_id, 4, '0', STR_PAD_LEFT);
        }

        RabHeader::create([
            'proyek_id'   => $this->proyek_id,
            'kategori_id' => $this->kategori_id,
            'parent_id'   => $parentId,
            'kode'        => $newKode,
            'kode_sort'   => $newKodeSort,
            'deskripsi'   => $this->newHeader['deskripsi'],
            'nilai'       => 0,
            'bobot'       => 0,
            // kolom nilai_material & nilai_upah otomatis diisi saat loadData() berikutnya
        ]);

        session()->flash('success', 'Header RAB berhasil ditambahkan.');
        $this->newHeader = ['parent_id' => null, 'deskripsi' => ''];
        $this->dispatch('rabHeaderCreated');
    }

    public function hapusHeader($id)
    {
        $header = RabHeader::find($id);
        if (!$header) return;

        if ($header->rabDetails()->count() > 0 || $header->children()->count() > 0) {
            session()->flash('error', 'Header tidak bisa dihapus karena masih memiliki detail atau sub-header.');
            return;
        }

        $header->delete();
        session()->flash('success', 'Header RAB berhasil dihapus.');
        $this->dispatch('rabHeaderCreated');
    }

    public function startEditHeader($headerId)
    {
        $this->editingHeaderId = $headerId;
        $h = RabHeader::find($headerId);
        if ($h) $this->editingHeaderDescription = $h->deskripsi;
    }

    public function saveHeaderDescription()
    {
        $this->validate(['editingHeaderDescription' => 'required|string|max:255']);

        $h = RabHeader::find($this->editingHeaderId);
        if ($h) {
            $h->deskripsi = $this->editingHeaderDescription;
            $h->save();

            // rekap ulang seluruh root di proyek (aman)
            $roots = RabHeader::where('proyek_id', $this->proyek_id)
                ->whereNull('parent_id')
                ->with('children', 'rabDetails')
                ->get();

            foreach ($roots as $root) {
                $this->updateHeaderAndChildrenTotals($root);
            }

            session()->flash('success', 'Deskripsi header berhasil diperbarui.');
        }

        $this->reset(['editingHeaderId', 'editingHeaderDescription']);
        $this->dispatch('rabHeaderUpdated');
    }

    // =====================
    // AKSI: detail
    // =====================
    public function tambahDetail()
    {
        $this->validate([
            'newItem.header_id'   => 'required',
            'newItem.ahsp_id'     => 'required|exists:ahsp_header,id',
            'newItem.deskripsi'   => 'required|string|max:255',
            'newItem.volume'      => 'required|numeric|min:0.01',
            'newItem.area'        => 'nullable|string|max:255',
            'newItem.spesifikasi' => 'nullable|string',
        ], [
            'newItem.header_id.required' => 'Pilih Sub-Induk (Header).',
            'newItem.ahsp_id.required'   => 'Pilih AHSP.',
            'newItem.ahsp_id.exists'     => 'AHSP tidak valid.',
            'newItem.deskripsi.required' => 'Deskripsi detail harus diisi.',
            'newItem.volume.required'    => 'Volume harus diisi.',
            'newItem.volume.numeric'     => 'Volume harus berupa angka.',
            'newItem.volume.min'         => 'Volume harus lebih besar dari 0.',
            'newItem.area.max'           => 'Area terlalu panjang (maksimal 255 karakter).',
        ]);

        $ahsp   = AhspHeader::find($this->newItem['ahsp_id']);
        $header = RabHeader::find($this->newItem['header_id']);

        if (!$ahsp || !$header) {
            session()->flash('error', 'Data AHSP atau Header tidak ditemukan.');
            return;
        }

        $volume = (float)$this->newItem['volume'];

        // Ambil komponen harga per satuan analisa dari AHSP
        $komp          = $this->getAhspKomponen((int)$ahsp->id);
        $hargaMaterial = (float)$komp['harga_material'];
        $hargaUpah     = (float)$komp['harga_upah'];
        $hargaSatuan   = $hargaMaterial + $hargaUpah; // RAW
        
        $totalMaterial = $hargaMaterial * $volume;
        $totalUpah     = $hargaUpah * $volume;
        $totalGab      = $hargaSatuan * $volume;

        // Penomoran detail dibawah header
        $seq = RabDetail::where('rab_header_id', $header->id)->count() + 1;
        $detailKode = $header->kode . '.' . $seq;
        $detailKodeSort = implode('.', array_map(
            fn($p) => str_pad($p, 4, '0', STR_PAD_LEFT), explode('.', $detailKode)
        ));

        RabDetail::create([
            'proyek_id'       => $this->proyek_id,
            'rab_header_id'   => $header->id,
            'ahsp_id'         => $ahsp->id,
            'kode'            => $detailKode,
            'kode_sort'       => $detailKodeSort,
            'deskripsi'       => $this->newItem['deskripsi'],
            'area'            => $this->newItem['area'] ?: null,
            'spesifikasi'     => $this->newItem['spesifikasi'] ?: null,
            'satuan'          => $komp['satuan'],
            'volume'          => $volume,

            'sumber_harga'    => 'ahsp',
            'harga_material'  => $hargaMaterial,
            'harga_upah'      => $hargaUpah,
            'harga_satuan'    => $hargaSatuan,

            'total_material'  => $totalMaterial,
            'total_upah'      => $totalUpah,
            'total'           => $totalGab,

            'bobot'           => 0,
        ]);

        // Optional: kunci AHSP jika kebijakan Anda demikian
        if (!$ahsp->is_locked) {
            $ahsp->is_locked = true;
            $ahsp->save();
        }

        session()->flash('success', 'Detail RAB berhasil ditambahkan.');
        $this->newItem = [
            'header_id'    => '',
            'ahsp_id'      => '',
            'deskripsi'    => '',
            'volume'       => 1,
            'satuan'       => '',
            'area'         => '',
            'spesifikasi'  => '',
            'harga_satuan' => 0,
        ];
        $this->dispatch('rabDetailUpdated');
    }

    public function startEditDetail($detailId)
    {
        $this->editingDetailId = $detailId;
        $d = RabDetail::find($detailId);
        if (!$d) return;

        $this->editingDetailSpesifikasi = $d->spesifikasi;
        $this->editingDetailVolume      = $d->volume;
        $this->editingDetailSatuan      = $d->satuan;
        $this->editingDetailDeskripsi   = $d->deskripsi;
        $this->editingDetailAhspId      = $d->ahsp_id;
    }

    public function saveDetailChanges()
    {
        $this->validate([
            'editingDetailSpesifikasi' => 'nullable|string',
            'editingDetailVolume'      => 'required|numeric|min:0.01',
            'editingDetailSatuan'      => 'nullable|string',
            'editingDetailDeskripsi'   => 'required|string|max:255',
            'editingDetailAhspId'      => 'required|exists:ahsp_header,id',
        ], [
            'editingDetailAhspId.required' => 'Pilih AHSP.',
            'editingDetailAhspId.exists'   => 'AHSP tidak valid.',
        ]);

        $d = RabDetail::find($this->editingDetailId);
        if (!$d) return;

        $ahspChanged = $d->ahsp_id != $this->editingDetailAhspId;
        $d->spesifikasi = $this->editingDetailSpesifikasi;
        $d->volume      = (float)$this->editingDetailVolume;
        $d->satuan      = $this->editingDetailSatuan;
        $d->deskripsi   = $this->editingDetailDeskripsi;

        if ($ahspChanged) {
            $d->ahsp_id = $this->editingDetailAhspId;
            $komp = $this->getAhspKomponen((int)$this->editingDetailAhspId);
            $d->satuan         = $komp['satuan'];
            $d->deskripsi      = $komp['nama'];
            $d->harga_material = (float)$komp['harga_material'];
            $d->harga_upah     = (float)$komp['harga_upah'];
            $d->harga_satuan   = (float)$komp['harga_gabungan'];
            $d->sumber_harga   = 'ahsp';
        }

        // Hitung ulang total-total
        $vol = (float)$d->volume;
        $hargaPakai = (float)($d->harga_satuan ?? 0);
        $d->total_material = (float)($d->harga_material ?? 0) * $vol;
        $d->total_upah     = (float)($d->harga_upah ?? 0)     * $vol;
        $d->total          = $hargaPakai * $vol;

        $d->save();

        // Rekap header terkait
        $this->rekapHeaderId($d->rab_header_id);

        $this->dispatch('rabDetailUpdated');
        session()->flash('success', 'Detail RAB berhasil diperbarui.');

        $this->reset(['editingDetailId','editingDetailSpesifikasi','editingDetailVolume','editingDetailSatuan','editingDetailDeskripsi','editingDetailAhspId']);
    }

    public function hapusDetail($id)
    {
        $d = RabDetail::find($id);
        if (!$d) return;

        $headerId = $d->rab_header_id;
        $d->delete();

        // Rekap ulang header terkait
        $this->rekapHeaderId($headerId);

        session()->flash('success', 'Detail RAB berhasil dihapus.');
        $this->dispatch('rabDetailUpdated');
    }

    private function rekapHeaderId(int $headerId): void
    {
        $h = RabHeader::with(['children.rabDetails','rabDetails'])->find($headerId);
        if (!$h) return;

        // cari root dari header ini agar rekap konsisten naik ke atas
        $root = $h;
        while ($root->parent_id) {
            $root = RabHeader::with(['children.rabDetails','rabDetails'])->find($root->parent_id) ?? $root;
            if ($root->id === $h->id) break;
        }
        $this->updateHeaderAndChildrenTotals($root);
    }

    // =====================
    // RENDER
    // =====================
    public function render()
    {
        return view('livewire.rab-input');
    }
}
