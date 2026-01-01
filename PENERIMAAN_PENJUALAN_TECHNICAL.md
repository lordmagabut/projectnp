# Penerimaan Penjualan - Technical Implementation Details

## System Architecture

### Layer-Based Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                      │
│  Views: index, create, show (Blade templates)                │
│  - Responsive Bootstrap UI                                    │
│  - Form validation feedback                                   │
│  - Status indicators & badges                                 │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────┴────────────────────────────────────┐
│                    APPLICATION LAYER                         │
│  Controller: PenerimaanPenjualanController                    │
│  - Route handling & request processing                        │
│  - Validation & error handling                                │
│  - Business logic (status calculation)                        │
│  - Response generation                                        │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────┴────────────────────────────────────┐
│                      MODEL LAYER                             │
│  Model: PenerimaanPenjualan + Relations                       │
│  - Data structure definition                                  │
│  - Database mapping (Eloquent ORM)                            │
│  - Relations to FakturPenjualan, User                         │
│  - Helper methods (generateNomorBukti)                        │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────┴────────────────────────────────────┐
│                      DATABASE LAYER                          │
│  Table: penerimaan_penjualan                                  │
│  - 14 columns with proper types & constraints                 │
│  - Foreign keys with cascade delete                           │
│  - Indexes on FK & commonly queried fields                    │
└─────────────────────────────────────────────────────────────┘
```

## Class Diagrams

### Model Relationships

```
User
├─ hasMany('penerimaan', dibuat_oleh_id)
└─ hasMany('penerimaan', disetujui_oleh_id)
   ↑
   │
PenerimaanPenjualan
├─ belongsTo(User, dibuat_oleh_id) → pembuatnya()
├─ belongsTo(User, disetujui_oleh_id) → penyetujunya()
├─ belongsTo(FakturPenjualan, faktur_penjualan_id)
└─ static generateNomorBukti()
   ↑
   │
FakturPenjualan
├─ hasMany(PenerimaanPenjualan, faktur_penjualan_id) → penerimaanPenjualan()
├─ belongsTo(SertifikatPembayaran)
├─ belongsTo(Proyek)
└─ belongsTo(Perusahaan)
```

## Database Design

### Table: penerimaan_penjualan

**Primary Key:** id (BIGINT UNSIGNED AUTO_INCREMENT)

**Columns:**

1. **id** (BIGINT UNSIGNED)
   - Auto-increment primary key
   - Indexes: PRIMARY

2. **no_bukti** (VARCHAR(255))
   - Unique business identifier
   - Format: PN-YYMMDD-XXX
   - Indexes: UNIQUE

3. **tanggal** (DATE)
   - Payment receipt date
   - Stored as DATE (time not needed)
   - Searchable for date range queries

4. **faktur_penjualan_id** (BIGINT UNSIGNED)
   - Foreign key to faktur_penjualan.id
   - Links payment to invoice
   - Indexes: INDEX (for JOIN queries)
   - Constraint: CASCADE DELETE

5. **nominal** (DECIMAL(20,2))
   - Amount received
   - 20 digits total, 2 decimal places
   - Supports up to 99,999,999,999,999,999.99

6. **metode_pembayaran** (VARCHAR(50))
   - Payment method type
   - Values: Tunai, Transfer, Cek, Giro, Kartu Kredit
   - Searchable & filterable

7. **keterangan** (TEXT)
   - Optional notes/description
   - Nullable field
   - Stored as TEXT for variable length

8. **status** (VARCHAR(20))
   - Payment recording status
   - Values: draft, approved
   - Default: 'draft'
   - Searchable for workflow filtering

9. **dibuat_oleh_id** (BIGINT UNSIGNED, NULLABLE)
   - User who created the record
   - Foreign key to users.id (implicit)
   - Nullable for legacy data

10. **disetujui_oleh_id** (BIGINT UNSIGNED, NULLABLE)
    - User who approved the record
    - Foreign key to users.id (implicit)
    - Nullable until approved

11. **tanggal_disetujui** (TIMESTAMP, NULLABLE)
    - Timestamp when approved
    - Captures exact approval time
    - Nullable until approved

12. **created_at** (TIMESTAMP)
    - Auto-set by Laravel on creation
    - Useful for audit trail

13. **updated_at** (TIMESTAMP)
    - Auto-updated by Laravel on any update
    - Tracks last modification

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE KEY (no_bukti)
INDEX (faktur_penjualan_id)
```

