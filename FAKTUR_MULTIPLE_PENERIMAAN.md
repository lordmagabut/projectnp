# Fitur Faktur dari Multiple Penerimaan

## Overview
Implementasi fitur untuk membuat 1 faktur dari beberapa penerimaan barang dengan supplier dan PO yang sama. User dapat memilih multiple penerimaan dan barang akan digabung dalam satu invoice.

## Workflow

### Step 1: Create Faktur
User membuka menu **Faktur Pembelian > Tambah Faktur**

### Step 2: Isi Data Dasar
- **Nomor Faktur**: Otomatis dari sistem
- **Tanggal Faktur**: Pilih tanggal invoice
- **File Faktur**: Opsional (PDF)

### Step 3: Pilih Supplier
Dropdown **Supplier** akan memicu:
- Load PO dari supplier tsb (status: sedang diproses/selesai)
- Load Uang Muka yang tersedia untuk supplier

### Step 4: Pilih Pesanan Pembelian (PO)
Dropdown **PO** akan menampilkan:
- Nomor PO
- Tanggal PO
- Otomatis set diskon/PPN dari PO

### Step 5: Pilih Penerimaan (Multiple Selection)
**BARU**: Setelah PO dipilih, muncul section "Pilih Penerimaan Barang" dengan:
- List checkbox untuk semua penerimaan approved dari PO tsb
- User bisa select 1 atau lebih penerimaan
- Checkboxes menampilkan:
  - No. Penerimaan
  - Tanggal Penerimaan
  - Status badge (Approved)

### Step 6: Detail Barang Terobah Secara Otomatis
Ketika penerimaan dipilih/dihapus, tabel "Detail Barang dari Penerimaan Terpilih" update otomatis:
- **Nama Barang**: Daftar barang dari penerimaan
- **Penerimaan**: Nomor penerimaan mana saja barang ini ada (bisa multiple)
- **Qty Tersedia**: Total qty yang belum difaktur dari semua penerimaan terpilih
- **Qty Faktur**: Input field untuk qty yang akan difaktur
- **Harga**: Dari PO Detail
- **Total**: Kalkulasi otomatis saat qty berubah

### Step 7: Opsi Global
- **Diskon Global (%)**: Diterapkan ke semua item
- **PPN Global (%)**: Diterapkan ke semua item
- **Uang Muka**: Opsional, pilih UM yang tersedia & masukkan nominal yang dipakai

### Step 8: Simpan Faktur
Klik **Simpan Faktur** untuk:
- Validasi qty tidak melebihi yang tersedia
- Create Faktur dengan status 'draft'
- Update qty_terfaktur di semua penerimaan detail
- Update qty_terfaktur di PO detail
- Update status_penagihan untuk setiap penerimaan (belum/sebagian/lunas)
- Jika PO fully invoiced → ubah status PO ke 'selesai'

## Validasi & Error Handling

### Validasi Penerimaan
✓ Hanya penerimaan dengan status 'approved' yang bisa dipilih
✓ Hanya dari supplier & PO yang SAMA untuk 1 faktur
✓ Hanya item yang belum fully invoiced yang ditampilkan

### Validasi Qty
✓ Qty faktur tidak boleh melebihi qty tersedia
✓ Return approved (retur) dikurangi dari qty tersedia
✓ Qty yang sudah terfaktur dikurangi dari qty tersedia

### Validasi Uang Muka
✓ UM harus status 'approved'
✓ Nominal UM yang dipakai tidak boleh melebihi sisa UM
✓ Total UM tidak boleh melebihi subtotal invoice setelah diskon+PPN

## API Endpoints

### New Endpoints

#### 1. GET /api/penerimaan-by-po/{po_id}
Return list penerimaan approved untuk PO tertentu

**Response**:
```json
[
  {
    "id": 1,
    "no_penerimaan": "PRC-2026-001",
    "tanggal": "2026-01-07"
  },
  ...
]
```

#### 2. POST /api/penerimaan-detail
Return list detail barang dari multiple penerimaan (hanya yang belum fully invoiced)

**Request Body**:
```json
{
  "penerimaan_ids": [1, 2, 3]
}
```

