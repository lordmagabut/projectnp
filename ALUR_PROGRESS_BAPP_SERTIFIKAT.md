# 📊 Alur Data Progress → BAPP → Sertifikat Pembayaran

## 🔄 Diagram Alur Lengkap

```
┌─────────────────────────────────────────────────────────────────┐
│                     1. TAHAP PROGRESS                            │
└─────────────────────────────────────────────────────────────────┘

Tab Progress (Proyek)
  └─ Input data progress per minggu
      ├─ Minggu ke-N
      ├─ Progress % (kumulatif)
      ├─ Progress % delta (minggu ini)
      └─ Status: Draft → Final (setelah disetujui)

Model: RabProgress + RabProgressDetail
Database:
  ├─ rab_progress (id, proyek_id, penawaran_id, minggu_ke, status)
  └─ rab_progress_details (id, progress_id, rab_detail_id, prev_pct, delta_pct, now_pct)


┌─────────────────────────────────────────────────────────────────┐
│             2. TAHAP BAPP (Berita Acara Pembayaran)              │
└─────────────────────────────────────────────────────────────────┘

Trigger: User klik "Terbitkan BAPP" di progress detail
  ├─ Syarat: Progress status harus "final"
  └─ Tidak boleh ada BAPP yang sudah dibuat untuk minggu_ke yang sama

BappController::create()
  ├─ Terima: penawaran_id, minggu_ke
  ├─ Query RabProgress matching minggu_ke
  └─ Dataset dari RabProgressDetail:
      ├─ Items dari RAB detail
      ├─ Previous progress % (kumulatif sampai minggu lalu)
      ├─ Delta progress % (hanya minggu ini)
      └─ Current progress % (cumulative)

BappController::store()
  ├─ Simpan Bapp record
  ├─ Buat BappDetail per item (snapshot dari progress)
  │  └─ Fields: bobot_item, prev_pct, delta_pct, now_pct
  ├─ Generate PDF
  ├─ Set status = 'draft'
  └─ User submit untuk approval

Model: Bapp + BappDetail
Database:
  ├─ bapps (id, proyek_id, penawaran_id, progress_id, minggu_ke, 
  │         tanggal_bapp, nomor_bapp, status, total_prev_pct, 
  │         total_delta_pct, total_now_pct, file_pdf_path)
  └─ bapp_details (id, bapp_id, rab_detail_id, kode, uraian, 
                   bobot_item, prev_pct, delta_pct, now_pct, ...)


┌─────────────────────────────────────────────────────────────────┐
│         3. TAHAP APPROVAL BAPP (Persetujuan Manager)             │
└─────────────────────────────────────────────────────────────────┘

Proyek > Tab BAPP > Tombol Approve
  ├─ Update Bapp.status = 'submitted'
  └─ Setelah disetujui: status = 'approved'

Syarat untuk membuat Sertifikat:
  └─ BAPP harus status 'approved'


┌─────────────────────────────────────────────────────────────────┐
│    4. TAHAP SERTIFIKAT PEMBAYARAN (Invoicing)                   │
└─────────────────────────────────────────────────────────────────┘

Trigger: User klik "Buat Sertifikat Pembayaran" dari BAPP yang approved

SertifikatPembayaranController::create()
  ├─ Query BAPP dengan status = 'approved'
  ├─ Untuk setiap BAPP, kumpulkan data:
  │  ├─ BAPP info: nomor_bapp, minggu_ke, tanggal_bapp
  │  ├─ Progress % dari BAPP: total_now_pct (kumulatif)
  │  ├─ WO values dari Penawaran:
  │  │  ├─ nilai_wo_material (sum dari rab_penawaran_items material)
  │  │  └─ nilai_wo_jasa (sum dari rab_penawaran_items jasa)
  │  ├─ Percentages:
  │  │  ├─ uang_muka_persen (dari Sales Order)
  │  │  ├─ retensi_persen (default 5%)
  │  │  └─ ppn_persen (default 11%)
  │  └─ Termin ke (dari BAPP.minggu_ke)
  │
  └─ Build JavaScript payload untuk autofill form

Form Sertifikat:
  ├─ [Auto-filled dari BAPP]
  │  ├─ Pilih BAPP dropdown
  │  ├─ Tanggal
  │  ├─ Progress % (kumulatif dari BAPP)
  │  ├─ WO Material, WO Jasa
  │  ├─ Uang Muka %, Retensi %, PPN %
  │  ├─ Termin Ke
  │  └─ Uang Muka info (nominal, used, remaining)
  │
  └─ [Manual input]
      ├─ Signature fields (Pemberi Tugas, Penerima Tugas)
      └─ Adjustment jika diperlukan

SertifikatPembayaranController::store()
  ├─ Validate all fields
  ├─ Calculate derived values:
  │  ├─ nilai_progress_rp = (WO Total × progress %)
  │  ├─ dpp_material = (WO Material × progress %)
  │  ├─ dpp_jasa = (WO Jasa × progress %)
  │  ├─ total_dibayar = DPP - retensi
  │  ├─ ppn_nilai = DPP × ppn_persen
  │  ├─ total_tagihan = DPP + PPN
  │  └─ pemotongan_um_nilai = total_tagihan × uang_muka_persen
  │
  ├─ Create SertifikatPembayaran record
  ├─ Handle UangMukaPenjualan update (tracking)
  ├─ Handle PPh calculation dari tax profile proyek
  └─ Generate nomor sertifikat

Model: SertifikatPembayaran
Database: sertifikat_pembayaran (id, bapp_id, nomor, tanggal, termin_ke,
         nilai_wo_material, nilai_wo_jasa, nilai_wo_total,
         persen_progress, persen_progress_prev, persen_progress_delta,
         dpp_material, dpp_jasa, nilai_progress_rp, total_dibayar,
         uang_muka_persen, uang_muka_nilai, pemotongan_um_nilai, sisa_uang_muka,
         retensi_persen, retensi_nilai,
         ppn_persen, ppn_nilai, total_tagihan,
         pph_persen, pph_nilai,
         pemberi_tugas_nama/jabatan/perusahaan,
         penerima_tugas_nama/jabatan/perusahaan,
         status, ...)


┌─────────────────────────────────────────────────────────────────┐
│      5. TAHAP FAKTUR PENJUALAN (Auto-created dari Sertifikat)    │
└─────────────────────────────────────────────────────────────────┘

Trigger: User approve Sertifikat Pembayaran

SertifikatPembayaranController::approve()
  ├─ Set Sertifikat status = 'approved'
  │
  ├─ Auto-create FakturPenjualan:
  │  ├─ Copy basic fields:
  │  │  ├─ tanggal
  │  │  ├─ id_proyek
  │  │  ├─ id_perusahaan
  │  │  ├─ subtotal = total_dibayar (DPP periode ini)
  │  │  ├─ total_ppn = ppn_nilai
  │  │  ├─ total = total_tagihan
  │  │  ├─ uang_muka_dipakai = pemotongan_um_nilai
  │  │  └─ status = 'draft'
  │  │
  │  └─ Copy financial deductions:
  │     ├─ retensi_persen (dari sertifikat)
  │     ├─ retensi_nilai (dari sertifikat)
  │     ├─ ppn_persen (dari sertifikat)
  │     ├─ ppn_nilai (dari sertifikat)
  │     ├─ pph_persen (calculate dari tax profile)
  │     └─ pph_nilai (calculate dari tax profile)
  │
  └─ FakturPenjualan siap untuk pembayaran

Model: FakturPenjualan
Database: faktur_penjualan (id, no_faktur, tanggal, sertifikat_pembayaran_id,
         id_proyek, subtotal, total_diskon, total_ppn, total,
         uang_muka_dipakai,
         retensi_persen, retensi_nilai,
         ppn_persen, ppn_nilai,
         pph_persen, pph_nilai,
         status, status_pembayaran, ...)


┌─────────────────────────────────────────────────────────────────┐
│    6. TAHAP PENERIMAAN PENJUALAN (Payment Recording)             │
└─────────────────────────────────────────────────────────────────┘

Trigger: User klik "Terima Pembayaran" dari Faktur show

PenerimaanPenjualanController::create()
  ├─ Pre-select Faktur dari query param (faktur_penjualan_id)
  └─ Display form for payment input

PenerimaanPenjualanController::store()
  ├─ Record pembayaran:
  │  ├─ tanggal
  │  ├─ nomor_bukti (auto-generated)
  │  ├─ nominal (payment amount)
  │  ├─ pph_dipotong (tax withheld)
  │  ├─ keterangan_pph (tax notes)
  │  └─ keterangan (general notes)
  │
  ├─ Set status = 'draft'
  └─ Update faktur status_pembayaran based on payments

Model: PenerimaanPenjualan
Database: penerimaan_penjualan (id, faktur_penjualan_id, tanggal,
         nomor_bukti, nominal, pph_dipotong, keterangan_pph,
         status, ...)
```

