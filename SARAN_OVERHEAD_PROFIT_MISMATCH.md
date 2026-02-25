# 💡 Saran: Mengatasi Perbedaan Overhead & Profit per Item

## Analisis Masalah

Dari struktur database dan controller, saya menemukan:

### **Struktur Data Saat Ini:**
```
RabPenawaranSection (Header)
  ├─ profit_percentage  (cth: 10%)
  ├─ overhead_percentage (cth: 5%)
  └─ RabPenawaranItem[] (10 items)
       └─ harga_material_penawaran_item (dibuat dari formula)
       └─ harga_upah_penawaran_item (dibuat dari formula)
```

### **Formula Perhitungan di Controller:**
```php
$denom = 1 - (profit/100) - (overhead/100);
// Maka: harga_penawaran = harga_dasar / denom
```

**Contoh:**
- Harga dasar: 1000
- Profit: 10%, Overhead: 5%
- Denom: 1 - 0.10 - 0.05 = 0.85
- Harga penawaran: 1000 / 0.85 = **1.176,47**

---

## 🔴 Penyebab Perbedaan Overhead/Profit per Item

### **Penyebab #1: Perubahan Overhead/Profit SETELAH Item Dibuat**
```
Waktu T0: Create penawaran dengan Profit=10%, Overhead=5%
          → Semua item di-calculate dengan denom=0.85

Waktu T1: Edit section, ubah Profit=15%, Overhead=0%
          → Profit/overhead di section berubah
          → TAPI items lama tidak otomatis ter-recalculate! ❌
          → Items sudah pakai denom=0.85, baru section pakai denom=0.85
```

### **Penyebab #2: Manual Price Override di Item**
```
User edit item langsung:
  - Ubah harga_material_penawaran_item secara manual
  - Ubah harga_upah_penawaran_item
  - Tidak ada tracking kapan perubahan terjadi
  - Item "out of sync" dengan section overhead/profit
```

### **Penyebab #3: Duplikasi/Merge Penawaran**
```
Penawaran A: Profit=10%, Overhead=5%
Penawaran B: Profit=15%, Overhead=2%

Jika merge atau copy-paste items B ke A:
  - Items B sudah disimpan dengan denom dari B
  - Tapi sekarang ada di section A (dengan denom berbeda)
  - Terjadi inkonsistensi ❌
```

### **Penyebab #4: Formula Berbeda saat Recalculate**
```
Jika ada update aplikasi atau perubahan formula pembulatan:
  - Item lama pakai formula lama
  - Item baru pakai formula baru
  - Hasilnya berbeda meski overhead/profit sama
```

---

## 💼 Solusi yang Direkomendasikan

### **Opsi 1: Snapshot Overhead/Profit ke Setiap Item** ⭐ RECOMMENDED

**Keuntungan:**
- ✅ Audit trail lengkap
- ✅ Deteksi mismatch mudah
- ✅ Recalculate bisa dibuat simple

**Implementasi:**
```php
// Tambah kolom ke rab_penawaran_items:
ALTER TABLE rab_penawaran_items ADD COLUMN profit_percentage_applied DECIMAL(5,2);
ALTER TABLE rab_penawaran_items ADD COLUMN overhead_percentage_applied DECIMAL(5,2);
ALTER TABLE rab_penawaran_items ADD COLUMN formula_version_applied INT; // tracking formula
```

**Saat Create Item:**
```php
RabPenawaranItem::create([
    'harga_material_penawaran_item' => $matCalc,
    'harga_upah_penawaran_item' => $upahCalc,
    'profit_percentage_applied' => $profitPercentage,  // ← BARU
    'overhead_percentage_applied' => $overheadPercentage, // ← BARU
    'formula_version_applied' => 1, // version control
]);
```

**UI Verification:**
```blade
@if($item->profit_percentage_applied != $section->profit_percentage ||
    $item->overhead_percentage_applied != $section->overhead_percentage)
  <span class="badge bg-warning">
    ⚠️ Profit/Overhead berbeda dari section!
    Applied: P={{ $item->profit_percentage_applied }}%, OH={{ $item->overhead_percentage_applied }}%
    Section: P={{ $section->profit_percentage }}%, OH={{ $section->overhead_percentage }}%
  </span>
@endif
```

---

