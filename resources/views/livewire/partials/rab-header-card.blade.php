{{-- resources/views/livewire/partials/rab-header-card.blade.php --}}
@props(['header', 'level' => 0])

{{-- Style indentasi untuk card keseluruhan --}}
@php
    $indentPx = $level * 0; // Indentasi 20px per level
    $cardBgClass = $level % 2 == 0 ? 'bg-white' : 'bg-light'; // Warna latar bergantian
    $headerBgClass = '';
    if ($level == 0) $headerBgClass = 'bg-primary text-white';
    elseif ($level == 1) $headerBgClass = 'bg-info text-white';
    else $headerBgClass = 'bg-secondary text-white';
@endphp

<div class="card mb-2 rab-header-card animate__animated animate__fadeInUp animate__faster {{ $cardBgClass }}" style="margin-left: {{ $indentPx }}px;">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center {{ $headerBgClass }}">
        {{-- Indentasi untuk teks kode dan deskripsi --}}
        <div>
            {{-- {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level * 0) !!} --}} {{-- Dihapus karena margin-left sudah menangani indentasi --}}
            @if ($editingHeaderId === $header->id)
                {{-- Tampilkan input field jika header ini sedang diedit --}}
                <input type="text"
                       wire:model.live="editingHeaderDescription"
                       wire:keydown.enter="saveHeaderDescription"
                       wire:blur="saveHeaderDescription"
                       class="form-control form-control-sm d-inline-block w-auto"
                       style="min-width: 200px;">
            @else
                {{-- Tampilkan teks biasa dan aktifkan mode edit saat diklik --}}
                <span wire:click="startEditHeader({{ $header->id }})" class="cursor-pointer">
                    <i class="fas fa-folder-open me-2"></i> {{ $header->kode }} - {{ $header->deskripsi }}
                </span>
            @endif
        </div>
        <div class="text-end">
            <span class="me-2">Total: <span class="fw-bold">Rp {{ number_format($header->nilai ?? 0, 0, ',', '.') }}</span></span>
            <button class="btn btn-sm btn-danger rounded" wire:click="hapusHeader({{ $header->id }})" wire:confirm="Anda yakin ingin menghapus header ini? Semua sub-header dan detail di dalamnya juga akan dihapus.">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div> {{-- End card-header --}}

    <div class="card-body p-0">
        @if ($header->rabDetails->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%">Kode</th>
                            <th style="width: 25%">Deskripsi</th>
                            <th style="width: 20%">Spesifikasi</th>
                            <th style="width: 8%">Satuan</th>
                            <th style="width: 8%" class="text-end">Volume</th>
                            <th style="width: 12%" class="text-end">Harga Satuan</th>
                            <th style="width: 12%" class="text-end">Total</th>
                            <th style="width: 7%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $detailsByArea = $header->rabDetails->groupBy(function($item) {
                                return $item->area ?? ''; // Group by area, default to 'Tanpa Area'
                            });
                        @endphp

                        @foreach($detailsByArea as $areaName => $areaDetails)
                            <tr class="rab-area-row">
                                <td colspan="8" class="bg-light fw-bold text-primary py-2">
                                     {{ $areaName }}
                                </td>
                            </tr>
                            @foreach($areaDetails->sortBy('kode_sort') as $d)
                                <tr class="rab-detail-row">
                                    <td>{{ $d->kode }}</td>
                                    {{-- Deskripsi Detail --}}
                                    <td>
                                        @if ($editingDetailId === $d->id)
                                            <textarea wire:model.live="editingDetailDeskripsi"
                                                      wire:keydown.enter="saveDetailChanges"
                                                      wire:blur="saveDetailChanges"
                                                      class="form-control form-control-sm"
                                                      rows="1"></textarea>
                                        @else
                                            <span wire:click="startEditDetail({{ $d->id }})" class="cursor-pointer">
                                                {{ $d->deskripsi }}
                                            </span>
                                        @endif
                                    </td>
                                    {{-- Spesifikasi --}}
                                    <td>
                                        @if ($editingDetailId === $d->id)
                                            <textarea wire:model.live="editingDetailSpesifikasi"
                                                      wire:keydown.enter="saveDetailChanges"
                                                      wire:blur="saveDetailChanges"
                                                      class="form-control form-control-sm"
                                                      rows="1"></textarea>
                                        @else
                                            <span wire:click="startEditDetail({{ $d->id }})" class="cursor-pointer">
                                                {{ $d->spesifikasi }}
                                            </span>
                                        @endif
                                    </td>
                                    {{-- Satuan --}}
                                    <td>
                                        @if ($editingDetailId === $d->id)
                                            <input type="text"
                                                   wire:model.live="editingDetailSatuan"
                                                   wire:keydown.enter="saveDetailChanges"
                                                   wire:blur="saveDetailChanges"
                                                   class="form-control form-control-sm d-inline-block w-auto"
                                                   style="min-width: 80px;">
                                        @else
                                            <span wire:click="startEditDetail({{ $d->id }})" class="cursor-pointer">
                                                {{ $d->satuan }}
                                            </span>
                                        @endif
                                    </td>
                                    {{-- Volume --}}
                                    <td class="text-end">
                                        @if ($editingDetailId === $d->id)
                                            <input type="number" step="0.01"
                                                   wire:model.live="editingDetailVolume"
                                                   wire:keydown.enter="saveDetailChanges"
                                                   wire:blur="saveDetailChanges"
                                                   class="form-control form-control-sm text-end"
                                                   style="min-width: 80px;">
                                        @else
                                            <span wire:click="startEditDetail({{ $d->id }})" class="cursor-pointer">
                                                {{ number_format($d->volume, 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($d->total, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-danger rounded" wire:click="hapusDetail({{ $d->id }})">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($header->children->isNotEmpty())
            <div class="mt-3 pt-3 border-top">
                @foreach($header->children as $childHeader)
                    @include('livewire.partials.rab-header-card', ['header' => $childHeader, 'level' => $level + 1])
                @endforeach
            </div>
        @endif
    </div>
</div>
