<li class="list-group-item">
    <div class="d-flex justify-content-between align-items-center">
        @if ($coa->children->count())
            <a class="fw-bold text-decoration-none" data-bs-toggle="collapse" href="#collapse-{{ $coa->id }}" role="button">
                <i data-feather="folder" class="me-2"></i>{{ $coa->no_akun }} - {{ $coa->nama_akun }}
            </a>
        @else
            <span><i data-feather="file-text" class="me-2"></i>{{ $coa->no_akun }} - {{ $coa->nama_akun }}</span>
        @endif

        <div class="d-flex align-items-center">
            <span class="badge bg-secondary me-3">{{ strtoupper($coa->tipe) }}</span>
            <a href="{{ route('coa.edit', $coa->id) }}" class="btn btn-sm btn-primary me-2">Edit</a>
            <form action="{{ route('coa.destroy', $coa->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
        </div>
    </div>

    @if ($coa->children->count())
        <div class="collapse mt-2" id="collapse-{{ $coa->id }}">
            <ul class="list-group ms-4">
                @foreach ($coa->children as $child)
                    @include('partials._coa-item', ['coa' => $child])
                @endforeach
            </ul>
        </div>
    @endif
</li>