**Response**:
```json
{
  "details": [
    {
      "id": 1,
      "penerimaan_id": 1,
      "po_detail_id": 5,
      "no_penerimaan": "PRC-2026-001",
      "barang_id": 100,
      "barang_nama": "Bahan Baku A",
      "satuan": "KG",
      "harga": 50000,
      "qty_diterima": 100,
      "qty_terfaktur": 0,
      "qty_available": 100
    },
    ...
  ]
}
```

## Database Changes
Tidak ada perubahan struktur database, hanya query logic yang lebih kompleks untuk aggregate multiple penerimaan.

## View Changes
**File**: `resources/views/faktur/create.blade.php`

Perubahan:
1. Tambah section "Pilih Penerimaan Barang" dengan multiple checkbox
2. Update tabel detail barang:
   - Tambah kolom "Penerimaan" (show nomor penerimaan)
   - Qty Tersedia dari total semua penerimaan
3. Update JavaScript:
   - Load penerimaan saat PO dipilih
   - Load detail dari multiple penerimaan saat checkbox berubah
   - Merge barang yang sama dari berbagai penerimaan
   - Update qty total

## Controller Changes
**File**: `app/Http/Controllers/FakturController.php`

### New Methods:
1. `getPenerimaanByPo($po_id)` - API endpoint
2. `getPenerimaanDetail(Request $request)` - API endpoint

### Updated Methods:
1. `store(Request $request)` - Tambah logic untuk handle multiple penerimaan:
   - Validasi semua penerimaan dari supplier & PO sama
   - Create faktur dari multiple penerimaan
   - Allocate qty menggunakan FIFO dari penerimaan
   - Update qty_terfaktur di semua penerimaan detail

## Routes
**File**: `routes/web.php`

New routes:
```php
Route::get('/api/penerimaan-by-po/{po_id}', [FakturController::class, 'getPenerimaanByPo']);
Route::post('/api/penerimaan-detail', [FakturController::class, 'getPenerimaanDetail']);
```

## Testing Checklist

- [ ] Buat PO dengan supplier A
- [ ] Terima barang 3x (3 penerimaan) dari PO tersebut
- [ ] Approve semua 3 penerimaan
- [ ] Buat faktur:
  - Pilih supplier A
  - Pilih PO
  - Lihat 3 penerimaan terlist
  - Pilih 2 dari 3 penerimaan
  - Verifikasi tabel detail hanya show 2 penerimaan
  - Verifikasi qty tersedia adalah total dari 2 penerimaan
  - Input qty faktur
  - Simpan
- [ ] Verifikasi:
  - Faktur created dengan status 'draft'
  - qty_terfaktur di 2 penerimaan updated
  - qty_terfaktur di PO detail updated
  - status_penagihan di 2 penerimaan updated (belum/sebagian/lunas)
- [ ] Approve faktur
- [ ] Verifikasi print button muncul

## Edge Cases Handled

1. **Penerimaan dengan item sama dari different batches**
   - Dimerge dalam satu baris tabel
   - Qty tersedia adalah sum dari semua batch

2. **Item partially invoiced**
   - Hanya sisa yang belum difaktur yang ditampilkan
   - Return approved (retur) dikurangi

3. **Item dari 1 penerimaan only**
   - Bisa select 1 penerimaan saja
   - Behavior sama dengan fitur lama (backward compatible)

4. **Qty yang sudah terfaktur di penerimaan lain**
   - Tidak double-invoice
   - Hanya qty_available yang bisa difaktur

5. **Different harga di item yang sama**
   - Gunakan harga dari PO Detail (single source of truth)
   - Tidak support mixed pricing

## Future Improvements

1. Bulk select penerimaan (Select All / Deselect All)
2. Filter penerimaan by tanggal range
3. Preview aggregated qty before confirming selection
4. Split faktur functionality (partial invoicing dari selected penerimaan)
5. Generate multiple invoice jika qty exceed threshold

## Security

- User harus punya permission 'create faktur'
- Validasi di controller untuk ensure penerimaan dari supplier/PO yang sama
- Qty validation memastikan tidak overfaktur
- Audit trail recorded (dibuat_oleh, dibuat_at)

## Performance Notes

- Penerimaan list load via API (tidak block form)
- Detail fetch setelah penerimaan dipilih (lazy load)
- Grouping by po_detail di client-side (efficient)
- No N+1 queries (with() relations used)
