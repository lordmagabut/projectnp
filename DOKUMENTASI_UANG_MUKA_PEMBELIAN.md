# Fitur Uang Muka Pembelian (Advance Payment)

## 📋 Alur Lengkap Uang Muka Pembelian

### 1. BUAT UANG MUKA
```
User → Menu Pembelian → Uang Muka Pembelian → Buat Uang Muka
Input:
  - PO (wajib)
  - Tanggal UM
  - Nominal UM
  - Metode Pembayaran (transfer, cek, tunai, giro)
  - Detail Bank (jika tidak tunai)
  - File Bukti (opsional)
Status: Draft
```

### 2. APPROVE UANG MUKA
```
User → Lihat detail UM → Klik "Approve"
Status: Draft → Approved

JURNAL YANG TERBUAT (saat Approve):
Debit:  Uang Muka ke Vendor (1-150)    Rp XXX
Kredit: Bank (1-120)                    Rp XXX

Posting otomatis ke General Ledger
```

### 3. BUAT FAKTUR (dengan Uang Muka)
```
User → Buat Faktur dari PO atau Penerimaan
Sistem otomatis:
  - Deteksi UM yang approved untuk PO ini
  - Tampilkan sisa UM tersedia
  - User bisa pilih: gunakan UM atau tidak

Jika menggunakan UM:
  - Nominal UM dikurangkan dari total faktur (sebagai kredit)
  - Update: uang_muka_pembelian.nominal_digunakan += nominal_dipakai
```

### 4. POSTING JURNAL FAKTUR (dengan UM)
```
Jika faktur dibuat dengan menggunakan UM:

DEBIT:
  - Beban/Persediaan/HPP        Rp XXX (per item)

KREDIT:
  - Uang Muka ke Vendor (1-150) Rp AAA (sebagian/semua UM)
  - Hutang Usaha (2-110)        Rp BBB (sisa setelah UM)
  
Dimana: Rp XXX = Rp AAA + Rp BBB
```

### 5. PEMBAYARAN HUTANG SISA
```
User → Buat Pembayaran
Nominal bayar = Total Faktur - UM yang digunakan

Misal:
  Total Faktur = Rp 100.000.000
  UM dipakai = Rp 25.000.000
  Sisa bayar = Rp 75.000.000

JURNAL Pembayaran:
Debit:  Hutang Usaha (2-110)     Rp 75.000.000
Kredit: Bank (1-120)              Rp 75.000.000
```

---

## 💾 Struktur Database

### Tabel: uang_muka_pembelian
```
- id                    : Primary Key
- no_uang_muka          : Nomor UM (format: UM-2025-0001)
- tanggal               : Tanggal UM dibuat
- po_id                 : Referensi ke PO
- id_supplier           : Referensi ke supplier
- nama_supplier         : Nama supplier (snapshot)
- id_perusahaan         : Referensi ke perusahaan
- id_proyek             : Referensi ke proyek (opsional)
- nominal               : Nilai total UM
- metode_pembayaran     : transfer/cek/tunai/giro
- no_rekening_bank      : No. rekening tujuan transfer
- nama_bank             : Nama bank
- tanggal_transfer      : Kapan dana ditransfer
- no_bukti_transfer     : No. TRF / No. Cek / No. Giro
- keterangan            : Catatan tambahan
- status                : draft / approved
- nominal_digunakan     : Tracking: berapa UM sudah digunakan di faktur
- file_path             : Lokasi file bukti PDF
- created_at            : Timestamp dibuat
- updated_at            : Timestamp diupdate
```

**Relasi:**
- Belongs to PO
- Belongs to Supplier
- Belongs to Perusahaan
- Belongs to Proyek
- Morph to Jurnal (untuk posting GL)

---

## 🔐 Controller Actions

### UangMukaPembelianController

```php
// List all UM dengan filter status & perusahaan
public function index(Request $request)

// Form input UM baru
public function create(Request $request)  // bisa terima ?po_id=X

// Simpan UM baru (status: draft)
public function store(Request $request)

// Lihat detail UM
public function show($id)

// Form edit UM (hanya draft)
public function edit($id)

// Update UM (hanya draft)
public function update(Request $request, $id)

// Approve UM (post jurnal, ubah status → approved)
public function approve(Request $request, $id)

// Hapus UM (hanya draft)
public function destroy($id)
```

