<?php

namespace App\Imports;

use App\Models\RabScheduleMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ScheduleMetaSheetImport implements ToCollection, WithHeadingRow
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
            foreach ($rows as $row) {
                // Skip empty rows
                if (empty($row['start_date']) && empty($row['end_date'])) {
                    continue;
                }

                $startDate = $row['start_date'] ?? null;
                $endDate = $row['end_date'] ?? null;
                $totalWeeks = isset($row['total_weeks']) ? (int)$row['total_weeks'] : null;

                // Parse dates if they are Excel serial numbers
                if (is_numeric($startDate)) {
                    $startDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($startDate)->format('Y-m-d');
                }
                if (is_numeric($endDate)) {
                    $endDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($endDate)->format('Y-m-d');
                }

                // Auto-calculate total_weeks if not provided
                if (!$totalWeeks && $startDate && $endDate) {
                    $start = \Carbon\Carbon::parse($startDate);
                    $end = \Carbon\Carbon::parse($endDate);
                    $days = $start->diffInDays($end) + 1;
                    $totalWeeks = (int) ceil($days / 7);
                }

                RabScheduleMeta::updateOrCreate(
                    [
                        'proyek_id' => $this->proyekId,
                        'penawaran_id' => $this->penawaranId,
                    ],
                    [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'total_weeks' => $totalWeeks,
                    ]
                );
            }
        });
    }
}
