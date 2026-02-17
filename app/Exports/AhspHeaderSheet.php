<?php

namespace App\Exports;

use App\Models\AhspHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AhspHeaderSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function collection()
    {
        // Ambil semua AHSP yang digunakan di RAB Detail proyek ini
        $ahspHeaders = AhspHeader::whereIn('id', function($query) {
            $query->select('ahsp_id')
                ->from('rab_detail')
                ->where('proyek_id', $this->proyekId)
                ->whereNotNull('ahsp_id');
        })
        ->orderBy('kode_pekerjaan')
        ->get();

        return $ahspHeaders->map(function($ahsp) {
            return [
                'kode_pekerjaan' => $ahsp->kode_pekerjaan ?? '',
                'nama_pekerjaan' => $ahsp->nama_pekerjaan ?? '',
                'satuan'         => $ahsp->satuan ?? '',
                'kategori_id'    => $ahsp->kategori_id ?? '',
                'catatan'        => $ahsp->catatan ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'kode_pekerjaan',
            'nama_pekerjaan',
            'satuan',
            'kategori_id',
            'catatan',
        ];
    }

    public function title(): string
    {
        return 'AHSP_Header';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, // kode_pekerjaan
            'B' => 36, // nama_pekerjaan
            'C' => 10, // satuan
            'D' => 12, // kategori_id
            'E' => 30, // catatan
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