---

## 🎯 Akun / COA yang Dibutuhkan

Sudah tersedia di CoaSeeder (Indonesia Standard):

| No. Akun | Nama Akun | Tipe |
|----------|-----------|------|
| 1-150 | Uang Muka ke Vendor | Aset |
| 1-120 | Bank | Aset |
| 2-110 | Hutang Usaha | Liabilitas |
| 2-130 | Uang Muka Pelanggan (DP Owner) | Liabilitas |

**Account Mappings yang di-setup:**
- `uang_muka_vendor` → 1-150
- `kas_bank` → 1-120
- `hutang_usaha` → 2-110

---

## 🔄 Integrasi dengan Faktur

### Di FakturController.store()

```php
// Ketika user membuat faktur dengan UM:
$uangMukaDisepakati = $request->input('uang_muka_dipakai') ?? 0;

if ($uangMukaDisepakati > 0) {
    // Validasi: jangan lebih dari sisa UM
    $uangMuka = UangMukaPembelian::findOrFail($uangMukaId);
    if ($uangMukaDisepakati > $uangMuka->sisa_uang_muka) {
        throw new Exception("UM yang dipakai melebihi sisa");
    }
    
    // Update tracking
    $uangMuka->updateNominalDigunakan($uangMukaDisepakati);
    
    // Simpan di faktur detail untuk referensi
    // $faktur->uang_muka_dipakai = $uangMukaDisepakati;
    
    // Posting jurnal: 
    //   Debit: Beban/Persediaan
    //   Kredit: UM ke Vendor + Hutang Usaha
}
```

---

## 🎨 Views

### 1. resources/views/uang-muka-pembelian/index.blade.php
- List UM dengan status badge
- Filter by status & perusahaan
- Tombol action: view, edit, delete
- Tampilkan sisa UM per record

### 2. resources/views/uang-muka-pembelian/create.blade.php
- Form input UM
- Auto-load PO (jika dari PO show)
- Input metode pembayaran + detail bank
- Upload file bukti PDF
- Info PO sidebar

### 3. resources/views/uang-muka-pembelian/show.blade.php
- Detail UM lengkap
- Tampilkan nominal, digunakan, sisa
- Progress bar tracking
- Tombol Approve/Edit/Delete (jika draft)
- Link ke jur nal yang terbuat

### 4. resources/views/uang-muka-pembelian/edit.blade.php
- Form edit (hanya untuk draft)
- Pre-fill dari data existing
- History file (download old)

---

## 📊 Reporting

### General Ledger Report akan menampilkan:
- Saldo awal 1-150 (Uang Muka ke Vendor)
- Mutasi: +Rp (saat approve) - Rp (saat digunakan di faktur)
- Saldo akhir

### Hutang Usaha Report:
- Menampilkan nominal setelah dikurangi UM
- Per supplier, per PO

### UM Tracking Report (TODO):
- Nominal UM per PO
- Sisa UM tersedia
- Berapa sudah dipakai di faktur

---

## ⚠️ Validasi & Constraint

```php
// UM hanya bisa dibuat untuk PO status >= 'Sedang Diproses'
if ($po->status === 'draft') {
    throw new Exception("PO draft tidak bisa memiliki UM");
}

// UM hanya bisa di-approve jika nominal > 0
if ($uangMuka->nominal <= 0) {
    throw new Exception("Nominal UM harus lebih dari 0");
}

// Faktur hanya bisa gunakan UM approved
if ($uangMuka->status !== 'approved') {
    throw new Exception("UM harus sudah di-approve");
}

// Total UM yang dipakai faktur tidak boleh > sisa UM
$sisaUM = $uangMuka->nominal - $uangMuka->nominal_digunakan;
if ($requestedUM > $sisaUM) {
    throw new Exception("UM yang diminta melebihi sisa");
}

// Tidak bisa delete UM yang sudah approved
if ($uangMuka->status === 'approved') {
    throw new Exception("UM approved tidak bisa dihapus");
}
```

---

## 🚀 Cara Menggunakan

