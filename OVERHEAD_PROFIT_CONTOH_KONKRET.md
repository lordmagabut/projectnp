# 📐 CONTOH KONKRET: Pembentukan Overhead & Profit dengan Angka

## Scenario: Membuat Penawaran untuk Proyek Gedung

### Input User di Form Create

```
PENAWARAN: "Penawaran Pekerjaan Gedung A - Revisi 1"
Tanggal: 2026-02-24
Sumber Nilai: "base"

═════════════════════════════════════════════════
SECTION 1: PEKERJAAN STRUKTUR & BETON
═════════════════════════════════════════════════
Header RAB: 1.1 Bekisting & Pembesian
Profit (%):   [10]  ← USER INPUT
Overhead (%): [5]   ← USER INPUT

Items dalam section ini:
  2.1.69: Pengiriman Material      Vol=1 LS  Harga Dasar=1000
  2.1.70: Instalasi Scaffolding    Vol=2 m²  Harga Dasar=2000
  2.1.71: Tenaga Bekisting         Vol=10 m²  Harga Dasar=5000

═════════════════════════════════════════════════
SECTION 2: PEKERJAAN PLAFON
═════════════════════════════════════════════════
Header RAB: 2. Pekerjaan Bangunan
Profit (%):   [12]  ← USER INPUT (BERBEDA!)
Overhead (%): [3]   ← USER INPUT (BERBEDA!)

Items dalam section ini:
  2.1.1: Plafon Gypsum             Vol=50 m²  Harga Dasar=800
  2.1.2: Pengecatan Plafon         Vol=50 m²  Harga Dasar=300
```

---

## Backend Processing (RabPenawaranController::store())

### SECTION 1: PEKERJAAN STRUKTUR

```
Profit:   10%
Overhead: 5%
Total Markup: 15%

Denominasi: denom = 1 - (10/100) - (5/100)
                  = 1 - 0.10 - 0.05
                  = 0.85  ← Sangat important!
```

#### Item 2.1.69: Pengiriman Material

**Raw Data dari RAB Dasar:**
```
Kode:     2.1.69
Deskripsi: Pengiriman Material
Volume:   1 LS
Type:     JASA saja

Breakdown Harga Dasar:
  Material: 0        (tidak ada bahan)
  Upah:     1000     (jasa pengiriman)
  ─────────────────
  Total:    1000
```

**Kalkulasi di Controller:**
```php
$matDasar  = 0      // dari RAB
$upahDasar = 1000   // dari RAB
$denom     = 0.85   // profit + overhead

// Kalkulasi harga penawaran dengan markup
$matCalc  = 0 / 0.85      = 0
$upahCalc = 1000 / 0.85   = 1176.47

// Hasil simpan ke DB
harga_material_penawaran_item = 0       ✓
harga_upah_penawaran_item     = 1176.47 ✓
total_item                    = 1176.47 * 1 = 1176.47
```

**Tersimpan di tabel `rab_penawaran_items`:**
```sql
INSERT INTO rab_penawaran_items (
  rab_penawaran_section_id,     ← 1 (section 1)
  harga_material_penawaran_item,← 0
  harga_upah_penawaran_item,    ← 1176.47
  total_penawaran_item,         ← 1176.47
  ...
) VALUES (1, 0, 1176.47, 1176.47, ...);
```

---

#### Item 2.1.70: Instalasi Scaffolding

**Raw Data dari RAB Dasar:**
```
Kode:     2.1.70
Deskripsi: Instalasi Scaffolding
Volume:   2 m²
Type:     MATERIAL + JASA

Breakdown Harga Dasar:
  Material: 800 (rental scaffolding)
  Upah:     1200 (tenaga instalasi)
  ─────────────────
  Total:    2000  ← harga_satuan_dasar per unit
```

**Kalkulasi di Controller:**
```php
$matDasar  = 800
$upahDasar = 1200
$denom     = 0.85

// Kalkulasi harga penawaran dengan markup
$matCalc  = 800 / 0.85   = 941.18
$upahCalc = 1200 / 0.85  = 1411.76
                 ─────────────────
                 Total = 2352.94

// Per unit: 2352.94
// Untuk volume 2 m²: 2352.94 * 2 = 4705.88

// Hasil simpan ke DB
harga_material_penawaran_item = 941.18 ✓
harga_upah_penawaran_item     = 1411.76 ✓
total_item                    = 4705.88 * 1 = 4705.88
```

**📝 Note:** Harga-harga ini SUDAH MELALUI MARKUP 15% (10% profit + 5% overhead)
- Harga Material naik dari 800 → 941.18 (markup 41.18 = 10%+5%/800)
- Harga Upah naik dari 1200 → 1411.76 (markup 211.76 = 10%+5%/1200)

---

#### Item 2.1.71: Tenaga Bekisting

**Raw Data dari RAB Dasar:**
```
Kode:     2.1.71
Deskripsi: Tenaga Bekisting
Volume:   10 m²
Type:     JASA & MATERIAL (mayoritas jasa)

Breakdown Harga Dasar:
  Material: 1000 (kayu, paku, etc)
  Upah:     4000 (tenaga tukang)
  ─────────────────
  Total:    5000
```

**Kalkulasi di Controller (ringkas):**
```
$matCalc  = 1000 / 0.85 = 1176.47 per m²
$upahCalc = 4000 / 0.85 = 4705.88 per m²
Total per m² = 5882.35

Untuk volume 10 m²: 5882.35 * 10 = 58823.50

Tersimpan:
harga_material_penawaran_item = 1176.47
harga_upah_penawaran_item     = 4705.88
total_item                    = 58823.50
```

---

### SECTION 2: PEKERJAAN PLAFON

