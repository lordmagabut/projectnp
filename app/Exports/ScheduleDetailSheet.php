<?php

namespace App\Exports;

use App\Models\RabScheduleDetail;
use App\Models\RabPenawaranItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleDetailSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
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
        $details = RabScheduleDetail::where('proyek_id', $this->proyekId)
            ->where('penawaran_id', $this->penawaranId)
            ->orderBy('minggu_ke')
            ->orderBy('rab_penawaran_item_id')
            ->get();

        // Get item info untuk kode dan deskripsi
        $itemIds = $details->pluck('rab_penawaran_item_id')->unique()->filter();
        $items = [];
        
        if ($itemIds->isNotEmpty()) {
            $items = RabPenawaranItem::whereIn('id', $itemIds)
                ->with('rabDetail')
                ->get()
                ->keyBy('id');
        }

        return $details->map(function($detail) use ($items) {
            $item = $items[$detail->rab_penawaran_item_id] ?? null;
            $kodeItem = '';
            $deskripsiItem = '';
            
            if ($item && $item->rabDetail) {
                $kodeItem = $item->rabDetail->kode ?? '';
                $deskripsiItem = $item->rabDetail->deskripsi ?? '';
            }

            return [
                'penawaran_item_id' => $detail->rab_penawaran_item_id ?? '',
                'kode_item'         => $kodeItem,
                'deskripsi_item'    => $deskripsiItem,
                'minggu_ke'         => $detail->minggu_ke ?? '',
                'bobot_mingguan'    => $detail->bobot_mingguan ?? 0,
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
            'bobot_mingguan',
        ];
    }

    public function title(): string
    {
        return 'Schedule_Detail';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // penawaran_item_id
            'B' => 14, // kode_item
            'C' => 44, // deskripsi_item
            'D' => 12, // minggu_ke
            'E' => 16, // bobot_mingguan
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
