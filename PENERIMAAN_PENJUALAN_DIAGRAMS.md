# Penerimaan Penjualan - Workflow Diagrams

## 1. Complete Sales-to-Payment Workflow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          SALES TO PAYMENT WORKFLOW                          │
└─────────────────────────────────────────────────────────────────────────────┘

1. SALES ORDER
   ├─ Create SO
   ├─ Define items, qty, price
   └─ Customer reference

        ↓

2. UANG MUKA PENJUALAN (Optional)
   ├─ Define UM rule: utuh (100%) or proporsional (cumulative %)
   ├─ Customer pays UM
   └─ UM tracked for deduction

        ↓

3. SERTIFIKAT PEMBAYARAN
   ├─ Monthly/periodic billing
   ├─ Apply UM deduction per mode
   ├─ Calculate amounts: subtotal, ppn, total
   ├─ Status: draft → approved
   └─ [Approve button creates Faktur]

        ↓

4. FAKTUR PENJUALAN (Auto-created)
   ├─ Auto-generated on Sertifikat approval
   ├─ Nomor: FP-YYMMDD-XXX
   ├─ Copy amounts from Sertifikat
   ├─ Status pembayaran: belum_dibayar
   └─ Ready for payment tracking

        ↓

5. PENERIMAAN PENJUALAN ← [YOU ARE HERE]
   ├─ Record customer payments
   ├─ Support multiple/partial payments
   ├─ Status: draft → approved
   ├─ Auto-update Faktur status pembayaran
   └─ Nomor: PN-YYMMDD-XXX

```

## 2. Penerimaan Penjualan State Machine

```
                    ┌─────────────────────────────┐
                    │     Penerimaan Created      │
                    │  (status: draft)            │
                    └────────────┬────────────────┘
                                 │
                    ┌────────────┴───────────────┐
                    │                            │
              [User approves]            [User deletes]
                    │                            │
                    ↓                            ↓
            ┌──────────────────┐    ┌──────────────────┐
            │  Approved        │    │   DELETED        │
            │ (status: approved)    │  (removed)       │
            │ disetujui_oleh_id│    │                  │
            │ tanggal_disetujui    └──────────────────┘
            └──────────────────┘
                    │
              [Update Faktur]
              status_pembayaran:
              - belum_dibayar
              - sebagian
              - lunas
```

## 3. Multiple Payment Tracking (same Faktur)

```
FAKTUR PENJUALAN: FP-260101-001
Total: Rp 100.000.000

┌──────────────────────────────────────────────────────────────────────┐
│                        Payment History                              │
├─────────────────┬───────────┬────────┬──────────────────┬───────────┤
│ No. Bukti       │ Tanggal   │ Nominal│ Status           │ User OK   │
├─────────────────┼───────────┼────────┼──────────────────┼───────────┤
│ PN-260101-001   │ 2026-01-05│ 50M    │ approved (✓)     │ User A    │
│ PN-260101-002   │ 2026-01-12│ 30M    │ approved (✓)     │ User A    │
│ PN-260101-003   │ 2026-01-20│ 20M    │ draft (⧖)        │ Pending   │
├─────────────────┴───────────┴────────┴──────────────────┴───────────┤
│ Total Received (draft + approved): 100M                              │
│ Status Pembayaran: LUNAS (Rp 100M = Total Faktur)                   │
└──────────────────────────────────────────────────────────────────────┘

Nomor Bukti Auto-Generation
─────────────────────────────

