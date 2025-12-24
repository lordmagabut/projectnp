@extends('layout.master')

@push('plugin-styles')
    {{-- Tambahkan jika ada gaya khusus --}}
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Profil Perusahaan</h4>
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ company_logo_url($perusahaan) }}" alt="Logo {{ $perusahaan->nama_perusahaan ?? '' }}" style="max-height:80px; max-width:180px; object-fit:contain; margin-right:16px;">
                    <div>
                        <h4 class="mb-1">Profil Perusahaan</h4>
                        <p class="mb-0">Informasi lengkap mengenai perusahaan Anda.</p>
                    </div>
                </div>

                {{-- Alert yang bisa ditutup --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @can('manage perusahaan')
                    @if(!$perusahaan)
                        <div class="alert alert-info" role="alert">
                            Anda belum memiliki profil perusahaan. Silakan tambahkan.
                        </div>
                        <a href="{{ route('perusahaan.create') }}" class="btn btn-primary mb-3">
                            <i class="btn-icon-prepend" data-feather="plus"></i> Tambah Perusahaan
                        </a>
                    @else
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <strong>Nama Perusahaan:</strong> {{ $perusahaan->nama_perusahaan }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Alamat:</strong> {{ $perusahaan->alamat }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Email:</strong> {{ $perusahaan->email }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <strong>No. Telp:</strong> {{ $perusahaan->no_telp }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>NPWP:</strong> {{ $perusahaan->npwp }}
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Tipe Perusahaan:</strong> {{ $perusahaan->tipe_perusahaan }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('perusahaan.edit', $perusahaan->id) }}" class="btn btn-warning me-2"> 
                                <i class="btn-icon-prepend" data-feather="edit"></i> Edit Profil
                            </a>

                            <form action="{{ route('perusahaan.destroy', $perusahaan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus profil perusahaan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="btn-icon-prepend" data-feather="trash"></i> Hapus Profil
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
    <script>
        // Inisialisasi Feather Icons
        feather.replace();
    </script>
@endpush