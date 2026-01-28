# 🔑 Fix: Detail Item Matching by Content, Not by ID

## 🔧 Masalahnya

Detail items yang **100% identical** tapi memiliki **referensi_id berbeda** dianggap "berbeda":

```
Lokal Detail Item:
├─ Tipe: Material
├─ Referensi ID: 5 (database lokal)
├─ Koefisien: 10
├─ Harga Satuan: 100.000
└─ Subtotal: 1.000.000

Eksternal Detail Item:
├─ Tipe: Material
├─ Referensi ID: 15 (database eksternal - ID berbeda!)
├─ Koefisien: 10 ✓
├─ Harga Satuan: 100.000 ✓
└─ Subtotal: 1.000.000 ✓

Sebelum: Compare by referensi_id → 5 ≠ 15 → Dianggap BERBEDA ❌
Seharusnya: Dianggap SAMA ✓ (karena content sama)
```

## 🤔 Root Cause

Matching detail items menggunakan:
```php
$key = $localDetail->tipe . '-' . $localDetail->referensi_id;
```

**Masalah:**
- `referensi_id` adalah ID di database masing-masing
- Database lokal dan eksternal memiliki ID yang berbeda untuk item yang sama
- Contoh: Material "Cat Tembok" bisa:
  - Lokal: ID 5
  - Eksternal: ID 15
- Padahal material-nya sama!

## ✅ Solusi

Match detail items berdasarkan **content, bukan ID**:

```php
// SEBELUM (ID-based):
$key = $localDetail->tipe . '-' . $localDetail->referensi_id;

// SESUDAH (Content-based):
$key = $localDetail->tipe . '-' . $localDetail->koefisien . '-' . $localDetail->harga_satuan;
```

### **Logika Baru:**
1. Material/Upah dalam lokal dan eksternal **dicocokkan berdasarkan:**
   - Tipe (material/upah)
   - Koefisien (qty/volume)
   - Harga satuan

2. Jika kombinasi (tipe + koefisien + harga) sama → **Same item**
3. Abaikan `referensi_id` (yang bisa berbeda di database berbeda)
4. Bandingkan hanya `subtotal_final` untuk verifikasi

## 📊 Example

### **Scenario: Material sama, ID berbeda**

```
Lokal AHSP:
├─ Detail 1: Material, Qty 10, Harga 50.000 (ID=5)
├─ Detail 2: Material, Qty 20, Harga 75.000 (ID=6)
└─ Detail 3: Upah, Qty 15, Harga 30.000 (ID=3)

Eksternal AHSP:
├─ Detail A: Material, Qty 10, Harga 50.000 (ID=101) ← Same as Detail 1!
├─ Detail B: Material, Qty 20, Harga 75.000 (ID=102) ← Same as Detail 2!
└─ Detail C: Upah, Qty 15, Harga 30.000 (ID=201) ← Same as Detail 3!

SEBELUM: ID berbeda (5≠101, 6≠102, 3≠201) → Dianggap BERBEDA ❌
SESUDAH: Content sama (qty+harga sama) → Dianggap SAMA ✅
```

## 🔄 Matching Key

```
Old Key:  tipe-referensi_id
          Fragile, depends on database IDs

New Key:  tipe-koefisien-harga_satuan
          Robust, depends on actual content
```

## 🎯 When This Matters

| Scenario | Before | After |
|----------|--------|-------|
| Material ID berbeda, qty&harga sama | BERBEDA ❌ | SAMA ✅ |
| Material ID sama, qty berbeda | SAMA ❌ | BERBEDA ✓ |
| Material ID sama, harga berbeda | SAMA ❌ | BERBEDA ✓ |
| Material ID sama, qty&harga sama | SAMA ✓ | SAMA ✓ |

## ✨ Why This Works

**Semantic meaning:**
- Detail item dalam AHSP = "use material X with qty Y at price Z"
- If qty dan price sama → it's the **same item specification**
- Doesn't matter which database ID it came from

**Practical implication:**
- Database lokal dan eksternal bisa punya ID berbeda
- Yang penting: apakah material/upah yang digunakan sama?
- Apakah quantity sama?
- Apakah harga sama?

## 🧪 Example from Screenshot

```
Item: "Pemasangan 1 m2 dinding partisi, Gypsumboard..."

Lokal:
├─ Tipe: Material
├─ Koefisien: 1
├─ Harga Satuan: 167.499,5

Eksternal:
├─ Tipe: Material
├─ Koefisien: 1
├─ Harga Satuan: 167.499,5

Key Match: "material-1-167.499,5" (lokal) == "material-1-167.499,5" (eksternal) ✓
Result: SAMA ✓
```

## 💡 Implementation Details

```php
// Build external details with content-based key
$externalDetails = DB::connection('external')
    ->table('ahsp_detail')
    ->where('ahsp_id', $extItem->id)
    ->get()
    ->keyBy(function($item) {
        // Key: tipe + koefisien + harga_satuan
        return $item->tipe . '-' . 
               $item->koefisien . '-' . 
               $item->harga_satuan;
    });

// Match local details using same key
foreach ($localItem->details as $localDetail) {
    $key = $localDetail->tipe . '-' . 
           $localDetail->koefisien . '-' . 
           $localDetail->harga_satuan;
    
    $extDetail = $externalDetails->get($key);
    
    if (!$extDetail) {
        // This item exists in local but not in external
        $isDifferent = true;
        break;
    }
}
```

## 🚀 Impact

| Case | Matching Basis | Result |
|------|----------------|--------|
| Same content, different DB ID | Content (qty+harga) | SAMA ✓ |
| Different content, same or different ID | Content (qty+harga) | BERBEDA ✓ |
| 100% duplicate across DBs | Content | SAMA ✓ |

## 📝 Related Changes

Also improved in same commit:
- Removed comparison of `referensi_id` (ID-based)
- Removed comparison of non-essential fields
- Only compare `subtotal_final` for verification

## 🔒 Data Integrity

**Benefits:**
- ✅ Handles ID mismatch across databases
- ✅ More accurate matching of items
- ✅ Prevent false "different" categorization
- ✅ Safe for multi-database sync

**Safety:**
- ✅ Still detects actual content differences (qty, harga)
- ✅ No data loss risk
- ✅ Conservative matching (requires exact qty+harga)

## 🎯 Summary

**Sekarang detail items dicocokkan berdasarkan:**
1. Tipe (material/upah)
2. Koefisien (qty)
3. Harga satuan

**Bukan berdasarkan:**
- ❌ Referensi ID (database-dependent)
- ❌ Timestamp (not content-relevant)
- ❌ Header values (derived)

**Result:** Items 100% identical akan dianggap SAMA, regardless of database ID differences! 🎯
