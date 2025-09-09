<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RabHeader;
use Livewire\Attributes\On; // Pastikan ini ada

class RabSummaryByCategory extends Component
{
    public $proyek_id;
    public $categorySummaries = []; // Properti untuk menyimpan ringkasan per kategori

    public function mount($proyek_id)
    {
        $this->proyek_id = $proyek_id;
        $this->loadCategorySummaries();
    }

    #[On('rabHeaderCreated')] // Dengar event dari RabInput
    #[On('rabDetailUpdated')] // Dengar event dari RabInput
    public function loadCategorySummaries()
    {
        // Muat semua RabHeader level teratas (induk utama) untuk proyek ini
        // Eager load relasi 'kategori' untuk mendapatkan nama kategori
        $topLevelHeaders = RabHeader::with('kategori')
            ->where('proyek_id', $this->proyek_id)
            ->whereNull('parent_id') // Hanya ambil header level teratas
            ->orderBy('kode_sort')
            ->get();

        $summaries = [];
        foreach ($topLevelHeaders as $header) {
            // Nilai total header sudah dihitung dan disimpan di kolom 'nilai' oleh RabInput
            $total = $header->nilai ?? 0;

            // Kelompokkan berdasarkan kategori
            $categoryName = $header->kategori->nama_kategori ?? 'Tanpa Kategori'; // Asumsi ada relasi kategori

            if (!isset($summaries[$categoryName])) {
                $summaries[$categoryName] = [
                    'name' => $categoryName,
                    'total' => 0,
                    'headers' => [],
                ];
            }

            $summaries[$categoryName]['total'] += $total;
            $summaries[$categoryName]['headers'][] = [
                'kode' => $header->kode,
                'deskripsi' => $header->deskripsi,
                'total' => $total,
            ];
        }

        $this->categorySummaries = $summaries;
    }

    public function render()
    {
        return view('livewire.rab-summary-by-category');
    }
}