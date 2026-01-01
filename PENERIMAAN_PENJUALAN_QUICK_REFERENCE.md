# Penerimaan Penjualan - Quick Reference

## Menu Access
**Sidebar:** Penjualan → Penerimaan Penjualan

## Available Routes

| Route | Method | Purpose |
|-------|--------|---------|
| `/penerimaan-penjualan` | GET | List semua penerimaan |
| `/penerimaan-penjualan/create` | GET | Form buat penerimaan baru |
| `/penerimaan-penjualan` | POST | Simpan penerimaan baru (store) |
| `/penerimaan-penjualan/{id}` | GET | Lihat detail penerimaan |
| `/penerimaan-penjualan/{id}/approve` | POST | Setujui penerimaan |
| `/penerimaan-penjualan/{id}` | DELETE | Hapus penerimaan |

## Database Table: penerimaan_penjualan

```sql
CREATE TABLE penerimaan_penjualan (
    id BIGINT UNSIGNED PRIMARY KEY,
    no_bukti VARCHAR(255) UNIQUE NOT NULL,
    tanggal DATE NOT NULL,
    faktur_penjualan_id BIGINT UNSIGNED NOT NULL (FK),
    nominal DECIMAL(20,2) NOT NULL,
    metode_pembayaran VARCHAR(50) NOT NULL,
    keterangan TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    dibuat_oleh_id BIGINT UNSIGNED,
    disetujui_oleh_id BIGINT UNSIGNED,
    tanggal_disetujui TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (faktur_penjualan_id) REFERENCES faktur_penjualan(id) ON DELETE CASCADE
);
```

## Model Usage

```php
// Get penerimaan
$penerimaan = PenerimaanPenjualan::find($id);

// Get all penerimaan for a faktur
$penerimaan = PenerimaanPenjualan::where('faktur_penjualan_id', $fakturId)->get();

// Get approved penerimaan only
$approved = PenerimaanPenjualan::where('status', 'approved')->get();

// Get draft penerimaan only
$draft = PenerimaanPenjualan::where('status', 'draft')->get();

// Generate nomor bukti
$nomorBukti = PenerimaanPenjualan::generateNomorBukti();

// Calculate total received for a faktur
$totalDiterima = PenerimaanPenjualan::where('faktur_penjualan_id', $fakturId)
    ->whereIn('status', ['draft', 'approved'])
    ->sum('nominal');
```

## Nomor Bukti Format
**Format:** `PN-YYMMDD-XXX`

Example:
- PN-260101-001 (first on Jan 1, 2026)
- PN-260101-002 (second on Jan 1, 2026)
- PN-260102-001 (first on Jan 2, 2026)

## Status Flow

```
Created (draft)
    ↓
[User approves via button]
    ↓
Approved
```

## Pembayaran Status Tracking

Automatically updated when penerimaan is created/deleted:

```
belum_dibayar  → Total diterima = 0
sebagian       → 0 < Total diterima < Total faktur
lunas          → Total diterima ≥ Total faktur
```

## Metode Pembayaran Options
- Tunai
- Transfer Bank
- Cek
- Giro
- Kartu Kredit

*(Can be extended in controller)*

## Form Validation Rules

```php
'tanggal' => 'required|date',
'faktur_penjualan_id' => 'required|exists:faktur_penjualan,id',
'nominal' => 'required|numeric|min:0.01',
'metode_pembayaran' => 'required|string|max:50',
'keterangan' => 'nullable|string',
```

## Workflow Example

1. **Sales Order** → **Sertifikat Pembayaran** (with UM rule)
   - UM mode: utuh (100%) or proporsional (cumulative %)

2. **Approve Sertifikat** → Auto-generates **Faktur Penjualan**
   - Status: draft → approved
   - Amounts: subtotal, ppn, total, uang_muka_dipakai calculated

3. **Create Penerimaan** → Record customer payments
   - Can have multiple payments per faktur
   - Each payment updates faktur's status_pembayaran

4. **Approve Penerimaan** → Finalize payment recording
   - Status: draft → approved
   - Recorded who approved and when

## Important Notes

⚠️ **Only draft penerimaan can be deleted**
- Once approved, use system admin to modify/delete if needed

⚠️ **Multiple payments per faktur supported**
- Customer can make partial/installment payments
- Each penerimaan recorded separately
- Status automatically updates based on total received

⚠️ **Nomor Bukti must be unique**
- Auto-generated, so should never have duplicates
- Manual entry not allowed (system generates)

⚠️ **Foreign Key Cascade Delete**
- If faktur penjualan is deleted, all related penerimaan will be deleted
- Be careful when deleting faktur!

## Performance Tips

- Index on `faktur_penjualan_id` and `tanggal` for fast queries
- Pagination on index: 20 per page (configurable)
- Eager load relations: `with(['fakturPenjualan', 'pembuatnya', 'penyetujunya'])`

## Integration Points

- **FakturPenjualan Model**: Has relation `penerimaanPenjualan()`
- **Sidebar Menu**: Links from Penjualan submenu
- **User Model**: Relations via `dibuat_oleh_id` and `disetujui_oleh_id`

## Future Enhancements

- [ ] Add Excel export for penerimaan list
- [ ] Add PDF print for bukti penerimaan
- [ ] Add email notification on approval
- [ ] Add payment reconciliation report
- [ ] Add payment schedule reminder
- [ ] Add bank account mapping per metode
- [ ] Add payment method details (bank name, check number, etc)
