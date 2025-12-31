# 📍 Panduan Akses Uang Muka Penjualan

## Lokasi Menu

**Path:** `/uang-muka-penjualan`

Anda dapat mengakses fitur Uang Muka Penjualan melalui:

### Cara 1: URL Langsung
- Ketik di browser: `http://127.0.0.1:8000/uang-muka-penjualan` (untuk development)
- Atau: `https://yoursite.com/uang-muka-penjualan` (untuk production)

### Cara 2: Link Menu (Jika sudah ditambahkan)
Biasanya ada di menu Penjualan atau Sales, tapi bisa juga ditambahkan secara manual

---

## Fitur yang Tersedia

### 1. **Daftar Uang Muka Penjualan** 
**URL:** `/uang-muka-penjualan`

**Fungsi:**
- Melihat semua uang muka penjualan yang telah dibuat
- Filter berdasarkan proyek
- Filter berdasarkan status (Diterima, Sebagian, Lunas)

**Kolom Ditampilkan:**
- Nomor Bukti
- Proyek
- Tanggal
- Nominal
- Digunakan
- Sisa
- Status
- Aksi (Detail, Edit, Hapus)

**Status:**
- 🟢 **Diterima** - Belum digunakan
- 🟡 **Sebagian** - Sebagian telah digunakan
- 🔵 **Lunas** - Seluruhnya telah digunakan

---

### 2. **Buat Uang Muka Penjualan**
**URL:** `/uang-muka-penjualan/create`

**Cara Akses:**
- Klik tombol "Buat Uang Muka" di halaman daftar
- Atau buka langsung URL di atas

**Form Input:**
1. **Sales Order** - Pilih SO yang terkait (hanya SO yang belum punya UM)
2. **Proyek** - Auto-fill dari SO, bisa diubah
3. **Nomor Bukti** - Nomor identifikasi UM (unik)
4. **Tanggal** - Tanggal penerimaan UM
5. **Nominal** - Jumlah uang muka yang diterima
6. **Metode Pembayaran** - Transfer, Tunai, Cek, dll (opsional)
7. **Keterangan** - Catatan tambahan (opsional)

**Validasi:**
- Setiap field diperlukan (kecuali yang opsional)
- Nomor Bukti harus unik
- Sales Order hanya bisa digunakan 1 kali

---

### 3. **Lihat Detail Uang Muka Penjualan**
**URL:** `/uang-muka-penjualan/{id}`

**Akses:** Klik "Detail" di daftar UM

**Info yang Ditampilkan:**
- Semua data UM lengkap
- Riwayat penggunaan:
  - Nominal Awal
  - Telah Digunakan (tracking otomatis)
  - Sisa Tersedia
- Informasi waktu pembuatan/perubahan
- Pembuat/pembuat perubahan

---

### 4. **Edit Uang Muka Penjualan**
**URL:** `/uang-muka-penjualan/{id}/edit`

**Akses:** 
- Klik "Edit" di daftar UM
- Atau klik tombol "Edit" di halaman detail

**Yang Bisa Diubah:**
- Nomor Bukti
- Tanggal
- Nominal (dengan validasi minimum = jumlah yang sudah digunakan)
- Metode Pembayaran
- Keterangan

**Tidak Bisa Diubah:**
- Sales Order (immutable)
- Proyek (immutable)

**Validasi:**
- Nominal minimal = nominal_digunakan (jika sudah ada penggunaan)

---

### 5. **Hapus Uang Muka Penjualan**
**Akses:** Tombol "Hapus" di daftar atau detail

**Kondisi:**
- ✅ Bisa dihapus jika **belum digunakan** (nominal_digunakan = 0)
- ❌ **Tidak bisa** dihapus jika **sudah ada penggunaan**

---

## 🔗 Integrasi dengan Sertifikat Pembayaran

Ketika membuat **Sertifikat Pembayaran**, sistem otomatis akan:

1. ✅ Mengambil data UM dari Sales Order
2. ✅ Menampilkan info UM ke user sebelum submit
3. ✅ Mendeduction UM sesuai dengan potongan UM di sertifikat
4. ✅ **Update otomatis `nominal_digunakan`** di tabel `uang_muka_penjualan`
5. ✅ **Update otomatis `status`** (diterima → sebagian → lunas)

---

## 📊 Data Flow

