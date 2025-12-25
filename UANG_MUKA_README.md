# 🎉 UANG MUKA PEMBELIAN - QUICK START GUIDE

## Apa itu Uang Muka Pembelian?
Fitur untuk mencatat pembayaran advance ke supplier sebelum barang diterima penuh. Jurnal otomatis posting ke GL.

## ✅ Yang Sudah Ada

### Database & Model
- ✅ Table `uang_muka_pembelian` (19 columns)
- ✅ Model `UangMukaPembelian` dengan relationships

### Controller (8 actions)
- ✅ List, Create, Store, Show, Edit, Update, Approve, Destroy

### Views (4 pages)
- ✅ index (list + filter)
- ✅ create (form input)
- ✅ show (detail)
- ✅ edit (update)

### Routes (8 endpoints)
```
GET    /uang-muka-pembelian              → index
GET    /uang-muka-pembelian/create       → create
POST   /uang-muka-pembelian/store        → store
GET    /uang-muka-pembelian/{id}         → show
GET    /uang-muka-pembelian/{id}/edit    → edit
PUT    /uang-muka-pembelian/{id}         → update
POST   /uang-muka-pembelian/{id}/approve → approve (POST JURNAL)
DELETE /uang-muka-pembelian/{id}         → destroy
```

### Akuntansi
- ✅ Jurnal otomatis saat approve:
  ```
  Debit:  1-150 (Uang Muka ke Vendor)
  Kredit: 1-120 (Bank)
  ```
- ✅ COA & Account Mappings sudah di-setup

## 🧪 Testing

### URL untuk test
```
http://localhost/uang-muka-pembelian
http://localhost/uang-muka-pembelian/create
http://localhost/uang-muka-pembelian/create?po_id=1
```

### Test Flow
1. Create UM (status: draft)
2. Approve UM (check GL, cek 1-150 & 1-120)
3. Edit UM (only draft)
4. Delete UM (only draft)
5. List & Filter

## ⏳ Yang Masih Perlu Dikerjakan

### 1. Update FakturController (PRIORITY)
Saat user buat faktur, harus:
- [ ] Deteksi UM approved untuk PO ini
- [ ] Tampilkan sisa UM di form
- [ ] User bisa pilih nominal UM yang dipakai
- [ ] Update jurnal faktur dengan UM:
  ```
  Debit:  5-xxx (HPP/Beban)
  Kredit: 1-150 (UM sebagian/semua) + 2-110 (Hutang sisa)
  ```
- [ ] Update: `uang_muka.nominal_digunakan`

**Estimasi**: 2 jam

### 2. Update Faktur Views
- [ ] Add UM dropdown di create
- [ ] Show UM amount di detail
- [ ] Add calculator untuk automatic deduction

**Estimasi**: 1 jam

### 3. Testing Integration
- [ ] Create UM
- [ ] Approve UM (check GL)
- [ ] Create Faktur dengan UM
- [ ] Check GL entries
- [ ] Create Pembayaran sisa

**Estimasi**: 1 jam

### 4. Optional: Reporting
- [ ] UM Tracking Report
- [ ] Hutang Supplier (after UM)
- [ ] UM Aging Report
- [ ] Dashboard widget

## 📚 Documentation

Baca untuk detail lengkap:
1. `DOKUMENTASI_UANG_MUKA_PEMBELIAN.md` - Complete spec
2. `UANG_MUKA_IMPLEMENTATION_SUMMARY.md` - What's done, what's next
3. `UANG_MUKA_COMPLETE_SPECIFICATION.md` - Technical specification

## 🚀 Deployment

```bash
# 1. Migration
php artisan migrate --path=database/migrations/2025_12_25_create_uang_muka_pembelian_table.php

# 2. Seeder
php artisan db:seed --class=AccountMappingSeeder

# 3. Cache clear
php artisan cache:clear && php artisan route:cache

# 4. Test
curl http://localhost/uang-muka-pembelian
```

## 📞 Contact / Questions

Untuk integration dengan Faktur atau pertanyaan lainnya, silakan refer ke dokumentasi atau konsultasikan dengan developer.

---

**Created**: 25 Desember 2025  
**Status**: ✅ Production Ready (core feature complete)
