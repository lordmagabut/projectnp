@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-md-12">
    <h4 class="mb-4">Detail Faktur: {{ $faktur->no_faktur }}</h4>

    <div class="row mb-3">
      <div class="col-md-3">
        <label class="form-label">Tanggal</label>
        <input type="text" class="form-control" value="{{ $faktur->tanggal }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">No Faktur</label>
        <input type="text" class="form-control" value="{{ $faktur->no_faktur }}" readonly>
      </div>
      <div class="col-md-6">
        <label class="form-label">Supplier</label>
        <input type="text" class="form-control" value="{{ $faktur->nama_supplier }}" readonly>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Proyek</label>
        <input type="text" class="form-control" value="{{ $faktur->proyek->nama_proyek ?? '-' }}" readonly>
      </div>
      <div class="col-md-6">
        <label class="form-label">Perusahaan</label>
        <input type="text" class="form-control" value="{{ $faktur->perusahaan->nama_perusahaan ?? '-' }}" readonly>
      </div>
    </div>

    <div class="table-responsive mt-4">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>Kode Item</th>
            <th>Uraian</th>
            <th>Qty</th>
            <th>UOM</th>
            <th class="text-end">Harga</th>
            <th class="text-end">Diskon</th>
            <th class="text-end">PPN</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($faktur->details as $detail)
          <tr>
            <td>{{ $detail->kode_item }}</td>
            <td>{{ $detail->uraian }}</td>
            <td>{{ $detail->qty }}</td>
            <td>{{ $detail->uom }}</td>
            <td class="text-end">{{ number_format($detail->harga, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($detail->diskon_rupiah, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($detail->ppn_rupiah, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($detail->total, 0, ',', '.') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="row justify-content-end mt-4">
      <div class="col-md-3">
        <label class="form-label">Subtotal</label>
        <input type="text" class="form-control text-end" value="{{ number_format($faktur->subtotal, 0, ',', '.') }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Total Diskon</label>
        <input type="text" class="form-control text-end" value="{{ number_format($faktur->total_diskon, 0, ',', '.') }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Total PPN</label>
        <input type="text" class="form-control text-end" value="{{ number_format($faktur->total_ppn, 0, ',', '.') }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Grand Total</label>
        <input type="text" class="form-control text-end" value="{{ number_format($faktur->total, 0, ',', '.') }}" readonly>
      </div>
    </div>

    @if($faktur->file_path)
    <div class="mt-4">
      <label class="form-label">Lampiran Faktur (PDF)</label><br>
      <a href="{{ asset('storage/' . $faktur->file_path) }}" class="btn btn-outline-primary" target="_blank">
        Lihat Lampiran
      </a>
    </div>
    @endif

    @if($faktur->status === 'draft')
  <form action="{{ route('faktur.approve', $faktur->id) }}" method="POST" class="mt-4">
    @csrf
    <button type="submit" class="btn btn-sm btn-primary btn-icon-text me-2" onclick="return confirm('Setujui faktur dan buat jurnal?')">
    <i class="btn-icon-prepend" data-feather="check-circle"></i> Setuju
    </button>
  </form>
    @endif

  </div>
</div>
@endsection