**Foreign Keys:**
```sql
FOREIGN KEY (faktur_penjualan_id) 
  REFERENCES faktur_penjualan(id) 
  ON DELETE CASCADE
```

## Code Structure

### Model: PenerimaanPenjualan

```php
class PenerimaanPenjualan extends Model
{
    // Configuration
    protected $table = 'penerimaan_penjualan';
    protected $fillable = [/* 9 fields */];
    protected $casts = [/* date & decimal casts */];
    
    // Relations
    public function fakturPenjualan() { ... }
    public function pembuatnya() { ... }
    public function penyetujunya() { ... }
    
    // Static Methods
    public static function generateNomorBukti() { ... }
}
```

**Key Methods:**

1. **generateNomorBukti()** - Static method
   ```
   Query: Get last no_bukti with prefix PN-YYMMDD
   Logic: Parse last number, increment by 1
   Padding: Zero-fill to 3 digits (XXX)
   Result: PN-260101-001, PN-260101-002, etc.
   ```

2. **Relations** - Eloquent relations
   ```
   fakturPenjualan()     → belongsTo (FK)
   pembuatnya()          → belongsTo (dibuat_oleh_id)
   penyetujunya()        → belongsTo (disetujui_oleh_id)
   ```

### Controller: PenerimaanPenjualanController

```php
class PenerimaanPenjualanController extends Controller
{
    // CRUD Methods
    public function index() { ... }
    public function create() { ... }
    public function store(Request $request) { ... }
    public function show(PenerimaanPenjualan $penerimaan) { ... }
    public function approve(PenerimaanPenjualan $penerimaan) { ... }
    public function destroy(PenerimaanPenjualan $penerimaan) { ... }
    
    // Business Logic
    private function updateFakturPembayaranStatus($id) { ... }
}
```

**Method Details:**

#### 1. index()
```
GET /penerimaan-penjualan
├─ Eager load: fakturPenjualan, pembuatnya, penyetujunya
├─ Order by: tanggal DESC (newest first)
├─ Paginate: 20 per page
└─ Return: view('index', compact('penerimaanPenjualan'))
```

#### 2. create()
```
GET /penerimaan-penjualan/create
├─ Query: FakturPenjualan where status_pembayaran != 'lunas'
├─ Eager load: sertifikatPembayaran relation
└─ Return: view('create', compact('fakturPenjualan'))
```

#### 3. store()
```
POST /penerimaan-penjualan
├─ Validate: All 5 required fields
├─ Generate: no_bukti = PenerimaanPenjualan::generateNomorBukti()
├─ Set: dibuat_oleh_id = auth()->id()
├─ Set: status = 'draft'
├─ Create: PenerimaanPenjualan::create($validated)
├─ Update: updateFakturPembayaranStatus($penerimaan->faktur_penjualan_id)
└─ Redirect: to show page with success message
```

#### 4. show()
```
GET /penerimaan-penjualan/{id}
├─ Find: PenerimaanPenjualan by ID (implicit route model binding)
├─ Load: Relations including fakturPenjualan.sertifikatPembayaran
├─ Calculate: Total received, sisa pembayaran
└─ Return: view('show', compact('penerimaanPenjualan'))
```

#### 5. approve()
```
POST /penerimaan-penjualan/{id}/approve
├─ Check: status === 'draft' (else error)
├─ Update:
│  ├─ status = 'approved'
│  ├─ disetujui_oleh_id = auth()->id()
│  └─ tanggal_disetujui = now()
├─ Save: Model
└─ Redirect: with success message
```

#### 6. destroy()
```
DELETE /penerimaan-penjualan/{id}
├─ Check: status === 'draft' (else error)
├─ Get: fakturId before deleting
├─ Delete: Model
├─ Update: updateFakturPembayaranStatus(fakturId)
└─ Redirect: to index with success message
```

#### 7. updateFakturPembayaranStatus() - Private
```
Logic:
├─ Calculate: totalDiterima = SUM(nominal) 
│  WHERE faktur_penjualan_id = X
│  AND status IN ['draft', 'approved']
├─ Get: faktur.total
├─ Compare:
│  ├─ If totalDiterima >= total → 'lunas'
│  ├─ Else if totalDiterima > 0 → 'sebagian'
│  └─ Else → 'belum_dibayar'
└─ Update: FakturPenjualan.status_pembayaran
```

## Validation Logic

### Server-Side Validation (in store() method)

```php
$validated = $request->validate([
    'tanggal' => 'required|date',
    'faktur_penjualan_id' => 'required|exists:faktur_penjualan,id',
    'nominal' => 'required|numeric|min:0.01',
    'metode_pembayaran' => 'required|string|max:50',
    'keterangan' => 'nullable|string',
]);
```