---

## 📋 Key Data Flow Points

| Stage | Input Source | Output | Purpose |
|-------|--------------|--------|---------|
| Progress | User input + RAB | RabProgress + Details | Record actual weekly progress |
| BAPP | RabProgress (status=final) | Bapp + BappDetails | Formalize progress report |
| BAPP Approval | Manager action | Bapp.status='approved' | Approve work before invoicing |
| Sertifikat | BAPP (status=approved) | SertifikatPembayaran | Create invoice/billing document |
| Faktur | Sertifikat (status=approved) | FakturPenjualan | Generate formal invoice |
| Penerimaan | FakturPenjualan | PenerimaanPenjualan | Record customer payments |

---

## 🔗 Key Models & Relations

```
RabProgress (Weekly progress record)
  ├─ has many RabProgressDetails
  ├─ belongs to Proyek
  └─ belongs to RabPenawaranHeader (Penawaran)

↓ (When status = final)

Bapp (Progress report document)
  ├─ has many BappDetails
  ├─ has one RabProgress
  ├─ belongs to Proyek
  └─ belongs to RabPenawaranHeader

↓ (When status = approved)

SertifikatPembayaran (Invoice document)
  ├─ belongs to Bapp
  ├─ has one RabProgress (through Bapp)
  ├─ has one Proyek (through Bapp)
  ├─ has one UangMukaPenjualan
  └─ has many PenerimaanPenjualan

↓ (When status = approved)

FakturPenjualan (Formal invoice)
  ├─ belongs to SertifikatPembayaran
  ├─ belongs to Proyek
  └─ has many PenerimaanPenjualan

↓ (Multiple payments)

PenerimaanPenjualan (Payment recording)
  ├─ belongs to FakturPenjualan
  └─ belongs to SertifikatPembayaran (optional, through FakturPenjualan)
```

