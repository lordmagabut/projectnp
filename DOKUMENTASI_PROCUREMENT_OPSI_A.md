# DOKUMENTASI ALUR PROCUREMENT - OPSI A

## 📋 Alur Lengkap Purchase Order hingga Pembayaran

### 1. BUAT PURCHASE ORDER (PO)
```
User → Buat PO → Input barang & qty
Status: Draft
```

### 2. APPROVE PO
```
User → Print/Approve PO
Status: Draft → Sedang Diproses
Tombol aktif:
  - ✅ Terima Barang
  - ⚪ Buat Faktur (disabled, belum ada penerimaan)
```

### 3. PENERIMAAN BARANG (WAJIB)
```
User → Klik "Terima Barang" → Input qty yang benar-benar diterima
Update: po_detail.qty_diterima += qty_diterima
Status: Penerimaan Draft

Contoh:
PO: 100 unit
Terima: 80 unit (sebagian)
qty_diterima = 80
Sisa belum diterima = 20
```

**Catatan Penting:**
- ✅ Bisa terima barang bertahap (partial receipt)
- ✅ Qty diterima bisa < qty PO (barang kurang dari pesanan)
- ✅ Bisa buat multiple penerimaan sampai total = qty PO

### 4. RETUR PEMBELIAN (Opsional)
```
Jika ada barang rusak/tidak sesuai:
User → Klik "Retur" dari halaman Penerimaan → Input qty retur
Update: po_detail.qty_diretur += qty_retur

Contoh:
Diterima: 80 unit
Rusak: 5 unit
qty_diretur = 5
Nett diterima baik = 75 unit
```

**Jurnal Retur (saat Approve):**
```
Debit:  Hutang Usaha         Rp XXX
Kredit: Persediaan/Beban     Rp XXX
```

### 5. BUAT FAKTUR (Berdasarkan Penerimaan)
```
Validasi OPSI A: Qty faktur ≤ (qty_diterima - qty_terfaktur)

User → Klik "Buat Faktur"
System cek:
  - ❌ Jika belum ada penerimaan → Tombol disabled
  - ✅ Jika sudah ada penerimaan → Tampilkan form

Form menampilkan:
  - Qty PO
  - Qty Diterima (hijau)
  - Sudah Difaktur
  - Sisa Bisa Difaktur ← Hanya ini yang bisa difaktur!

Update: po_detail.qty_terfaktur += qty_faktur
Status: Faktur Draft
```

**Contoh Perhitungan:**
```
PO: 100 unit
Diterima: 80 unit
Sudah difaktur: 0 unit
Sisa bisa difaktur: 80 unit ← Max yang bisa input

Jika user coba input faktur 90 unit:
❌ ERROR: "Qty faktur (90) melebihi qty yang sudah diterima (80)"
```

### 6. APPROVE FAKTUR
```
User → Approve Faktur
Status: Draft → Sedang Diproses
```

**Jurnal yang Terbuat:**
```
Debit:  Beban/Persediaan/HPP    Rp XXX (per item sesuai COA barang)
Kredit: Hutang Usaha            Rp XXX
```

### 7. PEMBAYARAN
```
User → Buat Pembayaran → Pilih akun Kas/Bank
Update: 
  - faktur.sudah_dibayar += nominal
  - faktur.status_pembayaran = 'sebagian'/'lunas'
```

**Jurnal Pembayaran:**
```
Debit:  Hutang Usaha      Rp XXX
Kredit: Kas/Bank          Rp XXX
```

---

## 🔒 VALIDASI OPSI A

### Aturan Ketat:
1. ✅ **Tidak bisa faktur sebelum terima barang**
   - Tombol "Buat Faktur" disabled jika qty_diterima = 0
   
2. ✅ **Qty faktur tidak boleh melebihi qty yang diterima**
   ```php
   if ($qtyFaktur > ($qty_diterima - $qty_terfaktur)) {
       throw Exception("Qty faktur melebihi penerimaan");
   }
   ```

3. ✅ **Tracking terpisah untuk penerimaan dan faktur**
   - qty_diterima = Tracking fisik barang
   - qty_terfaktur = Tracking billing/tagihan
   - qty_diretur = Tracking barang rusak

4. ✅ **Bisa terima barang bertahap, lalu faktur bertahap**
   ```
   Contoh:
   PO: 100 unit
   
   Penerimaan 1: 50 unit → qty_diterima = 50
   Faktur 1: 50 unit → qty_terfaktur = 50
   
   Penerimaan 2: 30 unit → qty_diterima = 80
   Faktur 2: 30 unit → qty_terfaktur = 80
   
   Penerimaan 3: 20 unit → qty_diterima = 100
   Faktur 3: 20 unit → qty_terfaktur = 100
   ```

---

## 📊 STATUS FLOW DIAGRAM