**Validation Rules Explanation:**

| Field | Rule | Purpose |
|-------|------|---------|
| tanggal | required, date | Must be valid date |
| faktur_penjualan_id | required, exists | Must exist in DB |
| nominal | required, numeric, min:0.01 | Amount > 0 |
| metode_pembayaran | required, string, max:50 | Text up to 50 chars |
| keterangan | nullable, string | Optional text |

**Error Handling:**

- If validation fails → Redirect back with errors
- Form pre-filled with old() data
- Errors displayed in red beneath fields
- User can correct and resubmit

## Business Logic

### Nomor Bukti Generation Algorithm

```
Input: (auto, called when creating penerimaan)

Step 1: Get current date
   $tanggal = now()->format('ymd')  → e.g., "260101"

Step 2: Build prefix
   $prefix = 'PN-' . $tanggal  → e.g., "PN-260101"

Step 3: Find last number with same date
   $last = PenerimaanPenjualan::where('no_bukti', 'like', $prefix . '%')
       ->orderBy('no_bukti', 'desc')
       ->first();

Step 4: Calculate next number
   if ($last) {
       $lastNumber = intval(substr($last->no_bukti, -3))
       $nextNumber = $lastNumber + 1
   } else {
       $nextNumber = 1
   }

Step 5: Format and return
   return $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT)
   → e.g., "PN-260101-001"

Output: Unique daily sequence
```

### Status Pembayaran Calculation

```
Called: After create and after delete

Step 1: Get FakturPenjualan
   $faktur = FakturPenjualan::findOrFail($fakturId)

Step 2: Calculate total received
   $totalDiterima = PenerimaanPenjualan
       ::where('faktur_penjualan_id', $fakturId)
       ->whereIn('status', ['draft', 'approved'])
       ->sum('nominal')

Step 3: Get faktur total
   $total = $faktur->total

Step 4: Determine status
   if ($totalDiterima >= $total) {
       $status = 'lunas'          // Paid in full
   } elseif ($totalDiterima > 0) {
       $status = 'sebagian'       // Partially paid
   } else {
       $status = 'belum_dibayar'  // Unpaid
   }

Step 5: Update faktur
   $faktur->update(['status_pembayaran' => $status])
```

## Route Definition

### Routes Added to web.php

```php
// Inside Route::middleware(['auth'])->group(function () { ... })

Route::get('/penerimaan-penjualan', 
    [PenerimaanPenjualanController::class, 'index'])
    ->name('penerimaan-penjualan.index');
    
Route::get('/penerimaan-penjualan/create', 
    [PenerimaanPenjualanController::class, 'create'])
    ->name('penerimaan-penjualan.create');
    
Route::post('/penerimaan-penjualan', 
    [PenerimaanPenjualanController::class, 'store'])
    ->name('penerimaan-penjualan.store');
    
Route::get('/penerimaan-penjualan/{penerimaanPenjualan}', 
    [PenerimaanPenjualanController::class, 'show'])
    ->name('penerimaan-penjualan.show');
    
Route::post('/penerimaan-penjualan/{penerimaanPenjualan}/approve', 
    [PenerimaanPenjualanController::class, 'approve'])
    ->name('penerimaan-penjualan.approve');
    
Route::delete('/penerimaan-penjualan/{penerimaanPenjualan}', 
    [PenerimaanPenjualanController::class, 'destroy'])
    ->name('penerimaan-penjualan.destroy');
```

**Route Model Binding:**

The route parameter `{penerimaanPenjualan}` automatically:
1. Resolves to PenerimaanPenjualan model instance
2. Uses implicit route model binding
3. Returns 404 if not found
4. No manual `PenerimaanPenjualan::find($id)` needed

## View Structure

### Three Views Created

#### 1. index.blade.php
```
Header
├─ Title
└─ "Buat Penerimaan Baru" button

Alert Section
├─ Success message (if any)
└─ Error message (if any)

Card
├─ Header
└─ Body
    ├─ Table
    │  ├─ Headers: No. Bukti, Tanggal, No. Faktur, Nominal, Metode, Status, Aksi
    │  └─ Rows: @foreach with eye icon button
    └─ Pagination (if applicable)
```