```
Profit:   12%  ← ⚠️ BERBEDA DARI SECTION 1!
Overhead: 3%   ← ⚠️ BERBEDA DARI SECTION 1!
Total Markup: 15%

Denominasi: denom = 1 - (12/100) - (3/100)
                  = 1 - 0.12 - 0.03
                  = 0.85  ← SAMA hasilnya, tapi perentasenya beda!
```

#### Item 2.1.1: Plafon Gypsum

**Raw Data dari RAB Dasar:**
```
Kode:     2.1.1
Deskripsi: Plafon Gypsum
Volume:   50 m²
Type:     MATERIAL-dominant

Breakdown Harga Dasar:
  Material: 600 (gypsum, furring channel, etc)
  Upah:     200 (tenaga pasang)
  ─────────────────
  Total:    800
```

**Kalkulasi di Controller:**
```
Profit:   12%
Overhead: 3%
denom     = 1 - 0.12 - 0.03 = 0.85  ← Kebetulan sama!

$matCalc  = 600 / 0.85 = 705.88 per m²
$upahCalc = 200 / 0.85 = 235.29 per m²
Total per m² = 941.18

Untuk volume 50 m²: 941.18 * 50 = 47058.82

Tersimpan:
harga_material_penawaran_item = 705.88
harga_upah_penawaran_item     = 235.29
total_item                    = 47058.82
```

**⚠️ PERHATIAN:**
- Item 2.1.1 ada di Section 2 dengan Profit=12%, Overhead=3%
- Item 2.1.70 ada di Section 1 dengan Profit=10%, Overhead=5%
- Meski keduanya punya `denom = 0.85`, tapi **persentasenya BERBEDA!**
- Jika kemudian user ubah Section 1 profit → item 2.1.70 TIDAK otomatis ter-update

---

## Database State Setelah Create

### Tabel `rab_penawaran_sections`
```
id | rab_penawaran_header_id | rab_header_id | profit_percentage | overhead_percentage
─────────────────────────────────────────────────────────────────────────────────────
1  | 1 (penawaran ini)       | 5 (Struktur)  | 10.00             | 5.00
2  | 1 (penawaran ini)       | 7 (Plafon)    | 12.00             | 3.00
```

### Tabel `rab_penawaran_items`
```
id | section_id | kode | volume | harga_material_penawaran_item | harga_upah_penawaran_item | total_item
─────────────────────────────────────────────────────────────────────────────────────────────────────────
1  | 1          | 2.1.69 | 1    | 0.00                         | 1176.47                    | 1176.47
2  | 1          | 2.1.70 | 2    | 941.18                       | 1411.76                    | 4705.88
3  | 1          | 2.1.71 | 10   | 1176.47                      | 4705.88                    | 58823.50
4  | 2          | 2.1.1  | 50   | 705.88                       | 235.29                     | 47058.82
5  | 2          | 2.1.2  | 50   | 352.94                       | 176.47                     | 26470.59
```

---

## Total Penawaran

```
SECTION 1 Total:
  Item 2.1.69: 1176.47
  Item 2.1.70: 4705.88
  Item 2.1.71: 58823.50
  ──────────────────────
  Section 1 = 64705.85

SECTION 2 Total:
  Item 2.1.1:  47058.82
  Item 2.1.2:  26470.59
  ──────────────────────
  Section 2 = 73529.41

═════════════════════════════════════════════════
TOTAL PENAWARAN BRUTO: 64705.85 + 73529.41 = 138235.26
Diskon (10%):          13823.53
═════════════════════════════════════════════════
TOTAL PENAWARAN NETTO: 124411.73
```

---

## 🔑 Key Insights

| # | Insight |
|---|---------|
| 1 | **Overhead & Profit disimpan di level SECTION** (tabel sections) |
| 2 | **Item TIDAK punya kolom `profit_percentage_applied`** - hanya hasil kalkulasinya |
| 3 | **Jika user ubah profit/overhead section SETELAH item dibuat:**<br/>→ Section punya profit baru<br/>→ Item lama tetap pake harga lama<br/>→ **MISMATCH!** ⚠️ |
| 4 | **Tidak ada validasi/warning** jika ada item dengan harga berbeda padding |
| 5 | **Untuk detect mismatch** harus compare profit/overhead section vs perhitungan reverse dari item |

---

## 📊 Formula Reverse (untuk detect mismatch)

Jika ingin tahu: "Item ini sebenarnya dibuat dengan profit berapa?", bisa reverse-engineer:

```python
# Di item, kita tahu:
harga_dasar           = 1000
harga_penawaran       = 1176.47

# Di section, kita tahu:
profit_percentage     = 10
overhead_percentage   = 5

# Reverse formula:
# harga_penawaran = harga_dasar / (1 - p% - oh%)
# (1 - p% - oh%) = harga_dasar / harga_penawaran

calculated_denom = harga_dasar / harga_penawaran
                 = 1000 / 1176.47
                 = 0.85

calculated_markup_pct = (1 - 0.85) * 100 = 15%

# Bandingkan dengan section:
section_markup = (profit + overhead) * 100 = (0.10 + 0.05) * 100 = 15%

# Jika berbeda:
if calculated_markup_pct != section_markup:
    flag_as_mismatch("Item punya profit berbeda dari section!")
```

---

## ✅ Kesimpulan

Pembentukan overhead & profit saat **create penawaran** adalah:

1. **Manual input user** di form (per section)
2. **Validasi backend** untuk profit+overhead < 100%
3. **Simpan ke DB** di tabel sections
4. **Gunakan untuk kalkulasi** harga item pada saat creation
5. **Simpan hasil** ke tabel items (BUKAN input overhead/profitnya)
6. **Tidak ada tracking** "item ini dibuat dengan profit berapa"

**Problem:** Jika user ubah profit/overhead section → items lama tidak otomatis ter-update

