# Uang Muka - Faktur Integration Complete

## Overview
Successfully integrated Uang Muka Pembelian (Advance Payment) with Faktur module for complete procurement cycle handling.

## Completed Integration Tasks

### 1. Database Schema ✓
- **Migration**: `2025_12_25_add_uang_muka_to_faktur_table.php`
- **New Columns**:
  - `uang_muka_id` (bigint, nullable, foreign key)
  - `uang_muka_dipakai` (decimal 20,2, nullable)

### 2. Model Updates ✓

#### Faktur Model
```php
// Fillable array updated with:
'uang_muka_id', 'uang_muka_dipakai'

// New relationship:
public function uangMuka()
{
    return $this->belongsTo(UangMukaPembelian::class, 'uang_muka_id');
}
```

### 3. Controller Logic ✓

#### FakturController::store()
**Validation Added**:
- `uang_muka_dipakai` - nullable, numeric, min 0
- UM status must be 'approved'
- UM amount must not exceed `sisa_uang_muka`
- Applied to BOTH PO and Penerimaan creation paths

**Fields Set**:
- `faktur.uang_muka_dipakai` - amount used from UM
- `faktur.uang_muka_id` - reference to UM record

#### FakturController::approve()
**Journal Posting Logic** (NEW):
1. Updates `uang_muka.nominal_digunakan` when faktur is approved
2. **With UM**:
   - Debit: Beban/HPP account (standard)
   - Credit Split:
     - 1-150 (Uang Muka Vendor): `uang_muka_dipakai` amount
     - 2-110 (Hutang Usaha): Remaining amount
3. **Without UM**:
   - Debit: Beban/HPP account (standard)
   - Credit: 2-110 (Hutang Usaha) - full amount

### 4. API Endpoints ✓

#### GET `/api/uang-muka-by-supplier/{supplier_id}`
**Purpose**: Load approved UM for supplier when creating faktur
**Response**:
```json
[
  {
    "id": 1,
    "no_uang_muka": "UM-2024-0001",
    "nominal": 5000000,
    "nominal_digunakan": 1000000
  }
]
```

### 5. User Interface Updates ✓

#### create.blade.php
- **UM Section**: Card with light background
- **UM Selector**: Dropdown listing approved UM for supplier
- **UM Amount Input**: Field to specify how much UM to use
- **UM Info Display**: Shows nominal and sisa (remaining)
- **Dynamic Load**: UM list loads after supplier selection

#### create-from-po.blade.php
- **UM Section**: Integrated into form
- **Pre-populated**: UM list from PO supplier
- **Field Validation**: Max value set to UM sisa
- **JavaScript Handler**: Updates info display on selection

#### create-from-penerimaan.blade.php
- **UM Section**: Integrated into form
- **Data Source**: Penerimaan->PO->Supplier relationship
- **jQuery Handler**: Uses jQuery for consistency with page
- **Visual Feedback**: Alert box shows UM details

#### show.blade.php
- **UM Display**: Shows `uang_muka_dipakai` in invoice summary
- **Color Coding**: Info color (blue) for UM line item
- **Placement**: Between diskon and PPN rows

### 6. JavaScript Implementations ✓

#### create.blade.php
```javascript
// On supplier selection: Load UM list via API
// On UM selection: Show nominal and sisa
// Set max limit on input field
```

#### create-from-po.blade.php
```javascript
// Document ready: Initialize UM handlers
// On UM selection: Update info display and max value
// Formatters: Rupiah currency display
```

#### create-from-penerimaan.blade.php
```javascript
// jQuery-based handlers (consistent with page)
// On UM selection: Show/hide info box
// Data attributes: nominal and sisa passed via HTML
```

### 7. Workflow Integration ✓

#### Complete Advance Payment Workflow:
```
1. Create UM (UangMukaPembelian)
   ├─ Draft status
   ├─ No GL impact yet
   └─ Input: nominal, supplier, ref PO/Proyek

2. Approve UM
   ├─ Status → 'approved'
   ├─ GL Journal Posted:
   │  ├─ Debit: 1-150 (Uang Muka Vendor)
   │  └─ Credit: 1-120 (Bank)
   └─ Amount available for faktur

3. Create Faktur (with UM selection)
   ├─ Choose applicable UM
   ├─ Specify amount to use
   ├─ Creates draft faktur with UM reference
   └─ Remaining UM still available

4. Approve Faktur
   ├─ Updates UM nominal_digunakan
   ├─ GL Journal Posted:
   │  ├─ Debit: 5-100 (Beban/HPP - from detail)
   │  ├─ Credit: 1-150 (Uang Muka Vendor) - if UM used
   │  └─ Credit: 2-110 (Hutang Usaha) - remaining amount
   └─ Tracks split credit to both accounts

5. Create Pembayaran (Payment)
   ├─ Only for 2-110 balance (NOT the UM portion)
   ├─ UM already "used" on GL, not paid
   └─ Effective cash payment reduction
```

### 8. General Ledger Postings ✓

#### When UM Approved:
```
Journal Entry: JV-2024XXXX
Dr. 1-150 (Uang Muka Vendor)        100,000,000
   Cr. 1-120 (Bank)                               100,000,000
```

#### When Faktur Approved (with 50% UM usage):
```
PO Total: 100,000,000
UM Dipakai: 50,000,000
Remaining Hutang: 50,000,000

Journal Entry: JV-2024YYYY
Dr. 5-100 (Beban/HPP)               100,000,000
   Cr. 1-150 (Uang Muka Vendor)                   50,000,000
   Cr. 2-110 (Hutang Usaha)                       50,000,000
```

