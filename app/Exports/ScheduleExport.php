<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ScheduleExport implements WithMultipleSheets
{
    protected $proyekId;
    protected $penawaranId;

    public function __construct($proyekId, $penawaranId)
    {
        $this->proyekId = $proyekId;
        $this->penawaranId = $penawaranId;
    }

    public function sheets(): array
    {
        return [
            new ScheduleMetaSheet($this->proyekId, $this->penawaranId),
            new ScheduleSetupSheet($this->proyekId, $this->penawaranId),
            new ScheduleDetailSheet($this->proyekId, $this->penawaranId),
        ];
    }
}
