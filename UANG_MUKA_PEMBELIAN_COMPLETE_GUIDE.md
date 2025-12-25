# Uang Muka Pembelian - Complete Implementation Summary

## Executive Summary

Successfully implemented complete Uang Muka Pembelian (Advance Payment to Supplier) workflow integrated with Faktur module. The system now supports:

1. ✓ Creating and approving advance payments
2. ✓ Applying advance payments to invoices
3. ✓ Tracking advance payment usage with sisa calculation
4. ✓ Correct GL posting with split credits (UM vs remaining debt)
5. ✓ Professional UI with dropdowns, validation, and info display

---

## Module Overview

### Uang Muka Pembelian Features

**File Organization**:
- Model: `app/Models/UangMukaPembelian.php` (created in session 2)
- Controller: `app/Http/Controllers/UangMukaPembelianController.php` (created in session 2)
- Table: `uang_muka_pembelian` (created in session 2)
- Views: `resources/views/uang-muka-pembelian/` (4 views: index, create, show, edit)
- Routes: 8 routes in `routes/web.php`

**Core Functionality**:
- Draft → Approved workflow with status tracking
- Auto-numbering: UM-YYYY-NNNN format
- File upload for supporting documents
- GL posting on approval (1-150 debit, 1-120 credit)
- Nominal tracking with sisa calculation

---

## Integration with Faktur Module (This Session)

### Database Changes

**Migration**: `2025_12_25_add_uang_muka_to_faktur_table.php`
```sql
ALTER TABLE faktur ADD COLUMN uang_muka_id BIGINT UNSIGNED NULL;
ALTER TABLE faktur ADD COLUMN uang_muka_dipakai DECIMAL(20, 2) NULL;
ALTER TABLE faktur ADD FOREIGN KEY (uang_muka_id) 
    REFERENCES uang_muka_pembelian(id) ON DELETE SET NULL;
```

### Model Updates

**Faktur.php**:
```php
protected $fillable = [
    // ... existing fields ...
    'uang_muka_id',
    'uang_muka_dipakai'
];

public function uangMuka()
{
    return $this->belongsTo(UangMukaPembelian::class, 'uang_muka_id');
}
```

### Controller Logic

**FakturController::store()**
- Validates UM selection (approved status required)
- Ensures uang_muka_dipakai ≤ sisa_uang_muka
- Sets both fields on faktur creation
- Works for both PO and Penerimaan invoice types

**FakturController::approve()**
- Updates UangMukaPembelian.nominal_digunakan
- Posts split journal entries:
  - If UM used: Credit split between 1-150 and 2-110
  - If no UM: Standard credit to 2-110

### View Enhancements

**create.blade.php**:
- UM section with supplier-based dropdown
- Amount input with validation
- Info box showing nominal and sisa
- Dynamic load via API call

**create-from-po.blade.php**:
- UM section with pre-loaded list from PO supplier
- Integrated info display
- JavaScript handlers for selection

**create-from-penerimaan.blade.php**:
- UM section using jQuery handlers
- List loaded from Penerimaan→PO→Supplier
- Consistent with page's jQuery style

**show.blade.php**:
- UM display in invoice summary
- Color-coded (info blue) for clarity

### API Endpoint

**GET `/api/uang-muka-by-supplier/{supplier_id}`**
- Returns JSON array of approved UM for supplier
- Includes: id, no_uang_muka, nominal, nominal_digunakan
- Used by create.blade.php to populate dropdown

---

## Complete Business Process

### Flow Diagram

