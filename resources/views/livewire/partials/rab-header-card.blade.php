@php
  $indentPx = $level * 20;
  if ($level === 0)      { $headerBgClass = 'bg-primary text-white'; }
  elseif ($level === 1)  { $headerBgClass = 'bg-info text-white'; }
  else                   { $headerBgClass = 'bg-secondary text-white'; }
@endphp

<div class="card mb-2 rab-header-card animate__animated animate__fadeInUp animate__faster" style="margin-left: {{ $indentPx }}px;">
  <div class="card-header fw-bold d-flex justify-content-between align-items-center {{ $headerBgClass }}">
    <div>
      @if ($editingHeaderId === $header->id)
        <input type="text"
               wire:model.live="editingHeaderDescription"
               wire:keydown.enter="saveHeaderDescription"
               wire:blur="saveHeaderDescription"
               class="form-control form-control-sm d-inline-block w-auto"
               style="min-width: 240px;">
      @else
        <span wire:click="startEditHeader({{ $header->id }})" class="cursor-pointer">
          <i class="fas fa-folder-open me-2"></i> {{ $header->kode }} - {{ $header->deskripsi }}
        </span>
      @endif
    </div>
    <div class="text-end">
      <span class="badge bg-light text-dark me-2">Material: <span class="fw-bold">Rp {{ number_format($header->nilai_material ?? 0, 0, ',', '.') }}</span></span>
      <span class="badge bg-light text-dark me-2">Upah: <span class="fw-bold">Rp {{ number_format($header->nilai_upah ?? 0, 0, ',', '.') }}</span></span>
      <span class="me-2">Total: <span class="fw-bold">Rp {{ number_format($header->nilai ?? 0, 0, ',', '.') }}</span></span>
      <button type="button"
              class="btn btn-sm btn-danger rounded"
              onclick="if(!confirm('Anda yakin ingin menghapus header ini? Pastikan tidak ada sub-header/detail.')) return;"
              wire:click="hapusHeader({{ $header->id }})">
        <i class="fas fa-trash-alt"></i>
      </button>
    </div>
  </div>

  <div class="card-body p-0">
    @if ($header->rabDetails->isNotEmpty())
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 8%">Kode</th>
              <th style="width: 8%">AHSP ID</th>
              <th style="width: 24%">Deskripsi & <small class="text-muted">Spesifikasi</small></th>
              <th style="width: 7%">Satuan</th>
              <th style="width: 7%" class="text-end">Volume</th>
              <th style="width: 9%" class="text-end">Hrg Material</th>
              <th style="width: 9%" class="text-end">Hrg Jasa</th>
              <th style="width: 9%" class="text-end">Hrg Total</th>
              <th style="width: 9%" class="text-end">Tot Material</th>
              <th style="width: 9%" class="text-end">Tot Jasa</th>
              <th style="width: 7%" class="text-end">Total</th>
              <th style="width: 4%" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @php
              $detailsByArea = $header->rabDetails->groupBy(fn($item) => $item->area ?? '');
            @endphp

            @foreach($detailsByArea as $areaName => $areaDetails)
              <tr class="rab-area-row">
                <td colspan="11" class="bg-light fw-bold text-primary py-2">
                  {{ $areaName !== '' ? strtoupper($areaName) : '' }}
                </td>
              </tr>

              @foreach($areaDetails->sortBy('kode_sort') as $d)
                <tr class="rab-detail-row">
                  <td>{{ $d->kode }}</td>
                  <td>
                    @if($d->ahsp_id)
                      <a href="{{ route('ahsp.show', $d->ahsp_id) }}" target="_blank" title="Lihat detail AHSP">
                        <span class="badge bg-success cursor-pointer">{{ $d->ahsp_id }}</span>
                      </a>
                    @else
                      <span class="badge bg-secondary">Manual</span>
                    @endif
                  </td>

                  {{-- Deskripsi + Spesifikasi di bawahnya --}}
                  <td>
                    @if ($editingDetailId === $d->id)
                      <div class="mb-1">
                        <select
                          wire:model="editingDetailAhspId"
                          class="form-select form-select-sm d-inline-block w-auto"
                          style="min-width:180px;max-width:300px;"
                        >
                          <option value="">-- Pilih AHSP --</option>
                          @foreach (\App\Models\AhspHeader::where('kategori_id', $header->kategori_id)->orderBy('kode_pekerjaan')->get() as $ahsp)
                            <option value="{{ $ahsp->id }}">{{ $ahsp->kode_pekerjaan }} - {{ $ahsp->nama_pekerjaan }}</option>
                          @endforeach
                        </select>
                        @error('editingDetailAhspId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                      </div>
                      <textarea wire:model.live="editingDetailDeskripsi"
                                wire:keydown.enter="saveDetailChanges"
                                wire:blur="saveDetailChanges"
                                class="form-control form-control-sm mb-1"
                                rows="1"></textarea>
                      <textarea wire:model.live="editingDetailSpesifikasi"
                                wire:keydown.enter="saveDetailChanges"
                                wire:blur="saveDetailChanges"
                                class="form-control form-control-sm"
                                rows="1"></textarea>
                    @else
                      <div class="cursor-pointer" wire:click="startEditDetail({{ $d->id }})">
                        <div>{{ $d->deskripsi }}</div>
                        @if($d->spesifikasi)
                          <small class="text-muted">{{ $d->spesifikasi }}</small>
                        @endif
                      </div>
                    @endif
                  </td>

                  <td>
                    @if ($editingDetailId === $d->id)
                      <input type="text"
                             wire:model.live="editingDetailSatuan"
                             wire:keydown.enter="saveDetailChanges"
                             wire:blur="saveDetailChanges"
                             class="form-control form-control-sm d-inline-block w-auto"
                             style="min-width: 72px;">
                    @else
                      <span wire:click="startEditDetail({{ $d->id }})" class="cursor-pointer">{{ $d->satuan }}</span>
                    @endif
                  </td>

                  <td class="text-end">
                    @if ($editingDetailId === $d->id)
                      <input type="number" step="0.001"
                             wire:model.live="editingDetailVolume"
                             wire:keydown.enter="saveDetailChanges"
                             wire:blur="saveDetailChanges"
                             class="form-control form-control-sm text-end"
                             style="min-width: 80px;">
                    @else
                      <span wire:click="startEditDetail({{ $d->id }})" class="cursor-pointer">{{ number_format($d->volume, 3, ',', '.') }}</span>
                    @endif
                  </td>

                  {{-- Harga satuan per komponen + gabungan --}}
                  <td class="text-end">Rp {{ number_format($d->harga_material ?? 0, 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format($d->harga_upah ?? 0, 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format($d->harga_satuan ?? 0, 0, ',', '.') }}</td>

                  {{-- Total per komponen + gabungan --}}
                  <td class="text-end">Rp {{ number_format($d->total_material ?? 0, 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format($d->total_upah ?? 0, 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format($d->total ?? 0, 0, ',', '.') }}</td>

                  {{-- Aksi: Hapus (fixed) --}}
                  <td class="text-center">
                    <button type="button"
                            class="btn btn-sm btn-danger rounded"
                            onclick="if(!confirm('Hapus detail ini?')) return;"
                            wire:click="hapusDetail({{ $d->id }})"
                            wire:loading.attr="disabled">
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

    {{-- Children headers --}}
    @if ($header->children->isNotEmpty())
      <div class="mt-3 pt-3 border-top">
        @foreach($header->children as $childHeader)
          @include('livewire.partials.rab-header-card', ['header' => $childHeader, 'level' => $level + 1])
        @endforeach
      </div>
    @endif
  </div>
</div>
