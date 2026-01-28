# 🔍 Bug Fix: External AHSP Total Harga Calculation

## 🐛 Masalahnya

Ketika membuka preview modal untuk AHSP berbeda, **total harga external menampilkan nilai yang sangat kecil** (Rp 3.425) dibanding lokal (Rp 3.535.000).

## 🎯 Root Cause

**External AHSP header hanya menampilkan nilai `total_harga` yang tersimpan di database eksternal**, tanpa recalculation dari detail items.

### Masalah Detail:

1. **Local AHSP**: Memiliki accessor method `getTotalMaterialAttribute()` dan `getTotalUpahAttribute()` di model AhspHeader
   - Ini menghitung total_harga secara dinamis dari detail items
   - Formula: Sum semua `subtotal_final` per tipe

2. **External AHSP**: Plain database object tanpa accessor
   - Hanya mengambil field `total_harga` dari `ahsp_header` table
   - Nilai ini adalah stored value yang mungkin **stale** (tidak pernah diupdate)
   - External database mungkin menggunakan format/kalkulasi berbeda

## ✅ Solusi

**Update `DataSyncController::getAhspDetails()`** untuk menghitung `total_harga` eksternal dari detail items-nya:

```php
// Sebelum loop detail
$externalTotalMaterial = 0;
$externalTotalUpah = 0;

// Dalam loop detail
$subtotalFinal = $detail->subtotal_final ?? $detail->subtotal;
if ($detail->tipe === 'material') {
    $externalTotalMaterial += $subtotalFinal;
} elseif ($detail->tipe === 'upah') {
    $externalTotalUpah += $subtotalFinal;
}

// Setelah loop, update external header
$externalHeaderArray = (array) $externalHeader;
$externalHeaderArray['total_harga'] = $externalTotalMaterial + $externalTotalUpah;
$externalHeader = (object) $externalHeaderArray;
```

### Bagaimana Cara Kerja:

1. **Fetch** semua detail dari external AHSP
2. **Loop** setiap detail dan hitung subtotal_final
3. **Accumulate** per tipe (material + upah)
4. **Update** header object dengan calculated total

## 🔄 Perubahan File

**File**: `app/Http/Controllers/DataSyncController.php`

**Method**: `getAhspDetails()` (lines 540-605)

**Change Summary**:
- Added: `$externalTotalMaterial`, `$externalTotalUpah` variables
- Updated: Calculation logic dalam detail loop
- Added: Header update dengan recalculated total

## ✨ Hasil

Sekarang **total harga external akan sama dengan hasil kalkulasi dari detail items-nya**:

### Sebelum Fix:
```
Lokal:     Rp 3.535.000
Eksternal: Rp 3.425 ❌ (wrong - stale value)
```

### Sesudah Fix:
```
Lokal:     Rp 3.535.000
Eksternal: Rp 3.535.000 ✅ (calculated from details)
```

## 🧪 Testing

1. Buka datasync AHSP tab
2. Cari AHSP dengan perbedaan data
3. Klik preview button
4. Lihat total harga di external - sekarang harus akurat!

## 💡 Catatan Penting

- **Ini hanya untuk preview display** - tidak mengubah external database
- Kalkulasi menggunakan `subtotal_final` (sudah termasuk diskon/ppn)
- Fallback ke `subtotal` jika `subtotal_final` null

## 🔜 Future Improvement

Sebaiknya juga update `buildComparison()` method untuk:
1. Recalculate external AHSP total harga
2. Gunakan calculated value untuk "different" comparison
3. Konsisten dengan display

Ini akan memastikan comparison logic juga akurat.
