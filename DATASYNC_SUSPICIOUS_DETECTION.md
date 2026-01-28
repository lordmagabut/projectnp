# 🚨 Suspicious AHSP Detection - Data Integrity Protection

## 📋 Masalah yang Ditemukan

Dari screenshot yang Anda tunjukkan, terlihat bahwa **beberapa AHSP memiliki kode yang sama tapi merujuk ke pekerjaan yang BERBEDA JAUH**:

```
AHSP-0002 Lokal:     "1 unit Jet Shower spray tails THA 25 NEW"
AHSP-0002 Eksternal: "Gabin Tanah Manual"
❌ COMPLETELY BERBEDA! Bukan hanya detail, tapi pekerjaan lain sama sekali!

AHSP-0003 Lokal:     "1 unit Stop Kran TOTO TJZ7591 Stop Valve"  
AHSP-0003 Eksternal: "Atonal puing / tanah di topi satil petiwik truk"
❌ COMPLETELY BERBEDA! Pekerjaan infrastructure vs plumbing!
```

### **Bahaya:**
Jika di-sync tanpa review, akan **menimpa pekerjaan yang benar dengan pekerjaan lain** → **DATA LOSS yang tidak dapat dipulihkan!**

---

## ✅ Solusi yang Diterapkan

### **1. Name Similarity Detection (Similarity Algorithm)**
Added `similar_text()` PHP function untuk detect:
- Jika nama pekerjaan lokal vs eksternal **kurang dari 60% similar**
- Item masuk ke section **"SUSPICIOUS"** (tidak "sama" atau "berbeda")
- Prevent otomatis sync

### **2. New Section: "SUSPICIOUS ITEMS"**
Tampil dengan:
- **🔴 RED Border** → Visual alert
- **Similarity Score** → Show seberapa besar perbedaan nama
- **"Review Detail" Button** → Alert user untuk manual check
- **Warning Box** → Jelaskan risiko data loss
- **Pertanyaan Penting** → Guide user decision making

### **3. Manual Review Flow (No Auto-Sync)**
Suspicious items:
- ❌ NOT auto-syncable (tidak ada button "Update")
- ✅ Bisa di-review manual
- ✅ User harus decide secara conscious

---

## 📊 Comparison Logic Sekarang

```
AHSP Matching Process:

1. Match by Code
   ↓
2. Check Name Similarity
   ├─ > 60% similar → Lanjut compare content
   └─ < 60% similar → SUSPICIOUS (perlu review manual)

3. If Similar Enough:
   ├─ Compare header fields
   ├─ Compare detail count
   ├─ Compare detail content
   └─ Categorize as "Same" or "Different"
```

## 🎯 Categories

| Category | When | Action | Risk |
|----------|------|--------|------|
| **Same** | Kode sama, nama sama, content sama | Auto-resync OK | ✅ Low |
| **Different** | Kode sama, nama sama, content berbeda | Preview + confirm | ⚠️ Medium |
| **Suspicious** | Kode sama, **nama < 60% similar** | Manual review ONLY | 🔴 HIGH |
| **Only Local** | Hanya di lokal | Keep as-is | ✅ Low |
| **Only External** | Hanya di eksternal | Copy if needed | ✅ Low |

## 🎨 Visual Indicators

### **Section Header**
```
🔴 PERLU REVIEW - Kode Sama Tapi Nama Berbeda Jauh!  [3 items]
```

### **Item Card**
```
┌─────────────────────────────────────────────────────┐
│ 🔴 AHSP-0002                                        │
│ Kesamaan Nama: 15%                 [Review Detail] │
├─────────────────────────────────────────────────────┤
│ 🔴 Lokal          │  🔴 Eksternal                  │
│ Jet Shower...     │  Gabin Tanah...                │
└─────────────────────────────────────────────────────┘
```

### **Warning Box**
```
⚠️ PERHATIAN:
- Kode sama tapi nama berbeda JAUH
- Bisa indicate kode reuse untuk pekerjaan berbeda
- RISK: Data loss jika sync salah
- Pastikan BENAR sebelum sync!
```

## 💡 How to Handle Suspicious Items

### **Option 1: Confirm It's Same (Manual Merge)**
```
1. Click "Review Detail"
2. Check detail items di kedua versi
3. Jika memang same pekerjaan, 
   informasikan administrator untuk sync manual
```

### **Option 2: Keep Local Version**
```
1. Ignore suspicious item
2. Local version tetap digunakan
3. Eksternal version diabaikan
```

### **Option 3: Use External Version**
```
1. Jika yakin eksternal lebih benar
2. Contact administrator untuk manual sync
3. Perform verification dulu sebelum replace
```

## 🔍 Example: AHSP-0002

### **Before Fix:**
```
AHSP-0002 akan masuk "Sama" → User bisa auto-sync
Result: Jet Shower diganti dengan Gabin Tanah ❌
```

### **After Fix:**
```
AHSP-0002 masuk "SUSPICIOUS" → User harus review manual
Result: User aware ada risiko, bisa decide dengan hati-hati ✅
```

## ✨ Technical Details

### **PHP Code:**
```php
$nameSimilarity = similar_text(
    strtolower($localItem->nama_pekerjaan),
    strtolower($extItem->nama_pekerjaan),
    $percent
);

if ($percent < 60) {
    $comparison['suspicious'][] = [
        'local' => $localItem,
        'external' => $extItem,
        'similarity' => round($percent, 2)
    ];
}
```

### **Threshold: 60%**
- Set conservatively untuk keamanan
- "Jet Shower" vs "Gabin Tanah" → ~10% similarity
- "Stop Kran" vs "Atonal puing" → ~5% similarity
- Trigger suspicious untuk semua kasus ekstrim

## 🚀 Changed Files

**1. `app/Http/Controllers/DataSyncController.php`**
- Method `compareAhsp()` - Add name similarity detection
- Use `similar_text()` for fuzzy matching
- Return 5 categories instead of 4

**2. `resources/views/datasync/index.blade.php`**
- Add "suspicious" section in accordion
- `renderSuspicious()` function - Display with warnings
- `showSuspiciousDetail()` function - Alert with guidance

## 🧪 Testing

```
1. Refresh datasync page
2. Go to AHSP tab
3. Look for section "PERLU REVIEW - Kode Sama Tapi Nama Berbeda Jauh!"
4. Should show AHSP-0002, AHSP-0003, etc
5. Click "Review Detail" → See alert with options
```

## 📋 Best Practices

✅ **DO:**
- Always check suspicious items before sync
- Review side-by-side comparison carefully
- Contact administrator for data integrity issues
- Keep audit trail of what was synced

❌ **DON'T:**
- Auto-sync suspicious items
- Trust only kode matching
- Ignore similarity warnings
- Sync without verifying detail items

## 🔄 Future Improvements

1. **Audit Logging** - Track semua suspicious merges
2. **Admin Approval** - Suspicious items perlu admin approval
3. **Version Control** - Keep history sebelum sync
4. **Smarter Matching** - Match by detail items, not just name
5. **Batch Review** - Review multiple suspicious items at once

## 🎯 Summary

**Sekarang sistem akan:**
1. ✅ Detect kode reuse untuk pekerjaan berbeda
2. ✅ Flag items sebagai "SUSPICIOUS" 
3. ✅ Prevent auto-sync untuk items riskky
4. ✅ Force manual review sebelum merge
5. ✅ Protect data integrity

**Result:** No more accidental data overwrites! 🛡️
