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
        return [
            new RABHeaderSheet($this->proyekId),
            new RABDetailSheet($this->proyekId),
        ];
    }
}
