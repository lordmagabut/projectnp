# 🔴 BUG: Duplikasi Harga Material & Jasa di Filter Excel-like

## Masalah
Pada filter seperti Excel di **RAB Proyek** dan **RAB Penawaran**, item dengan hanya jasa (tanpa material) menampilkan harga 2 kali:
- Contoh: Item 2.1.69 -> Hrg Material = 1000, Hrg Jasa = 1000 (padahal seharusnya ada hanya Jasa)

## Root Cause

### 1. **Tab RAB Proyek** (`resources/views/proyek/partials/tab_rab.blade.php` baris 277-281)

```php
// ❌ SALAH - Mengambil harga_satuan sebagai material fallback
$unitMat = (float)($d->harga_material_penawaran_item ?? $d->harga_material ?? $d->harga_satuan ?? 0);
$unitJasa = (float)($d->harga_upah_penawaran_item ?? $d->harga_upah ?? 0);

if ($unitMat == 0 && isset($d->harga_satuan) && (float)$d->harga_satuan > 0) {
    $unitMat = (float)$d->harga_satuan;  // ← DUPLIKASI TERJADI DI SINI
}
```

### **Skenario Duplikasi:**
Jika item hanya punya jasa (misalnya "Biaya Pengiriman"):
- `harga_material = NULL` atau `0`
- `harga_upah = 1000` (jasa)
- `harga_satuan = harga_material + harga_upah = 1000` (per migration)

**Hasil:**
```
$unitMat  = (NULL ?? NULL ?? 1000) = 1000  ← SALAH! Ini bukan material, ini jasa
$unitJasa = (NULL ?? 1000 ?? 0) = 1000     ← BENAR
```
→ Output: **Harga Material = 1000, Harga Jasa = 1000** (DUPLIKAT!)

## Penyebab Fallback Logic Salah

Database migration (2025_09_13_074748):
```sql
harga_satuan = COALESCE(harga_satuan_manual, COALESCE(harga_material,0) + COALESCE(harga_upah,0))
```

**Asumsi yang salah dalam kode:**
> Jika `harga_material` kosong, ambil dari `harga_satuan`

**Faktanya:**
> `harga_satuan` adalah HASIL dari material + jasa, bukan material standalone

## Solusi

### Perbaikan 1: Tab RAB Proyek
**File:** `resources/views/proyek/partials/tab_rab.blade.php` baris 277-281

```php
// ✅ BENAR - Gunakan HANYA field material/jasa, jangan ambil harga_satuan
$unitMat  = (float)($d->harga_material_penawaran_item ?? $d->harga_material ?? 0);
$unitJasa = (float)($d->harga_upah_penawaran_item ?? $d->harga_upah ?? 0);
// Hapus fallback ke harga_satuan - itu bukan material!
```

### Perbaikan 2: Verifikasi RAB Penawaran
**File:** `resources/views/rab_penawaran/show.blade.php` baris 509-514

Status: ✅ **SUDAH BENAR**
```php
$unitMat  = (float)($it->harga_material_penawaran_item ?? 0);
$unitJasa = (float)($it->harga_upah_penawaran_item ?? 0);
// Tidak ada fallback ke harga_satuan - SEHAH!
```

## Testing Checklist

- [ ] Filter item 2.1.69 di RAB Proyek
  - Verifikasi: Jika hanya jasa, Material = 0, Jasa > 0
- [ ] Filter item lain dengan material saja
  - Verifikasi: Material > 0, Jasa = 0
- [ ] Filter item dengan material + jasa
  - Verifikasi: Material > 0, Jasa > 0
- [ ] Hitung total dalam filter
  - Verifikasi: Total = (Material + Jasa) per item

## Impact
- **Severity:** HIGH (data calculation error)
- **Affected:** RAB Proyek filter Excel-like
- **Scope:** Display/calculation, bukan database
- **User-facing:** Ya (filter menampilkan data salah)