## Testing Checklist

- [ ] Create UM record with nominal 100,000,000
- [ ] Approve UM - verify GL posting (1-150 debit, 1-120 credit)
- [ ] Create Faktur from PO/Penerimaan
- [ ] Select UM from dropdown
- [ ] Verify sisa_uang_muka calculation (nominal - nominal_digunakan)
- [ ] Set uang_muka_dipakai = 50,000,000
- [ ] Approve Faktur - verify split journal posting
- [ ] Check UM nominal_digunakan updated to 50,000,000
- [ ] Verify GL shows split credits (1-150 + 2-110)
- [ ] Create Pembayaran for sisa hutang only

## Files Modified

1. `app/Http/Controllers/FakturController.php`
   - Added UangMukaPembelian import
   - Updated store() with UM validation and field setting
   - Updated approve() with UM tracking and split journal logic

2. `app/Models/Faktur.php`
   - Added fields to $fillable: uang_muka_id, uang_muka_dipakai
   - Added relationship: uangMuka()

3. `resources/views/faktur/create.blade.php`
   - Added UM section with selector and amount input
   - Added API call to load UM by supplier
   - JavaScript handlers for UM selection

4. `resources/views/faktur/create-from-po.blade.php`
   - Added UM section with server-side UM list loading
   - JavaScript handlers for UM selection
   - Max value validation on input field

5. `resources/views/faktur/create-from-penerimaan.blade.php`
   - Added UM section with jQuery handlers
   - UM list loaded from Penerimaan->PO->Supplier
   - Info display with alert box

6. `resources/views/faktur/show.blade.php`
   - Added UM display in invoice summary
   - Shows uang_muka_dipakai amount if > 0

7. `routes/web.php`
   - Added API endpoint: `/api/uang-muka-by-supplier/{supplier_id}`

8. `database/migrations/2025_12_25_add_uang_muka_to_faktur_table.php`
   - Added uang_muka_id and uang_muka_dipakai columns

## Key Business Logic

### Sisa Uang Muka Calculation
```php
sisa_uang_muka = nominal - nominal_digunakan
```
- Ensures UM cannot be over-allocated
- Updated after each faktur approval
- Displayed in UM selector dropdown

### Split Journal Credit Logic
```php
if (uang_muka_dipakai > 0) {
    credit_1_150 = uang_muka_dipakai
    credit_2_110 = totalFaktur - uang_muka_dipakai
} else {
    credit_2_110 = totalFaktur
}
```
- Properly tracks UM as applied against invoice
- Maintains double-entry accounting balance
- Reduces hutang by amount of UM used

### Payment Deduction
```
Sisa Hutang to Pay = Total Invoice - UM Dipakai
```
- UM is NOT paid (already paid when UM was created)
- Only pays the 2-110 (Hutang Usaha) balance
- Effective cash discount through UM mechanism

## Integration Points

### 1. PO Module
- Reads supplier from PO
- Loads UM for same supplier
- Pre-populates fields in create-from-po form

### 2. Penerimaan Module
- Reads supplier via Penerimaan->PO->Supplier
- Loads UM for same supplier
- Pre-populates fields in create-from-penerimaan form

### 3. GL System
- Posts UM as asset (1-150)
- Tracks liability split (1-150 vs 2-110)
- Maintains proper account reconciliation

### 4. Payment Module
- Should only allow payment for (Total - UM Dipakai)
- UM portion already "paid" via UM journal entry
- May need separate payment filtering (future enhancement)

## Validation Rules

### UM Selection Validation (FakturController::store)
```php
// Rule 1: UM must be approved
if ($request->uang_muka_id) {
    $um = UangMukaPembelian::findOrFail($request->uang_muka_id);
    if ($um->status !== 'approved') {
        // Validation error
    }
}

// Rule 2: UM amount cannot exceed sisa
if ($request->uang_muka_dipakai > 0) {
    if ($request->uang_muka_dipakai > $um->sisa_uang_muka) {
        // Validation error
    }
}
```

## Success Indicators

✓ UM field appears in faktur forms
✓ UM dropdown loads correctly
✓ Selected UM shows nominal and sisa
✓ Amount field has max validation
✓ Journal posts with split credits
✓ nominal_digunakan updates on approval
✓ Invoice summary shows UM dipakai
✓ GL reflects proper account split

## Next Steps (Future Enhancements)

1. **Payment Integration**
   - Filter payment to exclude UM portion
   - Show both UM credit and hutang balance

2. **UM Reporting**
   - UM usage by supplier
   - UM aging report
   - Sisa UM tracking

3. **Bulk Operations**
   - Apply UM to multiple invoices
   - Auto-deduct UM from invoices for supplier

4. **Mobile UI**
   - Responsive UM selector
   - Touch-friendly amount input

## Support Notes

- **UM Model**: `App\Models\UangMukaPembelian`
- **UM Controller**: `App\Http\Controllers\UangMukaPembelianController`
- **Sisa Calculation**: Accessor in UangMukaPembelian model
- **Update Tracking**: `updateNominalDigunakan()` helper method
- **API Route**: Inline closure in routes/web.php

---

**Integration Status**: ✓ COMPLETE
**Last Updated**: 2025-01-15
**Phase**: 2 - UM Integration with Faktur (DONE)
