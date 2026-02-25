# 📊 Flow Pembentukan Overhead & Profit saat Pembuatan RAB Penawaran

## 1️⃣ INPUT STAGE (di Form Create)

### **User Input:**
```
┌─────────────────────────────────────────────────┐
│ FORM BUAT PENAWARAN BARU                        │
├─────────────────────────────────────────────────┤
│ Nama Penawaran: "Penawaran Proyek A - Revisi 1" │
│ Tanggal: 2026-02-24                             │
│ Sumber Nilai: "base" atau "contingency"         │
├─────────────────────────────────────────────────┤
│ SECTION #1: Pekerjaan Struktur                  │
│ ┌───────────────────────────────────────────┐   │
│ │ RAB Header: 1. Persiapan & Perijinan      │   │
│ │ Profit (%):   10    ← USER INPUT          │   │
│ │ Overhead (%): 5     ← USER INPUT          │   │
│ └───────────────────────────────────────────┘   │
│                                                 │
│ Item dalam section ini:                         │
│ - Item 2.1.69: Pengiriman Material (Jasa)      │
│ - Item 2.1.70: Instalasi Scaffolding            │
│ - dst...                                        │
├─────────────────────────────────────────────────┤
│ SECTION #2: Pekerjaan Plafon                   │
│ ┌───────────────────────────────────────────┐   │
│ │ RAB Header: 2. Pekerjaan Bangunan         │   │
│ │ Profit (%):   12    ← USER INPUT          │   │
│ │ Overhead (%): 3     ← USER INPUT          │   │
│ └───────────────────────────────────────────┘   │
│                                                 │
│ Item dalam section ini:                         │
│ - Item 2.1.1: Plafon Gypsum                    │
│ - Item 2.1.2: Pengecatan Plafon                │
│ - dst...                                        │
└─────────────────────────────────────────────────┘
```

---

## 2️⃣ VALIDATION STAGE (di Backend Controller)

Saat form klik "Simpan", `store()` di `RabPenawaranController` melakukan:

```php
/**
 * RabPenawaranController::store()
 * File: app/Http/Controllers/RabPenawaranController.php line 134-300
 */

// Step 1: Validasi input (lines 147-159)
$request->validate([
    'sections.*.profit_percentage'   => 'required|numeric|min:0|max:100',
    'sections.*.overhead_percentage' => 'required|numeric|min:0|max:100',
    // ... validasi lainnya
]);

// Step 2: Loop setiap section (line 180)
foreach ($request->sections as $sectionIndex => $sectionData) {
    
    // Step 3: Extract profit & overhead (lines 181-182)
    $profitPercentage   = (float)$sectionData['profit_percentage'];      // 10.0
    $overheadPercentage = (float)($sectionData['overhead_percentage'] ?? 0); // 5.0
    
    // Step 4: Validasi profit + overhead < 100% (lines 184-189)
    if (($profitPercentage + $overheadPercentage) >= 100) {
        throw ValidationException::withMessages([
            "sections.$sectionIndex.profit_percentage" => 'Total profit + overhead harus kurang dari 100%.',
            "sections.$sectionIndex.overhead_percentage" => 'Total profit + overhead harus kurang dari 100%.',
        ]);
    }
    
    // Step 5: Buat record section di database (lines 191-197)
    $newSection = RabPenawaranSection::create([
        'rab_penawaran_header_id' => $penawaranHeader->id,
        'rab_header_id'           => $sectionData['rab_header_id'],
        'profit_percentage'       => $profitPercentage,        // ✅ Disimpan
        'overhead_percentage'     => $overheadPercentage,      // ✅ Disimpan
        'total_section_penawaran' => 0, // akan dihitung nanti
    ]);
    
    // Step 6: Loop setiap item dalam section
    foreach ($sectionData['items'] as $itemData) {
        
        // Ambil harga dasar dari RAB Detail
        $rabDetail = RabDetail::find($itemData['rab_detail_id']);
        [$matDasar, $upahDasar] = $this->deriveMaterialUpahFromDetail($rabDetail, $priceMode);
        
        // ⭐ KALKULASI HARGA PENAWARAN PAKAI OVERHEAD + PROFIT (lines 225-245)
        // Formula: harga_penawaran = harga_dasar / (1 - profit% - overhead%)
        
        $denom = 1 - ($profitPercentage / 100) - ($overheadPercentage / 100);
        //      1 - (10/100) - (5/100)
        //      1 - 0.1 - 0.05
        //      = 0.85
        
        $hargaSatuanDasar    = $matDasar + $upahDasar;  // cth: 1000
        $hargaSatuanCalculated = $denom > 0 
            ? ($hargaSatuanDasar / $denom)              // 1000 / 0.85 = 1176.47
            : 0.0;
        $hargaSatuanPenawaran = $hargaSatuanCalculated;
        $totalItem = $hargaSatuanPenawaran * $volume;
        
        // ⭐ BREAKDOWN MATERIAL & JASA DENGAN PROFIT + OVERHEAD
        $matCalc  = $denom > 0 ? ($matDasar / $denom) : 0.0;
        $upahCalc = $denom > 0 ? ($upahDasar / $denom) : 0.0;
        
        // Contoh:
        // materDasar = 600, upahDasar = 400
        // denom = 0.85
        // matCalc  = 600 / 0.85 = 705.88
        // upahCalc = 400 / 0.85 = 470.59
        // Total    = 705.88 + 470.59 = 1176.47 ✓
        
        // Step 7: Simpan item penawaran (lines 247-267)
        RabPenawaranItem::create([
            'rab_penawaran_section_id'       => $newSection->id,
            'rab_detail_id'                  => $rabDetail->id,
            'harga_satuan_dasar'             => $hargaSatuanDasar,      // TANPA profit/overhead
            'harga_satuan_calculated'        => $hargaSatuanCalculated,
            'harga_satuan_penawaran'         => $hargaSatuanPenawaran,   // DENGAN profit/overhead
            'total_penawaran_item'           => $totalItem,
            
            'harga_material_dasar_item'      => $matDasar,              // tanpa markup
            'harga_upah_dasar_item'          => $upahDasar,             // tanpa markup
            'harga_material_calculated_item' => $matCalc,               // ✅ DENGAN MARKUP
            'harga_upah_calculated_item'     => $upahCalc,              // ✅ DENGAN MARKUP
            'harga_material_penawaran_item'  => $matCalc,               // <- Disimpan
            'harga_upah_penawaran_item'      => $upahCalc,              // <- Disimpan
        ]);
    }
}
```

---

## 3️⃣ DATABASE STRUCTURE

### Tabel `rab_penawaran_sections`
```
id | rab_penawaran_header_id | rab_header_id | profit_percentage | overhead_percentage
1  | 5                       | 12            | 10.0              | 5.0
2  | 5                       | 18            | 12.0              | 3.0
```

### Tabel `rab_penawaran_items`
```
id | rab_penawaran_section_id | rab_detail_id | volume | harga_dasar | harga_material_dasar_item | harga_upah_dasar_item | harga_material_penawaran_item | harga_upah_penawaran_item
1  | 1                        | 69            | 1      | 1000        | 600                       | 400                   | 705.88                       | 470.59
2  | 1                        | 70            | 2      | 2000        | 1200                      | 800                   | 1411.76                      | 941.18
```

---

## 4️⃣ KALKULASI DETAIL

### Contoh untuk Item 2.1.69

**Dari RAB Dasar:**
```
Kode: 2.1.69
Item: Pengiriman Material (Jasa)
Volume: 1
Satuan: LS
Harga Dasar: 1000 (jasa saja)
  - Material: 0
  - Upah/Jasa: 1000
```

**Section yang dipakai:** "Pekerjaan Struktur"
- Profit: 10%
- Overhead: 5%

