<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RabHeader;
use App\Models\Proyek;
use Livewire\Attributes\On; // Import attribute On

class RabSummary extends Component
{
    public $proyek_id;
    public $grandTotal = 0;
    public $grandTotalBase = 0;
    public $kontigensiPersen = 0;

    public function mount($proyek_id)
    {
        $this->proyek_id = $proyek_id;
        $this->calculateGrandTotal();
    }

    // Mendengarkan event dari komponen RabInput
    #[On('rabDetailUpdated')]
    public function calculateGrandTotal()
    {
        $this->grandTotalBase = RabHeader::where('proyek_id', $this->proyek_id)
            ->with('rabDetails')
            ->get()
            ->sum(fn($header) => $header->rabDetails->sum('total'));

        $proyek = Proyek::find($this->proyek_id);
        $this->kontigensiPersen = (float) data_get($proyek, 'kontingensi_persen', data_get($proyek, 'persen_kontingensi', 0));

        $factor = 1 + ($this->kontigensiPersen / 100);
        $this->grandTotal = $this->grandTotalBase * $factor;
    }

    public function render()
    {
        return view('livewire.rab-summary');
    }
}