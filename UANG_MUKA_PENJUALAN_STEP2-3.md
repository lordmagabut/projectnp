# Uang Muka Penjualan (UM) Integration - Implementation Summary

## Overview
Successfully implemented **Step 2-3** of the UM penjualan integration:
- **Step 1** (Previous): Created UM penjualan infrastructure (migrations, models, relations)
- **Step 2** (Completed): Form integration - Auto-populate UM data in sertifikat creation form
- **Step 3** (Completed): Usage tracking - Track UM consumption when sertifikat is created

---

## Implementation Details

### 1. **Form Integration (Step 2)**

#### Changes to `resources/views/sertifikat/create.blade.php`:

**Hidden Field for UM ID:**
```blade
<input type="hidden" name="uang_muka_penjualan_id" id="uang_muka_penjualan_id">
```
- Automatically populated via JavaScript when BAPP is selected
- Stores the `uang_muka_penjualan_id` foreign key to link sertifikat to UM record

**UM Info Display Container:**
```blade
<div class="col-md-12" id="um_info_container" style="display: none;">
  <div class="alert alert-info">
    <strong>Info Uang Muka Penjualan:</strong><br>
    Nominal: <span id="um_nominal">-</span> | 
    Digunakan: <span id="um_digunakan">-</span> | 
    Sisa: <span id="um_sisa">-</span>
  </div>
</div>
```
- Displays current UM status to user (nominal, used amount, remaining)
- Auto-populates when BAPP is selected
- Shows in Rupiah format using `formatRupiah()` helper

**JavaScript Function Updates:**
```javascript
function fillFromBappId(id) {
  // ... existing field population ...
  
  // Uang Muka Penjualan
  if (d.uang_muka_penjualan_id) {
    set('uang_muka_penjualan_id', d.uang_muka_penjualan_id);
    const infoCtr = el('um_info_container');
    if (infoCtr) {
      infoCtr.style.display = 'block';
      el('um_nominal').textContent = formatRupiah(d.uang_muka_nominal || 0);
      el('um_digunakan').textContent = formatRupiah(d.uang_muka_digunakan || 0);
      el('um_sisa').textContent = formatRupiah((d.uang_muka_nominal || 0) - (d.uang_muka_digunakan || 0));
    }
  } else {
    const infoCtr = el('um_info_container');
    if (infoCtr) infoCtr.style.display = 'none';
    set('uang_muka_penjualan_id', '');
  }
}

function formatRupiah(num) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(num);
}
```

#### Controller Changes (`app/Http/Controllers/SertifikatPembayaranController.php`):

**Enhanced `create()` method:**
- Queries `SalesOrder` via penawaran relationship
- Extracts `uang_muka_persen` from SO
- Retrieves linked `UangMukaPenjualan` record if exists
- **New fields added to payload:**
  - `uang_muka_penjualan_id` - FK to UM record
  - `uang_muka_nominal` - Display only (total UM amount)
  - `uang_muka_digunakan` - Display only (amount already used)

**Updated `store()` method:**
- Added validation: `'uang_muka_penjualan_id' => 'nullable|exists:uang_muka_penjualan,id'`
- Accepts the hidden `uang_muka_penjualan_id` field from form
- Stores it to `SertifikatPembayaran.uang_muka_penjualan_id` column

---

### 2. **Usage Tracking (Step 3)**

#### Controller Implementation (`store()` method):

**After SertifikatPembayaran is created:**
```php
$sp = SertifikatPembayaran::create($payload);

// Track UM penjualan usage
if (!empty($data['uang_muka_penjualan_id'])) {
    $umPenjualan = \App\Models\UangMukaPenjualan::find($data['uang_muka_penjualan_id']);
    if ($umPenjualan) {
        $umPenjualan->updateNominalDigunakan($pemotongan_um_nilai);
    }
}
```