### **Opsi 2: Batch Recalculate dengan Dry-Run** ⭐ RECOMMENDED

**Tombol Action di UI:**
```blade
<button class="btn btn-warning" onclick="showRecalculateDryRun()">
  🔄 Recalculate Dengan Overhead/Profit Terbaru
</button>
```

**Proses Dry-Run (sebelum save):**
```javascript
// Tampilkan perbandingan:
// Item 2.1.69: 
//   Harga Lama: Mat=1000, Upah=200
//   Harga Baru: Mat=1050, Upah=210  ← based on updated profit/overhead
//   Status: ✓ OK → APPLY / ✗ CANCEL
```

**Batch Apply:**
- Filter: "Items dengan mismatch overhead/profit"
- Action: "Apply section profit/overhead to all items"
- Dry-run dulu, baru save

---

### **Opsi 3: Item-Level Profit/Overhead Overrides** 

**Untuk kasus special:** Ada item yang perlu persentase berbeda dari section

**UI:**
```blade
<div class="collapsible">
  <label>Manual Override? 
    <input type="checkbox" class="toggle-manual-override">
  </label>
  <div class="override-fields" style="display:none;">
    <input type="number" name="profit_override" placeholder="Profit % (optional)">
    <input type="number" name="overhead_override" placeholder="Overhead % (optional)">
  </div>
</div>
```

**Logic:**
```php
$profit = $item->profit_override ?? $section->profit_percentage;
$overhead = $item->overhead_override ?? $section->overhead_percentage;
// Apply ini daripada section punya
```

---

### **Opsi 4: Change Audit Log**

Track kapan item di-modify:

```php
// Tambah kolom:
ALTER TABLE rab_penawaran_items ADD COLUMN last_calculated_at TIMESTAMP;
ALTER TABLE rab_penawaran_items ADD COLUMN last_calculated_by INT;
ALTER TABLE rab_penawaran_items ADD COLUMN calculation_notes JSON;
```

**Saat Recalculate:**
```php
$item->update([
    'harga_material_penawaran_item' => $newMat,
    'harga_upah_penawaran_item' => $newUpah,
    'last_calculated_at' => now(),
    'last_calculated_by' => auth()->id(),
    'calculation_notes' => json_encode([
        'section_profit' => $section->profit_percentage,
        'section_overhead' => $section->overhead_percentage,
        'formula_version' => 1,
        'source' => 'batch_recalculate', // atau 'manual_override'
    ]),
]);
```

---

## 📋 Rekomendasi Implementasi (Urutan Prioritas)

| # | Solusi | Effort | Impact | Prioritas |
|---|--------|--------|--------|-----------|
| 1 | Snapshot Overhead/Profit ke Item | Medium | Tinggi ✅ | **FIRST** |
| 2 | Visual Warning (Mismatch Badge) | Easy | Medium | **FIRST** |
| 3 | Batch Recalculate dengan Dry-Run | Medium | Tinggi ✅ | **SECOND** |
| 4 | Item-level Overrides | Medium | Medium | **OPTIONAL** |
| 5 | Change Audit Log | Easy | Rendah | **OPTIONAL** |

---

## ✅ Action Plan (Jika Disetujui)

1. **Database Migrations:**
   - Add `profit_percentage_applied`, `overhead_percentage_applied` ke `rab_penawaran_items`
   - Add `last_calculated_at`, `calculation_notes` 

2. **Backend Changes:**
   - Update RabPenawaranController saat create item
   - Buat method `recalculateSectionItems()`

3. **Frontend Changes:**
   - Tambah badge warning di item table
   - Tombol "Recalculate All Items"
   - Modal dry-run sebelum apply

4. **Testing:**
   - Test perubahan overhead/profit section
   - Verify items ter-flagging dengan benar
   - Test batch recalculate

---

## 🎯 Kesimpulan

Saat ini sistem **tidak tracking** overhead/profit yang dipakai per item, hanya menyimpan hasil kalkulasi final. Ini menyebabkan:
- ❌ Tidak bisa tahu item punya persentase apa waktu dibuat
- ❌ Tidak bisa detect mismatch otomatis
- ❌ Recalculate manual atau harus delete+recreate

**Solusi terbaik:** Simpan snapshot overhead/profit per item + buat recalculate batch dengan dry-run.

Mau mulai dengan mana dulu? 🚀
