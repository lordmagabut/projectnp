# Integrasi Import RAB + AHSP + HSD Material & Upah

## Ringkasan Implementasi

Fitur ini memungkinkan Anda mengimport **seluruh data RAB, AHSP, dan harga material/upah secara bersamaan** dalam satu file Excel dengan 6 sheet terkoordinasi.

---

## File Yang Dibuat/Dimodifikasi

### 1. **Imports Baru (4 File)**
- [HsdMaterialSheetImport.php](app/Imports/HsdMaterialSheetImport.php) - Import data harga material
- [HsdUpahSheetImport.php](app/Imports/HsdUpahSheetImport.php) - Import data harga upah
- [AhspHeaderSheetImport.php](app/Imports/AhspHeaderSheetImport.php) - Import master AHSP
- [AhspDetailSheetImport.php](app/Imports/AhspDetailSheetImport.php) - Import komponen material/upah untuk setiap AHSP

### 2. **File Dimodifikasi**
- [RABImport.php](app/Imports/RABImport.php) - Update urutan processing sheet (HSD → AHSP → RAB)
- [RabController.php](app/Http/Controllers/RabController.php) - Update template Excel dengan 6 sheet

---

## Urutan Processing (Otomatis)

```
1. HSD_Material      (Import harga satuan material)
2. HSD_Upah          (Import harga satuan upah)
3. AHSP_Header       (Import master pekerjaan analisa)
4. AHSP_Detail       (Import komponen + hitung total)
5. RAB_Header        (Import struktur RAB header)
6. RAB_Detail        (Import detail RAB + auto link ke AHSP)
```

---

## Struktur Sheet Template

### **Sheet 1: HSD_Material**
```
| kode_item | nama_item      | satuan | harga_satuan |
|-----------|----------------|--------|--------------|
| MAT.001   | Pasir Malang   | m3     | 150000       |
| MAT.002   | Semen          | sak    | 50000        |
```

### **Sheet 2: HSD_Upah**
```
| kode_item | nama_item        | satuan | harga_satuan |
|-----------|------------------|--------|--------------|
| UPH.001   | Tukang Gali      | HOK    | 200000       |
| UPH.002   | Pembantu Tukang  | HOK    | 150000       |
```

### **Sheet 3: AHSP_Header**
```
| kode_pekerjaan | nama_pekerjaan | satuan | catatan      |
|----------------|----------------|--------|--------------|
| A.1            | Excavation 1m  | m3     | Tanah biasa  |
| A.2            | Excavation 2m  | m3     | Tanah batu   |
```

### **Sheet 4: AHSP_Detail**
```
| ahsp_kode | tipe     | kode_item | koefisien |
|-----------|----------|-----------|-----------|
| A.1       | material | MAT.001   | 1.2       |
| A.1       | upah     | UPH.001   | 5         |
| A.2       | material | MAT.002   | 1.5       |
| A.2       | upah     | UPH.003   | 6         |
```

### **Sheet 5: RAB_Header**
```
| kategori_id | parent_kode | kode | deskripsi                |
|-------------|-------------|------|--------------------------|
| 1           |             | 1    | PEKERJAAN PERSIAPAN      |
| 1           | 1           | 1.1  | PEKERJAAN PEMBERSIHAN    |
```

### **Sheet 6: RAB_Detail**
```
| header_kode | kode  | deskripsi      | area | spesifikasi | satuan | volume | ... | ahsp_kode |
|-------------|-------|----------------|------|-------------|--------|--------|-----|-----------|
| 1.1         | 1.1.1 | Excavation     | ...  | ...         | m3     | 50     | ... | A.1       |
| 1.1         | 1.1.2 | Excavation Pro | ...  | ...         | m3     | 75     | ... | A.2       |
```

---

## Cara Penggunaan

### **Download Template**
1. Go to Proyek → Tab RAB → Klik tombol "Import RAB"
2. Klik "Download Template (.xlsx)"
3. File Excel dengan 6 sheet akan didownload

### **Isi Data**
1. **HSD_Material** - Isi daftar harga material (kode + harga)
2. **HSD_Upah** - Isi daftar harga upah (kode + harga)
3. **AHSP_Header** - Isi daftar pekerjaan analisa (kode + nama + satuan)
4. **AHSP_Detail** - Isi komponen setiap AHSP (referensi ke material/upah + koefisien)
5. **RAB_Header** - Isi struktur header RAB
6. **RAB_Detail** - Isi detail RAB, **kolom `ahsp_kode` harus diisi dengan kode dari AHSP_Header**

### **Upload & Import**
1. Klik tombol "Pilih File" → pilih file Excel yang sudah diisi
2. Klik "Import"
3. Sistem akan memproses otomatis:
   - ✅ Membuat HSD Material & Upah
   - ✅ Membuat AHSP Header & Detail (hitung total)
   - ✅ Membuat RAB Header & Detail
   - ✅ **Auto-link RAB_Detail ke AHSP** berdasarkan `ahsp_kode`
   - ✅ Auto-pull harga dari AHSP ke RAB_Detail

---

## Keuntungan Pendekatan Ini

| Fitur | Manfaat |
|-------|---------|
| **Single-file import** | Semua data dalam 1 file, tidak perlu import berulang |
| **Auto-linking** | RAB otomatis ter-link ke AHSP tanpa manual setup |
| **Price management** | Harga terpusat di HSD, mudah update global |
| **Calculation** | Total material/upah otomatis dihitung dari komponen |
| **Consistency** | Seluruh rantai tervalidasi dalam satu proses |
| **Reusability** | AHSP bisa dipakai di proyek lain |

---

## Catatan Penting

1. **Kode harus unik**: `HSD_Material.kode`, `HSD_Upah.kode`, `AHSP_Header.kode_pekerjaan`
2. **RAB_Detail.ahsp_kode harus match AHSP_Header.kode_pekerjaan** → satu-satunya yang di-link otomatis
3. **Harga di RAB_Detail boleh kosong** → sistem ambil dari AHSP
4. **Koefisien AHSP_Detail harus > 0** → untuk kalkulasi harga
5. **Jika ada perubahan harga AHSP** → gunakan fitur "Kalkulasi Ulang AHSP" di halaman RAB untuk sync ke RAB_Detail

---

## Testing

Saat Anda upload file Excel dengan benar, log akan menunjukkan:
- ✅ HSD Material & Upah diimport
- ✅ AHSP Header & Detail diimport + total dihitung
- ✅ RAB Header & Detail diimport + di-link ke AHSP
- ⚠️ Warning jika ada AHSP yang tidak ditemukan (RAB_Detail tidak ter-link)

---

**Implementasi selesai! Siap testing?**
