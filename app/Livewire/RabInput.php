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
    public $kategori_id; // Ini adalah kategori_id proyek
    public $headers;
    public $ahspList; // Daftar AHSP yang akan digunakan untuk dropdown
    public $projectGrandTotal = 0;

    public $newHeader = [
        'parent_id' => null,
        'deskripsi' => '',
    ];

    public $newItem = [
        'header_id' => '',
        'ahsp_id' => '',
        'deskripsi' => '',
        'volume' => 1,
        'satuan' => '', // Tambahkan satuan di newItem
        'area' => '',
        'spesifikasi' => '',
        'harga_satuan' => 0,
    ];

    public $flatHeaders = [];

    public $editingDetailId = null;
    public $editingDetailSpesifikasi = '';
    public $editingDetailVolume = 0;
    public $editingDetailSatuan = '';
    public $editingDetailDeskripsi = '';

    public $editingHeaderId = null;
    public $editingHeaderDescription = '';

    // --- PROPERTI UNTUK FILTER AHSP ---
    public $ahspSearch = ''; // Properti untuk istilah pencarian AHSP
    public $selectedHeaderCategoryId = null; // Menyimpan kategori_id dari header yang dipilih
    // --- AKHIR PROPERTI FILTER AHSP ---


    public function mount($proyek_id, $kategori_id)
    {
        $this->proyek_id = $proyek_id;
        $this->kategori_id = $kategori_id; // Kategori ID proyek
        $this->loadData();
        // Saat mount, inisialisasi selectedHeaderCategoryId dengan kategori_id proyek
        $this->selectedHeaderCategoryId = $kategori_id;
        $this->loadAhspList(); // Muat daftar AHSP awal
    }

    // --- LOGIKA UPDATED PROPERTY ---
    public function updated($propertyName)
    {
        if ($propertyName === 'newItem.header_id') {
            // Reset AHSP search dan selection saat header berubah
            $this->ahspSearch = '';
            $this->newItem['ahsp_id'] = '';
            $this->newItem['deskripsi'] = '';
            $this->newItem['satuan'] = ''; // Reset satuan juga
            $this->newItem['harga_satuan'] = 0; // <- reset juga

            if (!empty($this->newItem['header_id'])) {
                $header = RabHeader::find($this->newItem['header_id']);
                if ($header) {
                    $this->selectedHeaderCategoryId = $header->kategori_id;
                } else {
                    $this->selectedHeaderCategoryId = $this->kategori_id; // Kembali ke kategori proyek jika header tidak ditemukan
                }
            } else {
                // Jika header tidak dipilih, kembali ke kategori proyek
                $this->selectedHeaderCategoryId = $this->kategori_id;
            }
            $this->loadAhspList(); // Muat ulang daftar AHSP dengan filter kategori baru
        } elseif ($propertyName === 'ahspSearch') {
            $this->loadAhspList(); // Muat ulang daftar AHSP dengan filter pencarian baru
        } elseif ($propertyName === 'newItem.ahsp_id') {
            if (!empty($this->newItem['ahsp_id'])) {
                $ahsp = AhspHeader::find($this->newItem['ahsp_id']);
                if ($ahsp) {
                    $this->newItem['deskripsi'] = $ahsp->nama_pekerjaan;
                    $this->newItem['satuan'] = $ahsp->satuan;
        
                    // gunakan harga pembulatan; fallback bila kolom lama kosong
                    $rounded = (int) ($ahsp->total_harga_pembulatan
                        ?? ceil(($ahsp->total_harga ?? 0) / 1000) * 1000);
        
                    $this->newItem['harga_satuan'] = $rounded; // <- untuk ditampilkan di form
                } else {
                    $this->newItem['deskripsi'] = '';
                    $this->newItem['satuan'] = '';
                    $this->newItem['harga_satuan'] = 0;
                    session()->flash('error', 'AHSP yang dipilih tidak ditemukan.');
                }
            } else {
                $this->newItem['deskripsi'] = '';
                $this->newItem['satuan'] = '';
                $this->newItem['harga_satuan'] = 0;
            }
        }
    }
    // --- AKHIR LOGIKA UPDATED PROPERTY ---

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

        $this->projectGrandTotal = RabDetail::whereHas('header', function ($query) {
            $query->where('proyek_id', $this->proyek_id);
        })->sum('total');

        $this->dispatch('projectTotalUpdated', total: $this->projectGrandTotal);

        DB::beginTransaction();
        try {
            foreach ($this->headers as $header) {
                $this->updateHeaderAndChildrenTotals($header);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal memperbarui nilai header: ' . $e->getMessage());
        }

        $this->flatHeaders = $this->generateFlatHeadersForDropdown($this->headers);
        $this->loadAhspList(); // Pastikan AHSP list dimuat ulang setelah data header
    }

    // --- METODE UNTUK MEMUAT DAFTAR AHSP ---
    private function loadAhspList()
    {
        $query = AhspHeader::orderBy('kode_pekerjaan');

        // Filter berdasarkan kategori ID jika ada yang dipilih
        if ($this->selectedHeaderCategoryId) {
            $query->where('kategori_id', $this->selectedHeaderCategoryId);
        }

        // Filter berdasarkan istilah pencarian
        if (!empty($this->ahspSearch)) {
            $searchTerm = '%' . $this->ahspSearch . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('kode_pekerjaan', 'like', $searchTerm)
                  ->orWhere('nama_pekerjaan', 'like', $searchTerm);
            });
        }

        $this->ahspList = $query->get();
    }
    // --- AKHIR METODE MEMUAT DAFTAR AHSP ---


    private function updateHeaderAndChildrenTotals(RabHeader $header)
    {
        $currentHeaderTotal = $header->rabDetails->sum('total');

        foreach ($header->children as $childHeader) {
            $currentHeaderTotal += $this->updateHeaderAndChildrenTotals($childHeader);
        }

        if ($header->nilai !== $currentHeaderTotal) {
            $header->nilai = $currentHeaderTotal;
            $header->save();
        }

        return $currentHeaderTotal;
    }

    private function generateFlatHeadersForDropdown($headers, $level = 0)
    {
        $flatList = [];
        foreach ($headers as $header) {
            $indent = str_repeat('-- ', $level);
            $flatList[] = [
                'id' => $header->id,
                'display_name' => $indent . $header->kode . ' - ' . $header->deskripsi,
            ];

            if ($header->children->isNotEmpty()) {
                $flatList = array_merge($flatList, $this->generateFlatHeadersForDropdown($header->children, $level + 1));
            }
        }
        return $flatList;
    }

    public function tambahHeader()
    {
        $this->validate([
            'newHeader.deskripsi' => 'required|string|max:255',
            'newHeader.parent_id' => 'nullable|exists:rab_header,id',
        ], [
            'newHeader.deskripsi.required' => 'Deskripsi header harus diisi.',
            'newHeader.parent_id.exists' => 'Induk yang dipilih tidak valid.',
        ]);

        $parentHeader = null;
        $parentKode = '';
        $existingChildrenCount = 0;
        $newKode = '';
        $newKodeSort = '';

        if ($this->newHeader['parent_id']) {
            $parentHeader = RabHeader::find($this->newHeader['parent_id']);
            if (!$parentHeader) {
                session()->flash('error', 'Induk yang dipilih tidak ditemukan.');
                return;
            }
            $parentKode = $parentHeader->kode;
            $existingChildrenCount = $parentHeader->children()
                                                    ->where('proyek_id', $this->proyek_id)
                                                    ->where('kategori_id', $this->kategori_id)
                                                    ->count();
            $nextSequence = $existingChildrenCount + 1;
            $newKode = $parentKode . '.' . $nextSequence;
            $newKodeSort = implode('.', array_map(fn($part) => str_pad($part, 4, '0', STR_PAD_LEFT), explode('.', $newKode)));

        } else {
            $existingKategoriRoot = RabHeader::where('proyek_id', $this->proyek_id)
                                              ->where('kategori_id', $this->kategori_id)
                                              ->whereNull('parent_id')
                                              ->where('kode', (string)$this->kategori_id)
                                              ->first();

            if ($existingKategoriRoot) {
                session()->flash('error', 'Header utama untuk kategori ini (Kode: ' . $this->kategori_id . ') sudah ada. Mohon pilih header tersebut sebagai induk jika Anda ingin membuat sub-header.');
                return;
            } else {
                $newKode = (string)$this->kategori_id;
                $newKodeSort = str_pad($this->kategori_id, 4, '0', STR_PAD_LEFT);
            }
        }

        RabHeader::create([
            'proyek_id' => $this->proyek_id,
            'kategori_id' => $this->kategori_id,
            'parent_id' => $this->newHeader['parent_id'] ?: null,
            'kode' => $newKode,
            'kode_sort' => $newKodeSort,
            'deskripsi' => $this->newHeader['deskripsi'],
            'nilai' => 0,
            'bobot' => 0,
        ]);

        session()->flash('success', 'Header RAB berhasil ditambahkan!');
        $this->newHeader = [
            'parent_id' => null,
            'deskripsi' => '',
        ];
        $this->dispatch('rabHeaderCreated');
    }

    public function tambahDetail()
    {
        $this->validate([
            'newItem.header_id' => 'required',
            'newItem.ahsp_id' => 'required|exists:ahsp_header,id',
            'newItem.deskripsi' => 'required|string|max:255',
            'newItem.volume' => 'required|numeric|min:0.01',
            'newItem.area' => 'nullable|string|max:255',
            'newItem.spesifikasi' => 'nullable|string',
        ], [
            'newItem.header_id.required' => 'Pilih Sub-Induk (Header).',
            'newItem.ahsp_id.required' => 'Pilih AHSP.',
            'newItem.ahsp_id.exists' => 'AHSP tidak valid.',
            'newItem.deskripsi.required' => 'Deskripsi detail harus diisi.',
            'newItem.volume.required' => 'Volume harus diisi.',
            'newItem.volume.numeric' => 'Volume harus berupa angka.',
            'newItem.volume.min' => 'Volume harus lebih besar dari 0.',
            'newItem.area.max' => 'Area terlalu panjang (maksimal 255 karakter).',
        ]);

        $ahsp = AhspHeader::find($this->newItem['ahsp_id']);
        $header = RabHeader::find($this->newItem['header_id']);

        if (!$ahsp || !$header) {
            session()->flash('error', 'Data AHSP atau Header tidak ditemukan.');
            return;
        }

        // Ambil harga pembulatan dari AHSP; fallback bila kolom lama kosong
        $harga_satuan = (int) ($ahsp->total_harga_pembulatan
        ?? ceil(($ahsp->total_harga ?? 0) / 1000) * 1000);

        $volume = (float) $this->newItem['volume'];
        $total  = $volume * $harga_satuan;

        $existingDetailsCount = RabDetail::where('rab_header_id', $header->id)->count();
        $nextSequence = $existingDetailsCount + 1;
        $detail_kode = $header->kode . '.' . $nextSequence;
        $detail_kode_sort = implode('.', array_map(fn($part) => str_pad($part, 4, '0', STR_PAD_LEFT), explode('.', $detail_kode)));

        RabDetail::create([
            'proyek_id' => $this->proyek_id,
            'rab_header_id' => $header->id,
            'ahsp_id' => $ahsp->id,
            'kode' => $detail_kode,
            'kode_sort' => $detail_kode_sort,
            'deskripsi' => $this->newItem['deskripsi'],
            'area' => $this->newItem['area'] ?: null,
            'spesifikasi' => $this->newItem['spesifikasi'] ?: null,
            'satuan' => $ahsp->satuan,
            'volume' => $volume,
            'harga_satuan' => $harga_satuan,
            'total' => $total,
            'bobot' => 0,
        ]);

            // --- LOGIKA PENGUNCIAN AHSP DITAMBAHKAN DI SINI ---
            // Pastikan AHSP ada dan belum terkunci sebelum menguncinya
            if ($ahsp && !$ahsp->is_locked) {
                $ahsp->is_locked = true;
                $ahsp->save();
            }
            // --- AKHIR LOGIKA PENGUNCIAN ---        

        session()->flash('success', 'Detail RAB berhasil ditambahkan!');
        $this->newItem = [
            'header_id' => '',
            'ahsp_id' => '',
            'deskripsi' => '',
            'volume' => 1,
            'area' => '',
            'spesifikasi' => '',
            'harga_satuan' => 0,
        ];
        $this->dispatch('rabDetailUpdated');
    }

    public function hapusDetail($id)
    {
        RabDetail::find($id)?->delete();
        session()->flash('success', 'Detail RAB berhasil dihapus!');
        $this->dispatch('rabDetailUpdated');
    }

    public function hapusHeader($id)
    {
        $header = RabHeader::find($id);
        if ($header) {
            if ($header->rabDetails()->count() > 0 || $header->children()->count() > 0) {
                session()->flash('error', 'Header tidak bisa dihapus karena masih memiliki detail atau sub-header.');
                return;
            }
            $header->delete();
            session()->flash('success', 'Header RAB berhasil dihapus!');
            $this->dispatch('rabHeaderCreated');
        }
    }

    public function startEditDetail($detailId)
    {
        $this->editingDetailId = $detailId;
        $detail = RabDetail::find($detailId);
        if ($detail) {
            $this->editingDetailSpesifikasi = $detail->spesifikasi;
            $this->editingDetailVolume = $detail->volume;
            $this->editingDetailSatuan = $detail->satuan;
            $this->editingDetailDeskripsi = $detail->deskripsi;
        }
    }

    public function saveDetailChanges()
    {
        $this->validate([
            'editingDetailSpesifikasi' => 'nullable|string',
            'editingDetailVolume' => 'required|numeric|min:0.01',
            'editingDetailSatuan' => 'nullable|string',
            'editingDetailDeskripsi' => 'required|string|max:255',
        ], [
            'editingDetailVolume.required' => 'Volume harus diisi.',
            'editingDetailVolume.numeric' => 'Volume harus berupa angka.',
            'editingDetailVolume.min' => 'Volume harus lebih besar dari 0.',
            'editingDetailDeskripsi.required' => 'Deskripsi detail harus diisi.',
        ]);

        $detail = RabDetail::find($this->editingDetailId);
        if ($detail) {
            $detail->spesifikasi = $this->editingDetailSpesifikasi;
            $detail->volume = $this->editingDetailVolume;
            $detail->satuan = $this->editingDetailSatuan;
            $detail->deskripsi = $this->editingDetailDeskripsi;

            $detail->total = $detail->volume * $detail->harga_satuan;
            $detail->save();

            $this->dispatch('rabDetailUpdated');
            session()->flash('success', 'Detail RAB berhasil diperbarui.');
        }

        $this->reset(['editingDetailId', 'editingDetailSpesifikasi', 'editingDetailVolume', 'editingDetailSatuan', 'editingDetailDeskripsi']);
    }

    public function startEditHeader($headerId)
    {
        $this->editingHeaderId = $headerId;
        $header = RabHeader::find($headerId);
        if ($header) {
            $this->editingHeaderDescription = $header->deskripsi;
        }
    }

    public function saveHeaderDescription()
    {
        $this->validate([
            'editingHeaderDescription' => 'required|string|max:255',
        ]);

        $header = RabHeader::find($this->editingHeaderId);
        if ($header) {
            $header->deskripsi = $this->editingHeaderDescription;
            $header->save();

            $rootHeaders = RabHeader::where('proyek_id', $this->proyek_id)
                                    ->whereNull('parent_id')
                                    ->with('children', 'rabDetails')
                                    ->get();

            foreach ($rootHeaders as $rootHeader) {
                $this->updateHeaderAndChildrenTotals($rootHeader);
            }

            session()->flash('success', 'Deskripsi header berhasil diperbarui.');
        }

        $this->reset(['editingHeaderId', 'editingHeaderDescription']);
        $this->dispatch('rabHeaderUpdated');
    }

    public function render()
    {
        return view('livewire.rab-input');
    }
}
