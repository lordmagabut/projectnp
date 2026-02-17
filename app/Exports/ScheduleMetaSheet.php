<?php

namespace App\Exports;

use App\Models\RabScheduleMeta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleMetaSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $proyekId;
    protected $penawaranId;

    public function __construct($proyekId, $penawaranId)
    {
        $this->proyekId = $proyekId;
        $this->penawaranId = $penawaranId;
    }

    public function collection()
    {
        $meta = RabScheduleMeta::where('proyek_id', $this->proyekId)
            ->where('penawaran_id', $this->penawaranId)
            ->first();

        if (!$meta) {
            // Return empty row if no meta exists
            return collect([[
                '',
                '',
                '',
                '',
                ''
            ]]);
        }

        return collect([[
            $meta->proyek_id ?? '',
            $meta->penawaran_id ?? '',
            $meta->start_date ?? '',
            $meta->end_date ?? '',
            $meta->total_weeks ?? '',
        ]]);
    }

    public function headings(): array
    {
        return [
            'proyek_id',
            'penawaran_id',
            'start_date',
            'end_date',
            'total_weeks',
        ];
    }

    public function title(): string
    {
        return 'Schedule_Meta';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // proyek_id
            'B' => 14, // penawaran_id
            'C' => 14, // start_date
            'D' => 14, // end_date
            'E' => 14, // total_weeks
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