PN-260101-001  ← First payment on Jan 1, 2026
PN-260101-002  ← Second payment on Jan 1, 2026
PN-260102-001  ← First payment on Jan 2, 2026
PN-260102-002  ← Second payment on Jan 2, 2026
PN-260103-001  ← First payment on Jan 3, 2026
```

## 4. Status Pembayaran Update Logic

```
                    After Each Penerimaan Create/Delete:
                    
                    Calculate: totalDiterima = 
                       SUM(penerimaan.nominal) 
                       WHERE status IN ['draft', 'approved']
                       AND faktur_penjualan_id = $fakturId

                                    ↓

                    ┌─────────────────────────────────────┐
                    │  Compare totalDiterima vs Total     │
                    └────────────┬────────────────────────┘
                                 │
                ┌────────────────┼────────────────┐
                ↓                ↓                ↓
        totalDiterima = 0  0 < X < Total    totalDiterima ≥ Total
                ↓                ↓                ↓
        "belum_dibayar"    "sebagian"          "lunas"
                │                │                │
                └────────────────┴────────────────┘
                                 ↓
                        Update Faktur
                        status_pembayaran
```

## 5. User Roles & Permissions Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                         User Workflow                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│ USER A (Pembuat/Staff)              USER B (Penyetuju/Manager)   │
│   │                                        │                      │
│   ├─ Access: /penerimaan-penjualan    ├─ Access: same             │
│   ├─ Can: Create (POST /store)        ├─ Can: View all (GET)      │
│   ├─ Can: View own (GET /show)        ├─ Can: Approve (POST)      │
│   ├─ Can: Delete own draft            ├─ Can: Approve anyone's    │
│   └─ dibuat_oleh_id = USER A          └─ disetujui_oleh_id = USER B
│                                                                    │
└─────────────────────────────────────────────────────────────────────┘
```

## 6. Data Flow - Create Penerimaan

```
User clicks "Buat Penerimaan Baru"
        ↓
    ┌───────────────────────────────────────────────┐
    │  PenerimaanPenjualanController::create()      │
    │  - Get FakturPenjualan (status_pembayaran    │
    │    != 'lunas')                                │
    │  - Pass to view                               │
    └───────────────────────┬───────────────────────┘
                            ↓
    ┌───────────────────────────────────────────────┐
    │   resources/views/.../create.blade.php        │
    │   - Form fields:                              │
    │     * faktur_penjualan_id (dropdown)          │
    │     * tanggal (date)                          │
    │     * nominal (currency)                      │
    │     * metode_pembayaran (dropdown)            │
    │     * keterangan (textarea)                   │
    └───────────────────────┬───────────────────────┘
                            ↓
User fills form and clicks "Simpan"
        ↓
    ┌───────────────────────────────────────────────┐
    │  PenerimaanPenjualanController::store()       │
    │  1. Validate all fields                       │
    │  2. Generate no_bukti                         │
    │  3. Set dibuat_oleh_id = auth()->id()         │
    │  4. Set status = 'draft'                      │
    │  5. Create PenerimaanPenjualan                │
    │  6. updateFakturPembayaranStatus()            │
    │     - Recalculate status pembayaran           │
    │  7. Redirect to show                          │
    └───────────────────────┬───────────────────────┘
                            ↓
    ┌───────────────────────────────────────────────┐
    │   Show detail page dengan status "draft"      │
    │   Tombol "Setujui" dan "Hapus" tersedia       │
    └───────────────────────────────────────────────┘
```

## 7. Data Flow - Approve Penerimaan

```
User clicks "Setujui" button
        ↓
POST /penerimaan-penjualan/{id}/approve
        ↓
    ┌──────────────────────────────────────┐
    │  PenerimaanPenjualanController::      │
    │  approve()                            │
    │  1. Check status == 'draft'           │
    │  2. Update:                           │
    │     - status = 'approved'             │
    │     - disetujui_oleh_id = auth()      │
    │     - tanggal_disetujui = now()       │
    │  3. Save                              │
    │  4. Redirect with success message     │
    └──────────────────────────────────────┘
                    ↓
        ┌──────────────────────────────────┐
        │  Penerimaan status: approved ✓   │
        │  Faktur pembayaran status auto   │
        │  updated (belum/sebagian/lunas)  │
        └──────────────────────────────────┘
```

## 8. Database Relationships Diagram