### Skenario: PO Rp 100 juta, UM 25 juta

**Langkah 1: Buat UM**
```
1. PO Vendor A: Rp 100.000.000 (approved)
2. Ke Uang Muka Pembelian → Buat UM
3. Input:
   - PO: Vendor A (Rp 100 juta)
   - Tanggal: 20/12/2025
   - Nominal: Rp 25.000.000
   - Metode: Transfer ke BCA 1234567890
   - No. Bukti: TRF-20251220-001234
4. Simpan (status: draft)
```

**Langkah 2: Approve UM**
```
1. Lihat detail UM
2. Klik "Approve"
3. Jurnal otomatis:
   Debit:  1-150 (UM ke Vendor)      Rp 25.000.000
   Kredit: 1-120 (Bank)               Rp 25.000.000
4. Status → Approved
```

**Langkah 3: Buat Faktur (Penerimaan Barang)**
```
1. Barang diterima, buat penerimaan
2. Terima barang, approve penerimaan
3. Buat faktur dari penerimaan:
   - Subtotal: Rp 100.000.000
   - UM dipakai: Rp 25.000.000 ← (sistem auto-suggest)
   - Hutang sisa: Rp 75.000.000
4. Posting jurnal:
   Debit:  5-110 (HPP Proyek)             Rp 100.000.000
   Kredit: 1-150 (UM ke Vendor)           Rp 25.000.000
   Kredit: 2-110 (Hutang Usaha)           Rp 75.000.000
```

**Langkah 4: Pembayaran Hutang Sisa**
```
1. Buat pembayaran untuk faktur
2. Nominal bayar otomatis: Rp 75.000.000
3. Pilih akun bank
4. Posting jurnal:
   Debit:  2-110 (Hutang Usaha)       Rp 75.000.000
   Kredit: 1-120 (Bank)                Rp 75.000.000
```

**Hasil Akhir GL (1-150 Uang Muka):**
```
Saldo Awal        : Rp 0
Debit (UM masuk)  : Rp 25.000.000
Kredit (UM pakai) : Rp 25.000.000
Saldo Akhir       : Rp 0
```

---

## 🔗 Routes

```php
// Index
GET  /uang-muka-pembelian                      → index
GET  /uang-muka-pembelian?status=draft&id_perusahaan=1

// Create & Store
GET  /uang-muka-pembelian/create               → create
GET  /uang-muka-pembelian/create?po_id=5       → create (pre-fill PO)
POST /uang-muka-pembelian/store                → store

// Show & Edit
GET  /uang-muka-pembelian/{id}                 → show
GET  /uang-muka-pembelian/{id}/edit            → edit
PUT  /uang-muka-pembelian/{id}                 → update

// Actions
POST /uang-muka-pembelian/{id}/approve         → approve (post jurnal)
DELETE /uang-muka-pembelian/{id}               → destroy
```

---

## 🎓 Testing Checklist

- [ ] Buat UM baru (status draft)
- [ ] Edit UM (status draft)
- [ ] Approve UM → jurnal otomatis
- [ ] Lihat GL: 1-150 & 1-120 ter-update
- [ ] Buat faktur tanpa UM
- [ ] Buat faktur dengan UM
- [ ] Update nominal_digunakan UM
- [ ] Lihat GL: perubahan debit/kredit
- [ ] Buat pembayaran sisa hutang
- [ ] Lihat GL lengkap
- [ ] Filter UM by status
- [ ] Filter UM by perusahaan
- [ ] Download file bukti PDF

---

## 📝 Catatan Developer

1. **Relasi Polymorphic**: UangMukaPembelian → Jurnal (saat approve)
2. **Auto-increment No. UM**: Format UM-YYYY-NNNN (per tahun)
3. **Tracking nominal_digunakan**: Update saat faktur dibuat, tidak saat dibayar
4. **COA Fallback**: Jika barang tidak punya COA, gunakan account mapping default
5. **Validasi Status**: Hanya UM approved yang bisa dipakai di faktur
6. **Audit Trail**: created_at, updated_at, jika ada approval user bisa ditambah

---

**Last Updated**: 25 Desember 2025
**Status**: ✅ Implemented & Ready for Testing
