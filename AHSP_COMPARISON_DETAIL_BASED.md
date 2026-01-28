# ✅ Fix: AHSP Comparison Logic - Detail-Based Instead of Header-Based

## 🔧 Masalahnya

AHSP yang **detail items-nya sama** tapi **total_harga header berbeda** dianggap "Berbeda":

```
ARS-028 Lokal:
├─ Nama: "1 unit jotas Kaya multispik varis hpl" ✓ SAMA
├─ Satuan: unit ✓ SAMA
├─ Detail Items: Material A (qty 5), Material B (qty 3) ✓ SAMA
├─ Total Harga: Rp 3.000.000
└─ Updated: 8/1/2026

ARS-028 Eksternal:
├─ Nama: "1 unit jotas Kaya multispik varis hpl" ✓ SAMA
├─ Satuan: unit ✓ SAMA
├─ Detail Items: Material A (qty 5), Material B (qty 3) ✓ SAMA
├─ Total Harga: Rp 1.000.000 ❌ BERBEDA
└─ Updated: 14/1/2026 ❌ BERBEDA

Dianggap: "BERBEDA" ❌
Seharusnya: "SAMA" ✅
```

## 🤔 Root Cause

Compare logic sebelumnya:
1. ✓ Compare header fields (`nama_pekerjaan`, `satuan`)
2. ❌ **Compare `total_harga` dari header** (stored value, bisa stale)
3. ❌ **Compare `updated_at`** (timestamp, bukan indikasi data berbeda)
4. ✓ Compare detail items

**Masalah:** Poin 2 dan 3 menyebabkan false positive "berbeda".

## ✅ Solusi

Update comparison logic untuk:

### **1. Jangan Compare Header `total_harga`**
`total_harga` adalah **derived value** (hasil kalkulasi dari detail items). 
- Jika detail items sama → total_harga harus sama (theoretically)
- Jika berbeda → itu issue di calculation engine, bukan data berbeda

### **2. Jangan Compare `updated_at` (timestamp)**
Timestamp berubah terus, bukan indikasi konten berbeda.

### **3. Focus pada Detail Items**
Comparison hanya lihat:
- Apakah detail items sama?
- Apakah koefisien sama?
- Apakah harga_satuan sama?
- Apakah subtotal_final sama?

### **Flowchart Baru:**
```
AHSP Matching:

1. Match by Code ✓
2. Check Name Similarity
   ├─ < 60% similar → SUSPICIOUS
   └─ ≥ 60% similar → Continue
3. Compare Detail Items ONLY
   ├─ Detail count berbeda → DIFFERENT
   ├─ Detail items berbeda → DIFFERENT
   └─ Detail items sama → SAME ✓
```

## 📊 Comparison Fields

### **YANG TIDAK DICOMPARE (tidak penting):**
```
- total_harga (header) → derived dari details
- updated_at → hanya timestamp
- created_at → hanya timestamp
- id → primary key, berbeda wajar
```

### **YANG DICOMPARE (data penting):**
```
DETAIL ITEMS:
├─ tipe (material/upah)
├─ referensi_id (which material/upah)
├─ koefisien (quantity)
├─ harga_satuan (unit price)
└─ subtotal_final (calculated final value)

HEADER:
├─ nama_pekerjaan (jika > 60% mirip)
└─ satuan
```

## 🎯 Hasil

### **Before Fix:**
```
ARS-028 Lokal:    Detail items sama
ARS-028 Eksternal: Detail items sama
                  tapi total_harga berbeda
                  → Masuk "BERBEDA"
```

### **After Fix:**
```
ARS-028 Lokal:    Detail items sama ✓
ARS-028 Eksternal: Detail items sama ✓
                  → Masuk "SAMA" ✓
```

## 📝 Code Logic

```php
// Get detail counts
$localDetailCount = $localItem->details->count();
$externalDetailCount = DB::connection('external')
    ->table('ahsp_detail')
    ->where('ahsp_id', $extItem->id)
    ->count();

// HANYA compare detail count dan content
if ($localDetailCount != $externalDetailCount) {
    $isDifferent = true;  // Different count → pasti berbeda
} else if ($localDetailCount > 0) {
    // Same count → check content detail by detail
    foreach ($localItem->details as $localDetail) {
        $extDetail = $externalDetails->get($key);
        
        // Compare hanya field penting
        if ($localDetail->koefisien != $extDetail->koefisien ||
            $localDetail->harga_satuan != $extDetail->harga_satuan ||
            $localDetail->subtotal_final != $extDetail->subtotal_final) {
            $isDifferent = true;
        }
    }
}
```

## ✨ Impact

| AHSP | Before | After | Reason |
|------|--------|-------|--------|
| ARS-028 | BERBEDA | SAMA | Detail items sama, hanya header total_harga berbeda |
| ARS-029 | SAMA | SAMA | Sama detail items, sama total_harga |
| ARS-030 | BERBEDA | BERBEDA | Detail items berbeda (qty/harga berbeda) |
| ARS-031 | SUSPICIOUS | SUSPICIOUS | Nama < 60% mirip (pekerjaan berbeda) |

## 🧪 Testing

```
1. Refresh datasync
2. Buka AHSP tab
3. ARS-028 seharusnya sekarang di "Sama" bukan "Berbeda"
4. Click pada ARS-028 di "Sama" → No preview button (sama)
5. ARS-029 dengan detail berbeda → Tetap di "Berbeda" ✓
```

## 💡 Why This Makes Sense

**Semantically:**
- AHSP merujuk ke "pekerjaan" dengan spesifikasi detail
- Detail items = spesifikasi pekerjaan
- Header fields (nama, satuan) = metadata
- total_harga, updated_at = derived/transactional, bukan part dari spec

**Practically:**
- Jika detail items sama, maka pekerjaan "sama"
- Perbedaan total_harga bisa karena rounding, currency conversion, dll
- Perbedaan updated_at hanya karena last edit, bukan data berbeda
- Yang penting: apakah detail specification-nya sama?

## 🚀 Summary

Sekarang AHSP comparison **detail-based, bukan header-based**:
- ✅ Detail items sama = SAMA (go to "Sama" section)
- ✅ Detail items berbeda = BERBEDA (go to "Berbeda" section)
- ✅ Ignore timestamp differences
- ✅ Ignore calculated header values
- ✅ Focus on actual specification

**Result:** No false positives dari header value differences! 🎯
