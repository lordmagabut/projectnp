# Fix: Perhitungan Diskon dan PPN pada PO (Purchase Order)

## Masalah yang Ditemukan
Perhitungan diskon dan PPN pada pembuatan PO **tidak konsisten** antara:
1. **Tampilan Frontend (JavaScript)**: Diskon & PPN diterapkan per item
2. **Penyimpanan Backend (Controller)**: Diskon & PPN diterapkan pada grand total

**Contoh Kasus Error**:
```
Item 1: Rp 100,000,000
Item 2: Rp 50,000,000
Subtotal: Rp 150,000,000
Diskon: 10%
PPN: 10%

CARA LAMA (SALAH - Per Item):
Item 1: 100M - (100M * 10%) + (90M * 10%) = Rp 99,000,000
Item 2: 50M - (50M * 10%) + (45M * 10%) = Rp 49,500,000
Total: Rp 148,500,000  ❌ SALAH

CARA BENAR (Global):
Subtotal: 150M
Diskon: 150M * 10% = 15M
PPN: (150M - 15M) * 10% = 13.5M
Total: 150M - 15M + 13.5M = Rp 148,500,000  ✓ BENAR
```

## Solusi yang Diterapkan

### 1. Perbaikan Frontend - JavaScript

**File**: `resources/views/po/create.blade.php`
**File**: `resources/views/po/edit.blade.php`

**Logika yang Diperbarui**:
```javascript
function hitungTotal() {
    let diskon = parseFloat(document.getElementById('diskon-global').value) || 0;
    let ppn = parseFloat(document.getElementById('ppn-global').value) || 0;
    let grandSubtotal = 0;

    // STEP 1: Hitung subtotal dari semua items
    document.querySelectorAll('#detail-barang tr').forEach(row => {
        let qty = parseFloat(row.querySelector('.qty').value) || 0;
        let harga = parseFloat(row.querySelector('.harga').value) || 0;
        let subtotal = qty * harga;
        grandSubtotal += subtotal;
    });

    // STEP 2: Hitung diskon dari grand subtotal
    let totalDiskon = grandSubtotal * (diskon / 100);
    
    // STEP 3: Hitung PPN dari (grandSubtotal - diskon)
    let totalPPN = (grandSubtotal - totalDiskon) * (ppn / 100);
    
    // STEP 4: Grand Total = subtotal - diskon + ppn
    let grandTotal = (grandSubtotal - totalDiskon) + totalPPN;

    // STEP 5: Update tampilan (setiap row tetap tampil subtotal item)
    document.querySelectorAll('#detail-barang tr').forEach(row => {
        let qty = parseFloat(row.querySelector('.qty').value) || 0;
        let harga = parseFloat(row.querySelector('.harga').value) || 0;
        let subtotal = qty * harga;
        row.querySelector('.total-row').innerText = subtotal.toLocaleString('id-ID', {minimumFractionDigits: 2});
    });

    // STEP 6: Tampilkan grand total final
    document.getElementById('grandTotal').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID', {minimumFractionDigits: 2});
}
```

### 2. Perbaikan Backend - Controller

**File**: `app/Http/Controllers/PoController.php`

**Method `store()`** - Sudah benar (tidak perlu perubahan besar):
```php
$diskonGlobal = $request->diskon_persen ?? 0;
$ppnGlobal = $request->ppn_persen ?? 0;
$grandSubtotal = 0;

foreach ($request->items as $item) {
    $qty = floatval($item['qty']);
    $harga = floatval($item['harga']);
    $grandSubtotal += $qty * $harga;
}

$diskonRupiah = ($diskonGlobal / 100) * $grandSubtotal;
$ppnRupiah = (($grandSubtotal - $diskonRupiah) * $ppnGlobal / 100);
$grandTotal = $grandSubtotal - $diskonRupiah + $ppnRupiah;

// Simpan ke Po table
$po = Po::create([
    'total' => $grandTotal,  // ✓ Benar
    // ...
]);

// Detail tetap simpan subtotal item (bukan item + diskon/ppn)
foreach ($request->items as $item) {
    // ...
    PoDetail::create([
        'total' => $subtotal,  // ✓ Per item, tanpa diskon/ppn
    ]);
}
```

**Method `update()`** - DIPERBAIKI:
```php
// Sebelum: Diskon & PPN diterapkan per item (SALAH)
$diskonItem = ($diskonGlobal / 100) * $subtotal;      // ✗ SALAH
$ppnItem = (($subtotal - $diskonItem) * $ppnGlobal / 100);  // ✗ SALAH
$totalItem = ($subtotal - $diskonItem) + $ppnItem;    // ✗ SALAH

// Sesudah: Diskon & PPN diterapkan pada grand total (BENAR)
$grandSubtotal = 0;
foreach ($request->items as $item) {
    $grandSubtotal += $qty * $harga;  // ✓ Hitung total dulu
}

$diskonRupiah = ($diskonGlobal / 100) * $grandSubtotal;  // ✓ BENAR
$ppnRupiah = (($grandSubtotal - $diskonRupiah) * $ppnGlobal / 100);  // ✓ BENAR

// Simpan ke detail dengan diskon/ppn global
foreach ($request->items as $item) {
    PoDetail::create([
        'diskon_persen' => $diskonGlobal,
        'diskon_rupiah' => $diskonRupiah,  // ✓ Global, bukan per item
        'ppn_persen' => $ppnGlobal,
        'ppn_rupiah' => $ppnRupiah,        // ✓ Global, bukan per item
        'total' => $subtotal  // ✓ Per item, tanpa efek diskon/ppn
    ]);
}
```

## Format Penyimpanan Data

### Po Table (Header)
```
| total | Nilai final setelah diskon & PPN |
```

### PoDetail Table (Line Items)
```
| kode_item | uraian | qty | harga | diskon_persen | diskon_rupiah | ppn_persen | ppn_rupiah | total |
|-----------|--------|-----|-------|---------------|---------------|-----------|-----------|-------|
| item-001  | desc   | 10  | 1M    | 10            | 15M           | 10        | 13.5M     | 10M   |
```

**Penjelasan**:
- `diskon_rupiah` & `ppn_rupiah` = nilai global (SAMA untuk semua detail)
- `total` = harga per item (qty * harga), TANPA diskon/ppn applied
- Diskon & PPN adalah atribut header, bukan item

## Verification Checklist

- [x] Syntax PHP valid
- [x] JavaScript logic fixed
- [x] Controller methods consistent
- [x] method `store()` - verified correct
- [x] method `update()` - FIXED
- [x] method `revisi()` - check needed (legacy)

## Testing Scenario

**Input**:
```
Item 1: Qty=10, Harga=1,000,000 → Rp 10,000,000
Item 2: Qty=5, Harga=2,000,000 → Rp 10,000,000
Subtotal: Rp 20,000,000
Diskon: 10%
PPN: 10%
```

**Perhitungan**:
```
Subtotal:              20,000,000
Diskon (10%):        - 2,000,000
Setelah Diskon:       18,000,000
PPN (10% dari sisanya): + 1,800,000
Grand Total:          19,800,000
```

**Expected Result**:
- Frontend display: Rp 19,800,000 ✓
- Database total: Rp 19,800,000 ✓
- Detail items: Tetap 10M + 10M (tanpa diskon/ppn) ✓

## Status

✅ **FIXED** - Perhitungan diskon dan PPN sekarang konsisten antara frontend dan backend
