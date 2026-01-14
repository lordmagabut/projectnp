<?php

namespace App\Exports;

use App\Models\RabDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RABDetailSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function collection()
    {
        $details = RabDetail::where('proyek_id', $this->proyekId)
            ->orderBy('kode_sort')
            ->get();

        return $details->map(function($detail) {
            return [
                'header_kode'    => $detail->header_kode ?? '',
                'kode'           => $detail->kode ?? '',
                'deskripsi'      => $detail->deskripsi ?? '',
                'area'           => $detail->area ?? '',
                'spesifikasi'    => $detail->spesifikasi ?? '',
                'satuan'         => $detail->satuan ?? '',
                'volume'         => $detail->volume ?? 0,
                'harga_material' => $detail->harga_material ?? 0,
                'harga_upah'     => $detail->harga_upah ?? 0,
                'harga_satuan'   => $detail->harga_satuan ?? 0,
                'total_material' => $detail->total_material ?? 0,
                'total_upah'     => $detail->total_upah ?? 0,
                'total'          => $detail->total ?? 0,
                'ahsp_id'        => $detail->ahsp_id ?? '',
                'ahsp_kode'      => $detail->ahsp_kode ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'header_kode',
            'kode',
            'deskripsi',
            'area',
            'spesifikasi',
            'satuan',
            'volume',
            'harga_material',
            'harga_upah',
            'harga_satuan',
            'total_material',
            'total_upah',
            'total',
            'ahsp_id',
            'ahsp_kode',
        ];
    }

    public function title(): string
    {
        return 'RAB_Detail';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // header_kode
            'B' => 12, // kode
            'C' => 44, // deskripsi
            'D' => 22, // area
            'E' => 36, // spesifikasi
            'F' => 10, // satuan
            'G' => 10, // volume
            'H' => 16, // harga_material
            'I' => 16, // harga_upah
            'J' => 16, // harga_satuan
            'K' => 16, // total_material
            'L' => 16, // total_upah
            'M' => 16, // total
            'N' => 10, // ahsp_id
            'O' => 14, // ahsp_kode
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
