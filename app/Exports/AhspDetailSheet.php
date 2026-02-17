<?php

namespace App\Exports;

use App\Models\AhspDetail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AhspDetailSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function collection()
    {
        // Ambil semua AHSP Detail yang terkait dengan AHSP yang digunakan di RAB Detail proyek ini
        $ahspDetails = AhspDetail::whereIn('ahsp_detail.ahsp_id', function($query) {
            $query->select('ahsp_id')
                ->from('rab_detail')
                ->where('proyek_id', $this->proyekId)
                ->whereNotNull('ahsp_id');
        })
        ->join('ahsp_header', 'ahsp_detail.ahsp_id', '=', 'ahsp_header.id')
        ->leftJoin('hsd_material', function($join) {
            $join->on('ahsp_detail.referensi_id', '=', 'hsd_material.id')
                ->whereRaw('ahsp_detail.tipe = ?', ['material']);
        })
        ->leftJoin('hsd_upah', function($join) {
            $join->on('ahsp_detail.referensi_id', '=', 'hsd_upah.id')
                ->whereRaw('ahsp_detail.tipe = ?', ['upah']);
        })
        ->select(
            'ahsp_detail.*', 
            'ahsp_header.kode_pekerjaan as ahsp_kode',
            DB::raw('COALESCE(hsd_material.kode, hsd_upah.kode) as kode_item')
        )
        ->orderBy('ahsp_header.kode_pekerjaan')
        ->orderBy('ahsp_detail.id')
        ->get();

        return $ahspDetails->map(function($detail) {
            return [
                'ahsp_kode'  => $detail->ahsp_kode ?? '',
                'tipe'       => $detail->tipe ?? '',
                'kode_item'  => $detail->kode_item ?? '',
                'koefisien'  => $detail->koefisien ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ahsp_kode',
            'tipe',
            'kode_item',
            'koefisien',
        ];
    }

    public function title(): string
    {
        return 'AHSP_Detail';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, // ahsp_kode
            'B' => 12, // tipe
            'C' => 14, // kode_item
            'D' => 12, // koefisien
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