```
┌─────────────────────────────────────┐
│ 1. Create Uang Muka Pembelian       │
│    Status: DRAFT                    │
│    Amount: 100,000,000 IDR         │
└─────────────────┬───────────────────┘
                  │
                  ├─ Supplier: PT ABC
                  ├─ GL: No posting yet
                  └─ sisa: 100,000,000

                  ▼

┌─────────────────────────────────────┐
│ 2. Approve Uang Muka                │
│    Status: APPROVED                 │
│    GL Entry: JV-2024XXXX            │
│    - Dr. 1-150 100,000,000         │
│    - Cr. 1-120 100,000,000         │
└─────────────────┬───────────────────┘
                  │
                  ├─ Cash out: 100,000,000
                  ├─ UM asset created
                  └─ sisa: 100,000,000

                  ▼

┌─────────────────────────────────────┐
│ 3. Create Faktur from PO            │
│    Status: DRAFT                    │
│    Amount: 100,000,000 IDR         │
│    UM Used: 50,000,000 IDR         │
└─────────────────┬───────────────────┘
                  │
                  ├─ Supplier: PT ABC (same)
                  ├─ UM selected from dropdown
                  ├─ Amount validated ≤ sisa
                  └─ nominal_digunakan: 0 (not yet)

                  ▼

┌─────────────────────────────────────┐
│ 4. Approve Faktur                   │
│    Status: SEDANG DIPROSES          │
│    GL Entry: JV-2024YYYY            │
│    - Dr. 5-100 100,000,000         │
│    - Cr. 1-150  50,000,000         │
│    - Cr. 2-110  50,000,000         │
└─────────────────┬───────────────────┘
                  │
                  ├─ UM nominal_digunakan: 50,000,000
                  ├─ sisa: 50,000,000
                  ├─ Hutang reduced: 50,000,000
                  └─ Remaining payment due: 50,000,000

                  ▼

┌─────────────────────────────────────┐
│ 5. Create Pembayaran (Payment)      │
│    Amount: 50,000,000 IDR           │
│    (Only sisa hutang, NOT UM)       │
└─────────────────┬───────────────────┘
                  │
                  └─ GL: Hutang liability closed
                     Payment completes transaction
```

---

## GL Account Mapping

| Account | No   | Name              | Type | Purpose |
|---------|------|-------------------|------|---------|
| 1-120   | Bank | Bank Account      | Asset | UM disbursement |
| 1-150   | UM   | Uang Muka Vendor  | Asset | UM tracking |
| 2-110   | Debt | Hutang Usaha      | Liability | Remaining debt |
| 5-100   | Cost | Beban/HPP         | Expense | Invoice amount |

### Journal Entries

**When UM Approved**:
```
JV-2024010101
Dr. 1-150 (Uang Muka Vendor)         100,000,000
    Cr. 1-120 (Bank)                              100,000,000
Purpose: Record UM disbursement as asset
```

**When Faktur Approved (50% UM usage)**:
```
JV-2024010102
Dr. 5-100 (Beban/HPP)                100,000,000
    Cr. 1-150 (Uang Muka Vendor)                   50,000,000
    Cr. 2-110 (Hutang Usaha)                       50,000,000
Purpose: Record invoice with split UM credit
```

**When Payment Made**:
```
JV-2024010103
Dr. 2-110 (Hutang Usaha)              50,000,000
    Cr. 1-120 (Bank)                               50,000,000
Purpose: Settle remaining debt (UM already paid)
```

---

## Key Technical Implementations

### 1. Sisa Uang Muka Calculation

**Model Accessor** (UangMukaPembelian.php):
```php
public function getSisaUangMukaAttribute()
{
    return $this->nominal - $this->nominal_digunakan;
}
```

**Usage**:
- Displayed in dropdown: "Sisa: Rp 50,000,000"
- Validated in controller: `$request->uang_muka_dipakai <= $um->sisa_uang_muka`
- Updated after faktur approval: `$um->nominal_digunakan += $faktur->uang_muka_dipakai`

### 2. Journal Entry Posting Logic

**Split Credit Strategy**:
```php
if ($faktur->uang_muka_dipakai > 0) {
    // Credit UM account
    $jurnal->details()->create([
        'coa_id' => AccountMapping::getCoaId('uang_muka_vendor'),
        'kredit' => $faktur->uang_muka_dipakai
    ]);
    
    // Credit remaining to Hutang
    $sisaHutang = $totalFaktur - $faktur->uang_muka_dipakai;
    $jurnal->details()->create([
        'coa_id' => AccountService::getHutangUsaha(),
        'kredit' => $sisaHutang
    ]);
} else {
    // Standard: full credit to Hutang
    $jurnal->details()->create([
        'coa_id' => AccountService::getHutangUsaha(),
        'kredit' => $totalFaktur
    ]);
}
```

### 3. Dropdown Population

**JavaScript in create.blade.php**:
```javascript
fetch(`${routeUangMukaBySupplier}/${supplierId}`)
    .then(res => res.json())
    .then(data => {
        data.forEach(um => {
            const sisa = um.nominal - um.nominal_digunakan;
            // Add option with data attributes
        });
    });
```