**Kalkulasi:**
```
Formula: harga_penawaran = harga_dasar / (1 - profit% - overhead%)

denom = 1 - (10/100) - (5/100)
      = 1 - 0.10 - 0.05
      = 0.85

Breakdown:
  Harga Material Dasar: 0
  Harga Upah Dasar:     1000
  
  Harga Material Penawaran: 0 / 0.85 = 0        → harga_material_penawaran_item
  Harga Upah Penawaran:     1000 / 0.85 = 1176.47 → harga_upah_penawaran_item
  
  Total Harga Penawaran: 0 + 1176.47 = 1176.47
  Total Item: 1176.47 * 1 = 1176.47
```

**Tersimpan di database:**
```
harga_material_dasar_item      = 0
harga_upah_dasar_item          = 1000
harga_material_penawaran_item  = 0       ✓
harga_upah_penawaran_item      = 1176.47 ✓
```

---

## 5️⃣ FLOW CHART

```
┌─────────────────────────────────┐
│ USER BUAT PENAWARAN             │
│ Input: Nama, Tgl, Section+Item  │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ BACKEND TERIMA REQUEST          │
│ (RabPenawaranController::store) │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ VALIDASI:                               │
│ - Profit + Overhead < 100%? ✓          │
│ - Semua field required terisi? ✓       │
└────────────┬────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│ LOOP SETIAP SECTION                      │
│ Extract: profit_percentage, overhead_pct │
└────────────┬─────────────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│ CREATE RabPenawaranSection         │
│ + Simpan profit & overhead di DB   │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│ LOOP SETIAP ITEM DALAM SECTION          │
│ Ambil: volume, harga_dasar (dari RAB)   │
└────────────┬─────────────────────────────┘
             │
             ▼
┌───────────────────────────────────────────────────╗
│ ⭐ KALKULASI HARGA DENGAN MARKUP                   │
│                                                   │
│ denom = 1 - (profit% + overhead%)                │
│ harga_penawaran = harga_dasar / denom            │
│                                                   │
│ harga_material_penawaran = mat_dasar / denom     │
│ harga_upah_penawaran = upah_dasar / denom        │
└────────────┬─────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│ CREATE RabPenawaranItem                  │
│ + Simpan harga_material_penawaran_item   │
│ + Simpan harga_upah_penawaran_item       │
└────────────┬─────────────────────────────┘
             │
             ▼
┌────────────────────────────┐
│ ✅ PENAWARAN BERHASIL DIBUAT│
│ Status: draft               │
└────────────────────────────┘
```

---

## 6️⃣ KEY POINTS

| Aspek | Penjelasan |
|-------|-----------|
| **Overhead & Profit disimpan di?** | Tabel `rab_penawaran_sections` (level section) |
| **Disimpan KAPAN?** | Saat create penawaran (bersama dengan item) |
| **Disimpan cara apa?** | Sebagai persentase (0-100) di kolom `profit_percentage` dan `overhead_percentage` |
| **Overhead & Profit berbeda per item?** | **TIDAK** - saat ini hanya per section. Semua item dalam 1 section memakai profit/overhead yang sama |
| **Harga item berubah jika overhead/profit diubah?** | **TIDAK** - sekali dibentuk, tidak otomatis ter-update |
| **Ada tracking kapan dibuat?** | Ada di `created_at` record di database, tapi BUKAN tentang "nilai profit berapa saat dibuat" |
| **Bisa detect item punya profit berbeda?** | **TIDAK** - tidak ada field yang menyimpan "profit applied saat item dibuat" |

---

## 💡 KESIMPULAN

Saat ini overhead dan profit:
1. ✅ **Diterima dari user di form** (section-level)
2. ✅ **Disimpan ke database** (tabel sections)
3. ✅ **Dipakai untuk kalkulasi harga item** saat pertama kali dibuat
4. ❌ **TIDAK disimpan snapshot-nya ke item** (hanya result kalkulasinya)
5. ❌ **Tidak ada tracking** "item ini dibuat dengan profit berapa %"
6. ❌ **Item tidak ter-update otomatis** jika profit/overhead section diubah kemudian

**Inilah mengapa masalah duplikasi harga bisa terjadi** - kalau user ubah profit/overhead SETELAH item dibuat, sistem tidak tahu item itu dibuat dengan profit/overhead yang berbeda.

