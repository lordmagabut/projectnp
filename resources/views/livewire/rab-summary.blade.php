{{-- resources/views/livewire/rab-summary.blade.php --}}
<div class="card text-center animate__animated animate__fadeInUp animate__faster">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave me-2"></i> Total Keseluruhan Proyek</h5>
    </div>
    <div class="card-body py-4">
        <p class="card-text fs-1 fw-bold text-primary mb-0">
            Rp {{ number_format($grandTotal, 0, ',', '.') }}
        </p>
        <small class="text-muted mt-2 d-block">Diperbarui secara real-time.</small>
    </div>
</div>