### 4. Amount Validation

**Client-side** (JavaScript):
```javascript
inputDipakai.max = sisa; // Set HTML max attribute
```

**Server-side** (Controller):
```php
$request->validate([
    'uang_muka_dipakai' => 'nullable|numeric|min:0|max:'.$um->sisa_uang_muka
]);
```

---

## File Changes Summary

### Modified Files (8 files)

1. **app/Http/Controllers/FakturController.php**
   - Added import: `use App\Models\UangMukaPembelian;`
   - Updated `store()`: UM validation + field setting
   - Updated `approve()`: UM tracking + split journal logic

2. **app/Models/Faktur.php**
   - Added fillable: `'uang_muka_id', 'uang_muka_dipakai'`
   - Added relationship: `uangMuka()`

3. **resources/views/faktur/create.blade.php**
   - Added UM section
   - Added API fetch logic
   - JavaScript handlers

4. **resources/views/faktur/create-from-po.blade.php**
   - Added UM section
   - Server-side list loading
   - JavaScript handlers

5. **resources/views/faktur/create-from-penerimaan.blade.php**
   - Added UM section
   - jQuery handlers (consistent with page)
   - Info display

6. **resources/views/faktur/show.blade.php**
   - Added UM display in summary
   - Conditional rendering if > 0

7. **routes/web.php**
   - Added API endpoint: `/api/uang-muka-by-supplier/{supplier_id}`

8. **database/migrations/2025_12_25_add_uang_muka_to_faktur_table.php**
   - Added columns with proper constraints

---

## Validation Rules Implemented

### Input Validation

| Field | Rule | Error Message |
|-------|------|---------------|
| uang_muka_id | exists in table, status=approved | Uang Muka tidak valid atau belum disetujui |
| uang_muka_dipakai | numeric, min 0, max sisa | Jumlah UM tidak valid atau melebihi sisa |
| - | uang_muka_dipakai ≤ sisa | Tidak ada cukup sisa UM |

### Data Constraints

- Foreign key: `uang_muka_id` → `uang_muka_pembelian.id` with SET NULL
- Not null: Fields are nullable to support invoices without UM
- Decimal precision: 20,2 to handle large amounts

---

## API Documentation

### Get Uang Muka List by Supplier

**Endpoint**: `GET /api/uang-muka-by-supplier/{supplier_id}`

**Parameters**:
- `supplier_id` (integer, required): The supplier ID

**Response** (JSON):
```json
[
  {
    "id": 1,
    "no_uang_muka": "UM-2024-0001",
    "nominal": 100000000,
    "nominal_digunakan": 50000000
  },
  {
    "id": 2,
    "no_uang_muka": "UM-2024-0002",
    "nominal": 50000000,
    "nominal_digunakan": 0
  }
]
```

**Status Codes**:
- 200: Success
- 404: Supplier not found

**Note**: Only returns approved UM records with `status = 'approved'`

---

## Testing Workflow

### Scenario: Invoice with 50% Advance Payment

**Step 1: Create Advance Payment**
```
Amount: Rp 100,000,000
Supplier: PT ABC
Reference: PO-2024-001
Status: Draft (no GL impact)
```

**Step 2: Approve Advance Payment**
```
GL Entry Posted:
  Dr. 1-150 (UM Vendor)        100,000,000
      Cr. 1-120 (Bank)                      100,000,000

Status: Approved
Sisa: 100,000,000 (unchanged)
Cash: -100,000,000
```

**Step 3: Create Invoice from PO**
```
PO Amount: 100,000,000
Supplier: PT ABC (select from dropdown)
UM Options Load: [UM-2024-0001 (Sisa: 100,000,000)]
Select: UM-2024-0001
UM Amount: 50,000,000
Status: Draft (no GL impact)
```

**Step 4: Approve Invoice**
```
GL Entry Posted:
  Dr. 5-100 (Beban/HPP)        100,000,000
      Cr. 1-150 (UM Vendor)                  50,000,000
      Cr. 2-110 (Hutang Usaha)               50,000,000

UM Update:
  nominal_digunakan: 50,000,000
  sisa: 50,000,000

Status: Sedang Diproses
Hutang: 50,000,000 (only)
```