#### 2. create.blade.php
```
Two Column Layout

Left (8 cols):
├─ Form
│  ├─ @csrf token
│  ├─ Faktur dropdown (only belum lunas)
│  ├─ Tanggal input (date)
│  ├─ Nominal input (currency)
│  ├─ Metode dropdown
│  ├─ Keterangan textarea
│  ├─ Buttons: Simpan, Batal
│  └─ Errors displayed below each field

Right (4 cols):
└─ Info card with instructions
```

#### 3. show.blade.php
```
Three Column Layout

Left (8 cols):
├─ Detail card
│  ├─ Header with status badge
│  ├─ Main info (no bukti, tanggal, nominal, metode, status, keterangan)
│  ├─ Audit trail (dibuat/disetujui)
│  └─ HR separator

├─ Faktur card
│  ├─ No faktur link
│  ├─ Tanggal
│  ├─ Total
│  ├─ Status pembayaran badge
│  └─ Payment history table

Right (4 cols):
├─ Action card
│  ├─ Approve button (draft only, POST form)
│  ├─ Delete button (draft only, confirmation)
│  └─ Back button

└─ Summary card
   ├─ Total faktur
   ├─ Total received (✓ success color)
   ├─ Sisa pembayaran (✗ danger color)
   └─ "Terima Lagi" button (if belum lunas)
```

## Performance Considerations

### Database Queries Optimization

1. **Eager Loading** (avoid N+1 problem)
   ```php
   // In index():
   with(['fakturPenjualan', 'pembuatnya', 'penyetujunya'])
   
   // In show():
   load(['fakturPenjualan.sertifikatPembayaran', 'pembuatnya', 'penyetujunya'])
   ```

2. **Pagination** (reduce memory usage)
   ```php
   paginate(20)  // Only load 20 items per page
   ```

3. **Indexes** (speed up queries)
   ```sql
   INDEX (faktur_penjualan_id)  -- For JOINs
   UNIQUE (no_bukti)             -- For uniqueness check
   ```

4. **Efficient Counting**
   ```php
   // Instead of loading all and counting:
   PenerimaanPenjualan::where(...)->count()
   // Instead of loading all and summing:
   PenerimaanPenjualan::where(...)->sum('nominal')
   ```

## Security Measures

1. **Authentication** - All routes protected with `auth` middleware
2. **CSRF Protection** - @csrf token in all forms
3. **Authorization** - Implicit via middleware
4. **SQL Injection** - Prevented by Eloquent parameterized queries
5. **Data Validation** - Server-side form validation
6. **Delete Protection** - Only draft can be deleted (status check)

## Error Handling

### Validation Errors
```
→ Redirect back to form
→ Show errors below fields
→ Preserve old input in form
→ Flash message in red box
```

### Logic Errors
```
→ Check status before approve/delete
→ Return with error message if fails
→ Redirect to detail page, not delete
```

### Database Errors
```
→ Let Laravel handle (500 error)
→ Log to logs/laravel.log
→ User sees generic error page
```

## Testing Strategies

### Unit Testing (Model)
```php
// Test generateNomorBukti()
$first = PenerimaanPenjualan::generateNomorBukti();
$this->assertEquals('PN-260101-001', $first);

// Test generating second of same day
$second = PenerimaanPenjualan::generateNomorBukti();
$this->assertEquals('PN-260101-002', $second);
```

### Feature Testing (Controller)
```php
// Test create flow
$response = $this->post('/penerimaan-penjualan', [...]);
$this->assertDatabaseHas('penerimaan_penjualan', [...]);

// Test approve flow
$response = $this->post("/penerimaan-penjualan/{$id}/approve");
$this->assertEquals('approved', $penerimaan->fresh()->status);
```

### Integration Testing (End-to-End)
```php
// Test complete workflow
1. Create penerimaan
2. Verify in database
3. Approve via POST
4. Check status updated
5. Verify faktur status_pembayaran updated
6. Delete draft
7. Verify cleared & faktur status reset
```

## Migration & Deployment

### Running Migration
```bash
php artisan migrate --step

# Or specific migration:
php artisan migrate --path=database/migrations/2026_01_01_000040_create_penerimaan_penjualan_table.php
```

### Rollback (if needed)
```bash
php artisan migrate:rollback --step=1
```

### Fresh Database (for testing)
```bash
php artisan migrate:fresh --seed
```

## Summary

This implementation provides a robust, well-structured payment receipt system that:
- Follows Laravel best practices
- Uses Eloquent ORM for database operations
- Implements proper validation & error handling
- Maintains data integrity with constraints
- Provides audit trail tracking
- Integrates seamlessly with existing system
- Includes comprehensive documentation
