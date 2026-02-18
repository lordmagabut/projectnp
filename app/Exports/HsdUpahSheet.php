<?php

namespace App\Exports;

use App\Models\HsdUpah;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HsdUpahSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function collection()
    {
        // Ambil semua HSD Upah yang digunakan di AHSP yang terhubung dengan RAB Detail proyek ini
        // Optimized: Direct join instead of whereIn subquery for better performance
        $upahs = HsdUpah::join('ahsp_detail', function($join) {
                $join->on('hsd_upah.id', '=', 'ahsp_detail.referensi_id')
                     ->where('ahsp_detail.tipe', '=', 'upah');
            })
            ->join('ahsp_header', 'ahsp_detail.ahsp_id', '=', 'ahsp_header.id')
            ->join('rab_detail', 'rab_detail.ahsp_id', '=', 'ahsp_header.id')
            ->where('rab_detail.proyek_id', $this->proyekId)
            ->select('hsd_upah.*')
            ->distinct()
            ->orderBy('hsd_upah.kode')
            ->get();

        return $upahs->map(function($upah) {
            return [
                'kode_item'    => $upah->kode ?? '',
                'nama_item'    => $upah->jenis_pekerja ?? '',
                'satuan'       => $upah->satuan ?? '',
                'harga_satuan' => $upah->harga_satuan ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'kode_item',
            'nama_item',
            'satuan',
            'harga_satuan',
        ];
    }

    public function title(): string
    {
        return 'HSD_Upah';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // kode_item
            'B' => 30, // nama_item
            'C' => 10, // satuan
            'D' => 16, // harga_satuan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
