<?php

namespace App\Imports;

use App\Models\RabScheduleMeta;
use App\Models\RabScheduleDetail;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ScheduleImport implements WithMultipleSheets
{
    protected $proyekId;
    protected $penawaranId;
    public $errors = [];
    public $warnings = [];

    public function __construct($proyekId, $penawaranId)
    {
        $this->proyekId = $proyekId;
        $this->penawaranId = $penawaranId;
    }

    public function sheets(): array
    {
        return [
            'Schedule_Meta'   => new ScheduleMetaSheetImport($this->proyekId, $this->penawaranId),
            'Schedule_Setup'  => new ScheduleSetupSheetImport($this->proyekId, $this->penawaranId),
            'Schedule_Detail' => new ScheduleDetailSheetImport($this->proyekId, $this->penawaranId),
        ];
    }
}
