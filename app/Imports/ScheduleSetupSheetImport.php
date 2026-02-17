<?php

namespace App\Imports;

use App\Models\RabSchedule;
use App\Models\RabPenawaranItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ScheduleSetupSheetImport implements ToCollection, WithHeadingRow
{
    protected $proyekId;
    protected $penawaranId;

    public function __construct($proyekId, $penawaranId)
    {
        $this->proyekId = $proyekId;
        $this->penawaranId = $penawaranId;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            // Clear existing schedule setup for this penawaran
            RabSchedule::where('proyek_id', $this->proyekId)
                ->where('penawaran_id', $this->penawaranId)
                ->delete();

            foreach ($rows as $row) {
                // Skip empty rows
                if (!isset($row['penawaran_item_id']) || trim((string)$row['penawaran_item_id']) === '') {
                    continue;
                }

                $penawaranItemId = (int)$row['penawaran_item_id'];
                $mingguKe = isset($row['minggu_ke']) ? (int)$row['minggu_ke'] : 1;
                $durasi = isset($row['durasi']) ? (int)$row['durasi'] : 1;

                // Validate item exists
                $item = RabPenawaranItem::find($penawaranItemId);
                if (!$item) {
                    continue; // Skip if item not found
                }

                // Get rab_header_id from item's rab_detail
                $rabHeaderId = null;
                if ($item->rabDetail) {
                    $rabHeaderId = $item->rabDetail->rab_header_id;
                }

                RabSchedule::create([
                    'proyek_id' => $this->proyekId,
                    'penawaran_id' => $this->penawaranId,
                    'rab_header_id' => $rabHeaderId,
                    'rab_penawaran_item_id' => $penawaranItemId,
                    'minggu_ke' => $mingguKe,
                    'durasi' => $durasi,
                ]);
            }
        });
    }
}