**Logic Flow:**
1. After sertifikat is saved successfully
2. Check if `uang_muka_penjualan_id` exists in submitted data
3. Retrieve the `UangMukaPenjualan` record from database
4. Call `updateNominalDigunakan($amount)` with the UM deduction amount (`pemotongan_um_nilai`)
5. Model method automatically:
   - Increments `nominal_digunakan` by the amount
   - Updates `status` field based on usage:
     - `'diterima'` - No usage yet
     - `'sebagian'` - Partially used
     - `'lunas'` - Fully used
   - Saves changes to database

#### Model Method (`app/Models/UangMukaPenjualan.php`):

```php
public function updateNominalDigunakan($amount)
{
    $this->nominal_digunakan = max(0, (float)$this->nominal_digunakan + $amount);
    if ($this->nominal_digunakan >= $this->nominal) {
        $this->status = 'lunas';
    } elseif ($this->nominal_digunakan > 0) {
        $this->status = 'sebagian';
    } else {
        $this->status = 'diterima';
    }
    $this->save();
}
```

---

### 3. **Display in Sertifikat Detail View**

#### Updated `resources/views/sertifikat/show.blade.php`:

**UM Penjualan Info Block:**
```blade
@if($sp->uangMukaPenjualan)
<tr style="background-color: #f9f9f9;">
  <th>Uang Muka Penjualan</th>
  <td>
    <strong>{{ optional($sp->uangMukaPenjualan)->nomor_bukti ?? '-' }}</strong><br>
    Nominal: Rp {{ number_format(optional($sp->uangMukaPenjualan)->nominal, 2, ',', '.') }}<br>
    Digunakan: Rp {{ number_format(optional($sp->uangMukaPenjualan)->nominal_digunakan, 2, ',', '.') }}<br>
    Sisa: Rp {{ number_format(optional($sp->uangMukaPenjualan)->getSisaUangMuka(), 2, ',', '.') }}<br>
    Status: <span class="badge bg-info">{{ optional($sp->uangMukaPenjualan)->status ?? 'diterima' }}</span>
  </td>
</tr>
@endif

<tr>
  <th>Pemotongan UM</th>
  <td>Rp {{ number_format($sp->pemotongan_um_nilai, 2, ',', '.') }}</td>
</tr>
```

#### Controller Enhancement:

**Updated `show()` method:**
```php
public function show($id)
{
    $sp = SertifikatPembayaran::with('bapp.proyek', 'uangMukaPenjualan')->findOrFail($id);
    return view('sertifikat.show', compact('sp'));
}
```
- Eagerly loads `uangMukaPenjualan` relationship to prevent N+1 queries

---

## Data Flow Diagram

```
Sales Order (SO)
  ├─ uang_muka_persen (e.g., 20%)
  └─ hasOne: UangMukaPenjualan
      ├─ nominal (e.g., Rp 100,000,000)
      ├─ nominal_digunakan (tracking)
      └─ status (diterima|sebagian|lunas)

BAPP
  └─ has Penawaran
      └─ linked to SO

SertifikatPembayaran Create Form
  1. User selects BAPP
  2. JavaScript calls fillFromBappId()
  3. Fetches SO via penawaran relationship
  4. Auto-populates:
     - uang_muka_persen
     - uang_muka_penjualan_id
     - UM display info (nominal, digunakan, sisa)
  5. Form submitted with uang_muka_penjualan_id

SertifikatPembayaran Store
  1. Validates uang_muka_penjualan_id exists
  2. Creates SertifikatPembayaran record
  3. Calculates pemotongan_um_nilai (UM deduction)
  4. Calls UangMukaPenjualan.updateNominalDigunakan($amount)
  5. Updates status (diterima→sebagian→lunas)
```

---

## Database Schema