---

## 🧮 Calculation Chain

### 1. Progress % → WO Values
```
Progress % (dari RabProgress.total_now_pct)
  ↓
nilai_progress_rp = WO_TOTAL × Progress %
dpp_material = WO_MATERIAL × Progress %
dpp_jasa = WO_JASA × Progress %
```

### 2. DPP → Deductions & Taxes
```
DPP = dpp_material + dpp_jasa
  ↓
total_dibayar = DPP - retensi_nilai
ppn_nilai = DPP × ppn_persen
pph_nilai = DPP × pph_persen (dari tax profile)
  ↓
total_tagihan = DPP + ppn_nilai
```

### 3. Uang Muka Calculation
```
DPP × uang_muka_persen = uang_muka_nominal
pemotongan_um_nilai = total_tagihan × uang_muka_persen
sisa_uang_muka = uang_muka_nominal - pemotongan_um_nilai
```

---

## ✅ Nilai Progress Tersimpan Di:

1. **RabProgress.minggu_ke** - Minggu reporting
2. **RabProgress.total_now_pct** - Progress % kumulatif (saved)
3. **RabProgressDetail.now_pct** - Progress % per item
4. **Bapp.total_now_pct** - Snapshot di BAPP (not editable after approved)
5. **SertifikatPembayaran.persen_progress** - Snapshot di Sertifikat
6. **SertifikatPembayaran.persen_progress_prev** - Previous cumulative %
7. **SertifikatPembayaran.persen_progress_delta** - This period delta %
8. **FakturPenjualan** - Values calculated from sertifikat

Setiap tahap **snapshot/menyimpan nilai** agar tidak berubah jika data upstream dimodifikasi.

