@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Setting</a></li>
    <li class="breadcrumb-item active" aria-current="page">Mapping COA</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="card-title mb-0">Pengaturan Mapping COA (Global)</h6>
        </div>
        
        <p class="text-muted mb-3">
          <i class="link-icon" data-feather="info"></i>
          Mapping ini digunakan sebagai default untuk seluruh transaksi. Anda dapat override per-perusahaan di menu Profil Perusahaan.
        </p>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <form action="{{ route('account-mapping.update') }}" method="POST">
          @csrf
          @method('PUT')
          
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th width="30%">Akun</th>
                  <th width="50%">COA Terpilih</th>
                  <th width="20%">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($data as $key => $info)
                <tr>
                  <td>
                    <strong>{{ $info['label'] }}</strong>
                    <br>
                    <small class="text-muted">{{ $key }}</small>
                  </td>
                  <td>
                    <select name="mappings[{{ $key }}]" class="form-select select2" data-width="100%">
                      <option value="">-- Pilih COA --</option>
                      @foreach($coaList as $coa)
                        <option value="{{ $coa->id }}" 
                          {{ $info['current_coa_id'] == $coa->id ? 'selected' : '' }}>
                          {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                        </option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    @if($info['current_coa'])
                      <span class="badge bg-success">
                        <i class="link-icon" data-feather="check-circle"></i> Sudah di-set
                      </span>
                      <br>
                      <small class="text-muted">
                        {{ $info['current_coa']->no_akun }} - {{ $info['current_coa']->nama_akun }}
                      </small>
                    @else
                      <span class="badge bg-warning">
                        <i class="link-icon" data-feather="alert-triangle"></i> Belum di-set
                      </span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="link-icon" data-feather="save"></i> Simpan Mapping
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('assets/plugins/select2/select2.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    'use strict';
    
    // Initialize Select2
    if ($('.select2').length) {
      $('.select2').select2();
    }
    
    // Initialize Feather Icons
    if (feather) {
      feather.replace();
    }
  });
</script>
@endpush
