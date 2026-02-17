<?php

namespace App\Exports;

use App\Models\RabSchedule;
use App\Models\RabPenawaranItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleSetupSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
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
        $schedules = RabSchedule::where('proyek_id', $this->proyekId)
            ->where('penawaran_id', $this->penawaranId)
            ->orderBy('rab_penawaran_item_id')
            ->get();

        // Get item info untuk kode dan deskripsi
        $itemIds = $schedules->pluck('rab_penawaran_item_id')->unique()->filter();
        $items = [];
        
        if ($itemIds->isNotEmpty()) {
            $items = RabPenawaranItem::whereIn('id', $itemIds)
                ->with('rabDetail')
                ->get()
                ->keyBy('id');
        }

        return $schedules->map(function($schedule) use ($items) {
            $item = $items[$schedule->rab_penawaran_item_id] ?? null;
            $kodeItem = '';
            $deskripsiItem = '';
            
            if ($item && $item->rabDetail) {
                $kodeItem = $item->rabDetail->kode ?? '';
                $deskripsiItem = $item->rabDetail->deskripsi ?? '';
            }

            return [
                'penawaran_item_id' => $schedule->rab_penawaran_item_id ?? '',
                'kode_item'         => $kodeItem,
                'deskripsi_item'    => $deskripsiItem,
                'minggu_ke'         => $schedule->minggu_ke ?? '',
                'durasi'            => $schedule->durasi ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'penawaran_item_id',
            'kode_item',
            'deskripsi_item',
            'minggu_ke',
            'durasi',
        ];
    }

    public function title(): string
    {
        return 'Schedule_Setup';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // penawaran_item_id
            'B' => 14, // kode_item
            'C' => 44, // deskripsi_item
            'D' => 12, // minggu_ke
            'E' => 12, // durasi
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
