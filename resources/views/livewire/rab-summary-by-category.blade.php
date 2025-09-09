{{-- resources/views/livewire/rab-summary-by-category.blade.php --}}
<div class="card animate__animated animate__fadeInUp animate__faster">
    <div class="card-header bg-info text-white">
        <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i> Ringkasan Per Kategori</h5>
    </div>
    <div class="card-body p-0">
        @if (empty($categorySummaries))
            <p class="text-center text-muted py-5 mb-0">
                <i class="fas fa-info-circle fa-2x mb-3"></i><br>Belum ada ringkasan kategori.<br>Tambahkan RAB untuk melihat ringkasan.
            </p>
        @else
            <ul class="list-group list-group-flush">
                @foreach($categorySummaries as $categoryName => $summary)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                        <span class="fw-bold text-dark">{{ $summary['name'] }}</span>
                        <span class="fw-bold text-primary">Rp {{ number_format($summary['total'], 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
