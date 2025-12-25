@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('uang-muka-pembelian.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Bayar via BKK</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-4">Pembayaran UM via BKK</h6>

        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('uang-muka-pembelian.bkk.store', $uangMuka->id) }}" method="POST">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tanggal BKK <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
              <select name="coa_id" class="form-select" required>
                <option value="">-- Pilih Akun Kas/Bank --</option>
                @foreach($coaKas as $coa)
                  <option value="{{ $coa->id }}">{{ $coa->no_akun }} — {{ $coa->nama_akun }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
              <select name="metode_pembayaran" id="metode_pembayaran" class="form-select" required onchange="toggleBankFields()">
                <option value="transfer">Transfer</option>
                <option value="cek">Cek</option>
                <option value="tunai">Tunai</option>
                <option value="giro">Giro</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Bank</label>
              <input type="text" name="nama_bank" class="form-control" placeholder="Misal: BCA">
            </div>
          </div>

          <div class="row g-3 mt-2" id="bank-fields">
            <div class="col-md-6">
              <label class="form-label">No. Rekening</label>
              <input type="text" name="no_rekening_bank" class="form-control" placeholder="Nomor rekening tujuan">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Transfer</label>
              <input type="date" name="tanggal_transfer" class="form-control" value="{{ now()->format('Y-m-d') }}">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-12">
              <label class="form-label">No. Bukti Transfer/Cek/Giro</label>
              <input type="text" name="no_bukti_transfer" class="form-control" placeholder="Misal: TRF-123456">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-12">
              <label class="form-label">Keterangan</label>
              <input type="text" class="form-control" value="Pembayaran Uang Muka {{ $uangMuka->no_uang_muka }} — {{ $uangMuka->nama_supplier }}" disabled>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Upload Bukti Bayar (PDF, JPG, PNG)</label>
            <input type="file" name="file_path" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-muted">Max 2MB</small>
          </div>

          <div class="alert alert-info mt-4">
            <i class="link-icon" data-feather="info"></i>
            Total dibayar: <strong>Rp {{ number_format($uangMuka->nominal, 0, ',', '.') }}</strong>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">
              <i data-feather="save"></i> Posting BKK
            </button>
            <a href="{{ route('uang-muka-pembelian.show', $uangMuka->id) }}" class="btn btn-secondary">
              <i data-feather="x"></i> Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Ringkasan UM</h6>
        <table class="table table-sm table-borderless">
          <tr>
            <td><strong>No. UM</strong></td>
            <td>: {{ $uangMuka->no_uang_muka }}</td>
          </tr>
          <tr>
            <td><strong>Supplier</strong></td>
            <td>: {{ $uangMuka->nama_supplier }}</td>
          </tr>
          <tr>
            <td><strong>Tanggal</strong></td>
            <td>: {{ $uangMuka->tanggal->format('d/m/Y') }}</td>
          </tr>
          <tr>
            <td><strong>Nominal</strong></td>
            <td>: Rp {{ number_format($uangMuka->nominal, 0, ',', '.') }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  toggleBankFields();
});

function toggleBankFields() {
  const methodSelect = document.getElementById('metode_pembayaran');
  if (!methodSelect) return;
  const method = methodSelect.value;
  const fields = document.getElementById('bank-fields');
  if (!fields) return;
  fields.style.display = method === 'tunai' ? 'none' : '';
}
</script>
@endsection