```
1. Sales Order dibuat dengan uang_muka_persen
   ↓
2. Buat Uang Muka Penjualan (manual input)
   ├─ Nominal UM diterima
   ├─ Status: diterima
   └─ nominal_digunakan: 0
   ↓
3. Buat Sertifikat Pembayaran
   ├─ Pilih BAPP (auto-pull UM dari SO)
   ├─ Form menampilkan UM info
   └─ Submit
   ↓
4. Tracking Otomatis
   ├─ Hitung pemotongan_um_nilai
   ├─ Update nominal_digunakan += pemotongan_um_nilai
   ├─ Update status (diterima → sebagian → lunas)
   └─ Simpan ke database
   ↓
5. Verifikasi di Detail Uang Muka
   ├─ Lihat nominal_digunakan terupdate
   ├─ Lihat status terupdate
   └─ Lihat sisa UM tersisa
```

---

## 🎯 Use Cases

### Skenario 1: UM Pembelian SO Rp 100 Juta

```
1. Sales Order dibuat: Rp 100M dengan uang_muka_persen: 20%
2. Buat Uang Muka Penjualan:
   - Nominal: Rp 20,000,000 (20% × 100M)
   - Status: diterima

3. Buat Sertifikat Pembayaran Minggu Ke-1 (50% progress):
   - UM dipotong: 50% × 20M = Rp 10,000,000
   - Update: nominal_digunakan = 10M, status = sebagian

4. Buat Sertifikat Pembayaran Minggu Ke-2 (100% progress):
   - UM dipotong: 50% × 20M = Rp 10,000,000
   - Update: nominal_digunakan = 20M, status = lunas
```

---

## ⚙️ Admin Tasks

### Untuk Admin/Developer:

**Tambahkan Link Menu (Opsional):**
Edit `resources/views/layout/sidebar.blade.php` atau menu file Anda:

```blade
<li class="nav-item">
  <a href="{{ route('uang-muka-penjualan.index') }}" class="nav-link">
    <i class="icon-md" data-feather="dollar-sign"></i>
    <span>Uang Muka Penjualan</span>
  </a>
</li>
```

**Lokasi:** Biasanya di bawah menu Penjualan/Sales atau Keuangan

---

## 📱 Routes

**Semua routes yang tersedia:**

| Method | URL | Nama Route | Fungsi |
|--------|-----|-----------|--------|
| GET | `/uang-muka-penjualan` | `uang-muka-penjualan.index` | Daftar UM |
| GET | `/uang-muka-penjualan/create` | `uang-muka-penjualan.create` | Form buat UM |
| POST | `/uang-muka-penjualan/store` | `uang-muka-penjualan.store` | Simpan UM |
| GET | `/uang-muka-penjualan/{id}` | `uang-muka-penjualan.show` | Detail UM |
| GET | `/uang-muka-penjualan/{id}/edit` | `uang-muka-penjualan.edit` | Form edit UM |
| PUT | `/uang-muka-penjualan/{id}` | `uang-muka-penjualan.update` | Simpan edit UM |
| DELETE | `/uang-muka-penjualan/{id}` | `uang-muka-penjualan.destroy` | Hapus UM |

---

## 🔒 Permissions

Semua fitur memerlukan login. Pastikan user memiliki:
- Akses ke menu penjualan
- Izin untuk mengelola UM penjualan

---

## ✅ Checklist Implementasi

- ✅ Controller: `UangMukaPenjualanController.php` dibuat
- ✅ Routes di `routes/web.php` ditambahkan
- ✅ Views (index, create, show, edit) dibuat
- ✅ Model relations sudah ada
- ✅ Database table sudah ada
- ✅ Integrasi dengan Sertifikat Pembayaran berfungsi

---

## 🧪 Testing

**Test Checklist:**

1. ✅ Buka `/uang-muka-penjualan` → Halaman daftar
2. ✅ Klik "Buat Uang Muka" → Form create
3. ✅ Isi form dan submit → Simpan ke database
4. ✅ Klik "Detail" → Lihat info lengkap
5. ✅ Klik "Edit" → Ubah data
6. ✅ Buat Sertifikat Pembayaran → Tracking otomatis
7. ✅ Verifikasi `nominal_digunakan` terupdate
8. ✅ Verifikasi `status` terupdate

---

## 📞 Support

**Jika ada pertanyaan:**

1. **Bagaimana cara membuat UM penjualan baru?**
   → Buka `/uang-muka-penjualan/create` atau klik tombol "Buat Uang Muka"

2. **Kemana UM penjualan akan digunakan?**
   → Di Sertifikat Pembayaran, system secara otomatis akan mendeduction UM

3. **Bagaimana tracking UM?**
   → Otomatis saat membuat Sertifikat Pembayaran, lihat di detail UM

4. **Bisa diedit setelah ada penggunaan?**
   → Nominal minimal harus = jumlah yang sudah digunakan

5. **Bisa dihapus?**
   → Hanya jika belum ada penggunaan (nominal_digunakan = 0)

---

**Versi:** 1.0  
**Status:** ✅ Siap Digunakan  
**Last Updated:** December 31, 2025
