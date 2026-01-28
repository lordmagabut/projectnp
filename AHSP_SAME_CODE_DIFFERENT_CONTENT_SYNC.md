# 🔄 Cara Mensinkronkan AHSP dengan Kode Sama Tapi Content Berbeda

## 📋 Situasi

AHSP-0001 ada di **kedua database** (lokal dan eksternal):
- Kode sama: `AHSP-0001`
- Tapi **content/details-nya berbeda** (items berbeda, harga berbeda, dll)

Sebelumnya sistem hanya membandingkan **header AHSP** (nama, satuan, total_harga), tidak detect perbedaan di **details**. Jadi AHSP-0001 masuk ke section "Sama" padahal seharusnya "Berbeda".

## ✅ Solusi yang Diterapkan

Saya telah update **`compareAhsp()` method** di `DataSyncController` untuk:

### 1. **Compare Header Fields**
```
✓ Kode pekerjaan (untuk matching)
✓ Nama pekerjaan
✓ Satuan  
✓ Total harga
```

### 2. **Compare Detail Count**
```
Jika jumlah detail items berbeda → PASTI "Berbeda"
```

### 3. **Compare Detail Content (line-by-line)**
```
Jika jumlah sama, check setiap item:
- Tipe (material/upah)
- Referensi ID
- Koefisien
- Harga satuan
- Subtotal final
```

### 4. **Keyby Detail Comparison**
Details diindex dengan key: `tipe-referensi_id`
- Material ID 5 ≠ Material ID 6 → Berbeda
- Koefisien 5 ≠ Koefisien 3 → Berbeda

## 🎯 Hasil

**Sekarang AHSP-0001 akan masuk ke section "Berbeda" bukan "Sama"** karena sistem detect detail-nya berbeda.

## 📱 Cara Mensinkronkan

### **Step 1:** Reload datasync page
Klik refresh atau buka ulang datasync tab untuk re-compare AHSP.

### **Step 2:** Cari AHSP-0001 di section "Berbeda"
AHSP-0001 sekarang akan tampil di tab "Berbeda (AHSP)" bukan "Sama".

### **Step 3:** Klik tombol Eye (Preview)
Lihat perbandingan detail antara lokal vs eksternal:
```
┌─────────────────────────────────────┐
│ Perbandingan Detail AHSP            │
├─────────────────────────────────────┤
│ Lokal:  Item A, Item B             │
│ Eksternal: Item X, Item Y, Item Z  │
├─────────────────────────────────────┤
│ [Update dengan Data Eksternal]      │
└─────────────────────────────────────┘
```

### **Step 4:** Review perbandingan
- Lihat detail items di kedua versi
- Check apakah mau replace lokal dengan eksternal

### **Step 5:** Klik "Update dengan Data Eksternal"
```
✓ Confirm di dialog
✓ AHSP-0001 lokal di-replace dengan data eksternal
✓ Semua details ter-update
✓ List otomatis reload
```

## 🔍 Detail Comparison Logic

```javascript
Lokal AHSP-0001:          Eksternal AHSP-0001:
├─ Material A (100 unit)  ├─ Material X (80 unit)
├─ Material B (50 unit)   ├─ Material Y (50 unit)
└─ Upah C (30 OH)         └─ Upah Z (40 OH)

Perbedaan Terdeteksi:
✓ Item berbeda (A vs X)
✓ Koefisien berbeda (100 vs 80)
✓ Jumlah item berbeda (3 vs 3 sama, tapi isi beda)

→ Masuk section "Berbeda" ✅
```

## ✨ Fitur Preview Modal

Modal yang muncul menampilkan:

### Header Comparison (2 kolom)
```
Lokal                  │ Eksternal
──────────────────────┼──────────────────────
Kode: AHSP-0001       │ Kode: AHSP-0001
Nama: Pekerjaan ABC   │ Nama: Pekerjaan ABC
Satuan: Unit          │ Satuan: Unit
Total: Rp 3.535.000   │ Total: Rp 3.425.000
```

### Details Comparison (Tabs)
**Tab Local (3 items)**
| Tipe | Kode | Nama | Koef | Harga | Final |
|------|------|------|------|-------|-------|
| Mat  | M001 | ... | 100 | ... | ... |
| Mat  | M002 | ... | 50 | ... | ... |
| Upah | U001 | ... | 30 | ... | ... |

**Tab Eksternal (3 items)**
| Tipe | Kode | Nama | Koef | Harga | Final |
|------|------|------|------|-------|-------|
| Mat  | X001 | ... | 80 | ... | ... |
| Mat  | Y001 | ... | 50 | ... | ... |
| Upah | Z001 | ... | 40 | ... | ... |

## 🚀 Cara Update Jika Puas dengan Eksternal

**Scenario: Eksternal lebih update, mau pakai itu**

```
1. Refresh datasync
2. Cari AHSP-0001 → sekarang di "Berbeda"
3. Klik eye icon
4. Review modal → eksternal lebih bagus
5. Klik "Update dengan Data Eksternal"
6. Confirm
7. ✅ AHSP-0001 lokal diganti eksternal
8. Masuk "Sama" sekarang
```

## 🔄 Cara Update Jika Ada Item Lokal Penting

**Scenario: Lokal ada item yang eksternal tidak punya**

Untuk ini, harus manual:
1. Buka AHSP-0001 di form edit
2. Tambah/hapus/ubah items sesuai kebutuhan
3. Copy detail eksternal secara manual atau
4. Gunakan button "Batch Update yang Dipilih" kalau ada

## 🐛 Bug yang Diperbaiki

| Issue | Penyebab | Solusi |
|-------|----------|--------|
| AHSP-0001 di "Sama" padahal berbeda | Hanya compare header | Now compare header + detail count + detail content |
| Tidak bisa detect item berbeda | Detail comparison missing | Added detail line-by-line comparison |
| User tidak tahu cara sync | UX unclear | Added detailed flow documentation |

## 💡 Comparison Fields

### Header Compare:
```php
nama_pekerjaan  // Nama/deskripsi AHSP
satuan         // Unit pengukuran
total_harga    // Total harga hasil kalkulasi
```

### Detail Compare (Per Item):
```php
tipe           // 'material' atau 'upah'
referensi_id   // ID material atau upah yang dirujuk
koefisien      // Quantity/volume
harga_satuan   // Harga per unit
subtotal_final // Hasil kalkulasi (setelah diskon/ppn)
```

## 📊 Comparison Result

```
AHSP-0001 dengan content berbeda:

Sebelum Fix:
├─ Section "Sama" → TIDAK BISA SYNC
└─ Tidak ada preview

Sesudah Fix:
├─ Section "Berbeda" → BISA PREVIEW + SYNC
└─ Modal dengan perbandingan detail lengkap ✅
```

## 🎯 Kesimpulan

Sekarang **AHSP-0001 dengan kode sama tapi content berbeda** akan:
1. ✅ Terdeteksi sebagai "Berbeda"
2. ✅ Bisa di-preview dengan modal
3. ✅ Bisa di-sync dengan eksternal
4. ✅ Perbandingan detail lengkap visible

**Reload browser dan coba lagi datasync-nya!** AHSP-0001 seharusnya sekarang ada di section "Berbeda". 🎉