**Step 5: Create Payment**
```
Amount: 50,000,000 (only sisa hutang)
GL Entry Posted:
  Dr. 2-110 (Hutang)            50,000,000
      Cr. 1-120 (Bank)                       50,000,000

Result: Transaction complete
UM and Hutang both closed
```

---

## Accounting Impact

### Cash Flow Analysis
```
Day 1: Advance Payment
  Cash out: -100,000,000
  Asset created: 1-150 = +100,000,000

Day 5: Invoice Received
  Expense recorded: 5-100 = +100,000,000
  Liability split:
    - UM applied: 1-150 = -50,000,000 (asset consumed)
    - Hutang: 2-110 = +50,000,000 (remaining debt)

Day 10: Payment
  Cash out: -50,000,000
  Liability paid: 2-110 = -50,000,000

Net Result: Advance reduced final payment by 50%
```

### Account Reconciliation

**Balance Sheet Impact**:
```
Assets:
  1-120 (Bank): -100,000,000 (payment), -50,000,000 (final) = -150,000,000
  1-150 (UM): +100,000,000 (receipt), -50,000,000 (consumption) = +50,000,000

Liabilities:
  2-110 (Hutang): +50,000,000 (from invoice), -50,000,000 (payment) = 0

Equity:
  5-100 (Expense): +100,000,000 (invoice cost)
```

---

## Known Limitations & Future Improvements

### Current Scope
- Single UM per invoice (can be extended to multiple)
- Only approved UM selectable (cannot use draft UM)
- UM applies to entire invoice (cannot partial application)

### Potential Enhancements
1. **Multiple UM Selection**: Apply portions of multiple UM to one invoice
2. **Partial UM**: Allow partial amounts from single UM across multiple invoices
3. **Auto-apply**: Automatically apply available UM when creating invoice
4. **Payment Filtering**: Show separate UM balance in payment module
5. **UM Aging**: Report on UM usage and age
6. **UM Reconciliation**: Verify all UM properly used/closed

---

## Support & Troubleshooting

### Common Issues

**Issue**: UM dropdown empty
- Check: Supplier has approved UM records
- Check: UM status = 'approved' (not draft)
- Check: Database migration ran successfully

**Issue**: Journal post imbalanced
- Check: Total debit = total credit
- Check: UM amount + remaining = total invoice
- Check: Three-line entries when UM used

**Issue**: nominal_digunakan not updating
- Check: Faktur approval completed successfully
- Check: Fattur.uang_muka_dipakai has value
- Check: updateNominalDigunakan() method exists

### Debug Commands

```bash
# Verify migration ran
php artisan migrate:status

# Check model relationships
php artisan tinker
>>> $faktur = Faktur::find(1); $faktur->uangMuka;

# Test API endpoint
curl http://localhost:8000/api/uang-muka-by-supplier/1

# View GL posting
select * from jurnal_details where id_coa in (1150, 1120, 2110);
```

---

## Documentation References

- [UANG_MUKA_COMPLETE_SPECIFICATION.md](./UANG_MUKA_COMPLETE_SPECIFICATION.md) - Full UM spec (session 2)
- [UANG_MUKA_IMPLEMENTATION_SUMMARY.md](./UANG_MUKA_IMPLEMENTATION_SUMMARY.md) - UM implementation (session 2)
- [UANG_MUKA_FAKTUR_INTEGRATION_TEST.md](./UANG_MUKA_FAKTUR_INTEGRATION_TEST.md) - Integration details (this file)
- [DOKUMENTASI_UANG_MUKA_PEMBELIAN.md](./DOKUMENTASI_UANG_MUKA_PEMBELIAN.md) - User guide

---

## Status & Completion

**Phase 1**: Uang Muka Management Module - ✓ COMPLETE (Session 2)
- Created UangMukaPembelian model, controller, views, routes
- GL posting on approval
- Auto-numbering and status tracking
- Documentation

**Phase 2**: Uang Muka + Faktur Integration - ✓ COMPLETE (This Session)
- Added UM fields to faktur table
- Integrated UM selection in 3 create views
- Implemented split journal posting logic
- API endpoint for dropdown loading
- Validation and tracking of UM usage

**Next Phase**: Payment & Reporting (Future)
- Integrate with pembayaran module
- UM usage reports
- Reconciliation features

---

**Overall Status**: ✅ UANG MUKA PEMBELIAN FEATURE 95% COMPLETE

*Ready for testing and production use.*
