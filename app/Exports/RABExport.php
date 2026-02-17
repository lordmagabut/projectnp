<?php

namespace App\Exports;

use App\Models\RabHeader;
use App\Models\RabDetail;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RABExport implements WithMultipleSheets
{
    protected $proyekId;

    public function __construct($proyekId)
    {
        $this->proyekId = $proyekId;
    }

    public function sheets(): array
    {
        // Urutan sheet sama seperti template import: HSD → AHSP → RAB
        return [
            new HsdMaterialSheet($this->proyekId),
            new HsdUpahSheet($this->proyekId),
            new AhspHeaderSheet($this->proyekId),
            new AhspDetailSheet($this->proyekId),
            new RABHeaderSheet($this->proyekId),
            new RABDetailSheet($this->proyekId),
        ];
    }
}
