<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RabHeader;
use Livewire\Attributes\On; // Import attribute On

class RabSummary extends Component
{
    public $proyek_id;
    public $grandTotal = 0;

    public function mount($proyek_id)
    {
        $this->proyek_id = $proyek_id;
        $this->calculateGrandTotal();
    }

    // Mendengarkan event dari komponen RabInput
    #[On('rabDetailUpdated')]
    public function calculateGrandTotal()
    {
        $this->grandTotal = RabHeader::where('proyek_id', $this->proyek_id)
                                    ->with('rabDetails')
                                    ->get()
                                    ->sum(fn($header) => $header->rabDetails->sum('total'));
    }

    public function render()
    {
        return view('livewire.rab-summary');
    }
}