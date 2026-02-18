<?php

namespace App\Exports;

use App\Models\HsdMaterial;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HsdMaterialSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function collection()
    {
        // Ambil semua HSD Material yang digunakan di AHSP yang terhubung dengan RAB Detail proyek ini
        // Optimized: Direct join instead of whereIn subquery for better performance
        $materials = HsdMaterial::join('ahsp_detail', function($join) {
                $join->on('hsd_material.id', '=', 'ahsp_detail.referensi_id')
                     ->where('ahsp_detail.tipe', '=', 'material');
            })
            ->join('ahsp_header', 'ahsp_detail.ahsp_id', '=', 'ahsp_header.id')
            ->join('rab_detail', 'rab_detail.ahsp_id', '=', 'ahsp_header.id')
            ->where('rab_detail.proyek_id', $this->proyekId)
            ->select('hsd_material.*')
            ->distinct()
            ->orderBy('hsd_material.kode')
            ->get();

        return $materials->map(function($material) {
            return [
                'kode_item'    => $material->kode ?? '',
                'nama_item'    => $material->nama ?? '',
                'satuan'       => $material->satuan ?? '',
                'harga_satuan' => $material->harga_satuan ?? 0,
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
        return 'HSD_Material';
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
