<?php

namespace App\Exports;

use App\Models\RabHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RABHeaderSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function collection()
    {
        $headers = RabHeader::where('proyek_id', $this->proyekId)
            ->orderBy('kode_sort')
            ->get();

        return $headers->map(function($header) {
            return [
                'kategori_id'  => $header->kategori_id ?? '',
                'parent_kode'  => $header->parent_kode ?? '',
                'kode'         => $header->kode ?? '',
                'deskripsi'    => $header->deskripsi ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'kategori_id',
            'parent_kode',
            'kode',
            'deskripsi',
        ];
    }

    public function title(): string
    {
        return 'RAB_Header';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // kategori_id
            'B' => 14, // parent_kode
            'C' => 14, // kode
            'D' => 44, // deskripsi
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