```
┌────────────────────────┐
│        users           │
│  ┌──────────────────┐  │
│  │ id               │  │
│  │ name             │  │
│  │ email            │  │
│  └──────────────────┘  │
└──────┬─────────────────┘
       │
       │ dibuat_oleh_id
       │ disetujui_oleh_id
       ↓
┌────────────────────────────────────┐         ┌──────────────────────────┐
│  penerimaan_penjualan              │         │  faktur_penjualan        │
│ ┌──────────────────────────────┐   │         │ ┌──────────────────────┐ │
│ │ id ◄──────────────┐          │   │         │ │ id ◄──────────────┐  │ │
│ │ no_bukti          │          │   │         │ │ no_faktur        │  │ │
│ │ tanggal           │          │   │         │ │ tanggal          │  │ │
│ │ faktur_penjualan_id  ◄────────────┼─────────┼─┤ sertifikat_pembayaran_id │
│ │ nominal           │          │   │         │ │ subtotal         │  │ │
│ │ metode_pembayaran │          │   │         │ │ total_ppn        │  │ │
│ │ status            │          │   │         │ │ total            │  │ │
│ │ dibuat_oleh_id    │          │   │         │ │ status_pembayaran│  │ │
│ │ disetujui_oleh_id │          │   │         │ │ ┌────────────────┤  │ │
│ │ tanggal_disetujui │          │   │         │ └─┤ belum_dibayar  │  │ │
│ │ created_at        │          │   │         │   │ sebagian       │  │ │
│ │ updated_at        │          │   │         │   │ lunas          │  │ │
│ └──────────────────────────────┘   │         │   └────────────────┘  │ │
└────────────────────────────────────┘         └──────────────────────────┘
         hasMany ↑                                      │
         (relasi: penerimaanPenjualan)        belongsTo │
                                                   (relasi: fakturPenjualan)
```

## 9. Monthly Invoice Example

```
PROYEK: Pembangunan Gedung A
CUSTOMER: PT Maju Jaya

Month: January 2026
────────────────────────────────────────────────────────────────

SERTIFIKAT PEMBAYARAN (Jan 2026)
├─ Progress: 30%
├─ Subtotal: Rp 100.000.000
├─ PPN (11%): Rp 11.000.000
├─ Total: Rp 111.000.000
├─ Less: UM Penjualan 10%: -Rp 10.000.000
├─ **Amount Due: Rp 101.000.000**
└─ Status: approved → triggers Faktur creation

FAKTUR PENJUALAN (FP-260101-001)
├─ Amount: Rp 101.000.000
├─ Status Pembayaran: belum_dibayar
└─ Ready for payment tracking

PENERIMAAN PENJUALAN (Payment tracking)
├─ PN-260115-001: Rp 50.000.000 (Transfer) - approved
├─ PN-260120-001: Rp 51.000.000 (Transfer) - approved
└─ **Total Received: Rp 101.000.000**
   **Status: LUNAS** ✓

────────────────────────────────────────────────────────────────
Month: February 2026
Similar workflow...
```

## 10. Error & Validation Handling

```
User Action: Create Penerimaan
        ↓
┌─────────────────────────────────────────┐
│  Form Validation Checks:                │
│  ✓ tanggal required & valid date        │
│  ✓ faktur_penjualan_id exists in DB     │
│  ✓ nominal > 0.01                       │
│  ✓ metode_pembayaran not empty          │
│  ✓ keterangan optional but valid        │
└─────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────┐
│  If any validation fails:               │
│  ↓ Redirect back with errors            │
│  ↓ Show error message in red            │
│  ↓ Pre-fill form with old data          │
│  ↓ User can correct & resubmit          │
└─────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────┐
│  If validation passes:                  │
│  ↓ Create penerimaan                    │
│  ↓ Update faktur status                 │
│  ↓ Redirect with success message        │
│  ↓ Show detail page                     │
└─────────────────────────────────────────┘
```