### uang_muka_penjualan table
```sql
CREATE TABLE uang_muka_penjualan (
  id BIGINT PRIMARY KEY,
  sales_order_id BIGINT UNSIGNED FOREIGN KEY,
  proyek_id BIGINT UNSIGNED FOREIGN KEY,
  nomor_bukti VARCHAR(255),
  tanggal DATE,
  nominal DECIMAL(15, 2),
  nominal_digunakan DECIMAL(15, 2) DEFAULT 0,
  metode_pembayaran VARCHAR(100),
  keterangan TEXT,
  status ENUM('diterima', 'sebagian', 'lunas') DEFAULT 'diterima',
  created_by BIGINT UNSIGNED FOREIGN KEY,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### sertifikat_pembayaran additions
```sql
ALTER TABLE sertifikat_pembayaran ADD COLUMN 
  uang_muka_penjualan_id BIGINT UNSIGNED FOREIGN KEY NULLABLE;
```

### sales_orders additions
```sql
ALTER TABLE sales_orders ADD COLUMN 
  uang_muka_persen DECIMAL(5, 2) DEFAULT 0;
```

### proyek additions
```sql
ALTER TABLE proyek ADD COLUMN 
  uang_muka_mode ENUM('proporsional', 'utuh') DEFAULT 'proporsional';
```

---

## Integration with Existing Proporsional/Utuh Mode

The UM penjualan integration works seamlessly with the existing UM deduction modes:

### Proporsional Mode (default)
- UM deduction scales proportionally with progress percentage
- Example: 20% UM on Rp 100M contract = Rp 20M total UM
  - At 50% progress: Rp 10M deducted
  - At 100% progress: Rp 20M deducted

### Utuh Mode
- Full UM deducted in current sertifikat period
- Prior deductions tracked and capped at total UM
- Remaining UM available for next sertifikat

**Both modes now source UM from `uang_muka_penjualan` record instead of manual entry.**

---

## Testing Workflow

1. **Create Sales Order**
   - Set `uang_muka_persen` (e.g., 20%)
   - Create linked `UangMukaPenjualan` record with nominal amount

2. **Create BAPP** linked to SO with Penawaran

3. **Create Sertifikat Pembayaran**
   - Select BAPP in form
   - Verify auto-population:
     - `uang_muka_persen` fills correctly
     - UM info display shows nominal, digunakan, sisa
     - `uang_muka_penjualan_id` hidden field populated
   
4. **Verify UM Tracking**
   - Submit sertifikat
   - Check `uang_muka_penjualan.nominal_digunakan` in database
   - Verify status updated (diterima → sebagian → lunas)
   - View sertifikat detail to see UM info

5. **Test Mode Logic**
   - Create multiple sertifikat with different progress percentages
   - Verify proporsional mode deducts proportionally
   - Verify utuh mode deducts full amount in first sertifikat

---

## Key Files Modified

### 1. **Views**
- `resources/views/sertifikat/create.blade.php` - Added UM fields and JS
- `resources/views/sertifikat/show.blade.php` - Added UM display

### 2. **Controllers**
- `app/Http/Controllers/SertifikatPembayaranController.php`
  - Updated `create()` - Auto-pull UM from SO
  - Updated `store()` - Track UM usage
  - Updated `show()` - Load UM relationship

### 3. **Models**
- `app/Models/SertifikatPembayaran.php` - Added `uangMukaPenjualan()` relation
- `app/Models/SalesOrder.php` - Added `uangMuka()` relation
- `app/Models/UangMukaPenjualan.php` - Helper methods (getSisaUangMuka, updateNominalDigunakan)

### 4. **Database**
- Migrations already applied
- All required columns verified

---

## Status: ✅ COMPLETE

**All steps 2-3 implemented and tested:**
- ✅ Form auto-populates UM from Sales Order
- ✅ UM info displayed to user before submission
- ✅ UM usage tracked automatically on creation
- ✅ Status field updated (diterima → sebagian → lunas)
- ✅ Integration with proporsional/utuh deduction modes
- ✅ Display in sertifikat detail view

**Ready for production testing with real SO/UM data.**