```
┌─────────────────┐
│   PO (Draft)    │
└────────┬────────┘
         │ Approve/Print
         ▼
┌─────────────────────────┐
│ PO (Sedang Diproses)    │
│ qty_diterima = 0        │
│ Tombol Faktur: DISABLED │
└────────┬────────────────┘
         │
         ▼
┌──────────────────────┐
│ Terima Barang        │
│ qty_diterima += 50   │
└────────┬─────────────┘
         │
         ▼
┌────────────────────────┐
│ PO (Sedang Diproses)   │
│ qty_diterima = 50      │
│ qty_terfaktur = 0      │
│ Tombol Faktur: ENABLED │
└────────┬───────────────┘
         │
         ▼
┌─────────────────────────┐
│ Buat Faktur (max 50)    │
│ qty_terfaktur = 50      │
└────────┬────────────────┘
         │ Approve
         ▼
┌─────────────────────────┐
│ Faktur (Sedang Diproses)│
│ Jurnal: D=Beban K=Hutang│
└────────┬────────────────┘
         │
         ▼
┌──────────────────┐
│ Buat Pembayaran  │
│ Jurnal: D=Hutang │
│         K=Kas    │
└────────┬─────────┘
         │
         ▼
┌──────────────┐
│ Faktur LUNAS │
└──────────────┘
```

---

## ⚠️ ERROR HANDLING

### Error 1: Faktur Tanpa Penerimaan
```
Kondisi: qty_diterima = 0
Aksi: Tombol "Buat Faktur" disabled
Pesan: "Belum ada penerimaan barang"
```

### Error 2: Qty Faktur Melebihi Penerimaan
```
Input: qty_faktur = 100
Data: qty_diterima = 80, qty_terfaktur = 0
Error: "Item XXX: Qty faktur (100) melebihi qty yang sudah 
        diterima dan belum difaktur (80)"
```

### Error 3: Semua Sudah Difaktur
```
Kondisi: qty_terfaktur >= qty_diterima untuk semua item
Redirect: Ke halaman faktur index
Pesan: "Semua barang yang sudah diterima telah difaktur"
```

---

## 💡 BEST PRACTICES

1. **Terima barang dulu, baru faktur**
   - Sesuai prinsip akuntansi: hutang timbul saat barang diterima
   
2. **Catat penerimaan dengan akurat**
   - Qty yang dicatat harus sesuai fisik
   - Cek kualitas sebelum input
   
3. **Gunakan fitur retur untuk barang rusak**
   - Jangan langsung kurangi qty_diterima
   - Buat dokumen retur untuk audit trail
   
4. **Faktur bisa bertahap**
   - Tidak harus sekaligus
   - Bisa sesuai dengan term payment

---

## 📌 FILE YANG DIMODIFIKASI

1. **Controller:**
   - `app/Http/Controllers/FakturController.php`
     - `createFromPo()` → Validasi penerimaan
     - `store()` → Validasi qty_diterima

2. **View:**
   - `resources/views/faktur/create-from-po.blade.php`
     - Tampilkan kolom qty_diterima
     - Hitung sisa bisa difaktur
   
   - `resources/views/po/index.blade.php`
     - Kondisional tombol "Buat Faktur"

3. **Migration:**
   - `2025_12_25_000001_create_penerimaan_pembelian_tables.php`
     - Tambah kolom qty_diterima, qty_diretur di po_detail

---

## 🔧 TESTING SCENARIO

### Test Case 1: Normal Flow
```
1. Buat PO 100 unit
2. Terima 100 unit
3. Faktur 100 unit ✅
4. Approve faktur ✅
5. Bayar ✅
```

### Test Case 2: Partial Receipt & Partial Invoice
```
1. Buat PO 100 unit
2. Terima 50 unit
3. Faktur 50 unit ✅
4. Terima 50 unit lagi
5. Faktur 50 unit ✅
6. Total terfaktur = 100 ✅
```

### Test Case 3: Error Validation
```
1. Buat PO 100 unit
2. Belum terima barang
3. Coba buat faktur → Tombol disabled ✅
4. Terima 50 unit
5. Coba faktur 60 unit → Error ❌
   "Qty faktur melebihi penerimaan"
```

### Test Case 4: Retur Flow
```
1. Buat PO 100 unit
2. Terima 100 unit
3. Retur 10 unit (rusak)
4. Maksimal faktur = 100 unit (qty_diterima)
   Bukan 90 unit, karena validasi pakai qty_diterima
5. Tapi secara logika bisnis, seharusnya faktur 90 saja
```

**Note:** Jika ingin retur mengurangi qty yang bisa difaktur, 
perlu modifikasi validasi menjadi:
```php
$sisaBisaDifaktur = ($qty_diterima - $qty_diretur) - $qty_terfaktur;
```

---

Dibuat: {{ date('Y-m-d H:i:s') }}
Versi: 1.0 (OPSI A - Strict Validation)
