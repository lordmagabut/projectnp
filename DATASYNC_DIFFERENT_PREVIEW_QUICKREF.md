# Quick Reference - Datasync Different AHSP Preview

## Files Modified
- `resources/views/datasync/index.blade.php` (1283 lines)
  - Added modal: `previewDifferentAhspModal` (lines 173-189)
  - Updated function: `renderDifferent()` (lines 461-505)
  - Added function: `showAhspDifferentPreview()` (lines 967-1175)
  - Added function: `confirmDifferentSync()` (lines 1181-1209)

## Key Features Added

### 1. Modal for Different AHSP Preview
- **Modal ID**: `previewDifferentAhspModal`
- **Size**: `modal-xl` (extra large for side-by-side comparison)
- **Auto-loading**: Fetches AHSP details when user clicks preview
- **Tabs**: Local vs External details comparison

### 2. Preview Button in renderDifferent()
- Small info button with eye icon
- Only shows for AHSP items (not HSD Material/Upah)
- Passes external and local IDs to preview function

### 3. Side-by-Side Comparison Display
- **Header Section**: Shows AHSP code, name, satuan, total harga
- **Details Section**: Tabs for Local and External item details
- **Columns**: Tipe, Kode, Nama, Satuan, Koefisien, Harga, Subtotal, Diskon %, PPN %, Final
- **Formatting**: Currency with separator, percentage 2 decimals

### 4. Update Action
- "Update dengan Data Eksternal" button
- Confirms before executing
- Calls resyncAhsp() endpoint
- Auto-reloads comparison after success

## Usage Example

```javascript
// When user clicks preview button:
showAhspDifferentPreview(
  pair.external.id,  // External AHSP ID
  pair.local.id      // Local AHSP ID
);

// Modal opens and:
// 1. Fetches from datasync.get-ahsp-details endpoint
// 2. Renders header comparison (local vs external)
// 3. Shows details in tabs
// 4. Awaits user action

// When user clicks "Update dengan Data Eksternal":
confirmDifferentSync();
// 1. Shows confirmation dialog
// 2. POSTs to datasync.resync-ahsp
// 3. Closes modal on success
// 4. Reloads comparison
```

## API Endpoints Used

### GET: datasync.get-ahsp-details
- **Query**: `?id=<externalId>`
- **Returns**: 
  ```json
  {
    "success": true,
    "external": {
      "header": { ... },
      "details": [ ... ]
    },
    "existing": {
      "header": { ... },
      "details": [ ... ]
    },
    "hasExisting": true
  }
  ```

### POST: datasync.resync-ahsp
- **Payload**: `{ id: externalId }`
- **Returns**:
  ```json
  {
    "success": true,
    "message": "..."
  }
  ```

## Comparison with Resync Preview

| Feature | Resync | Different |
|---------|--------|-----------|
| Modal ID | previewAhspModal | previewDifferentAhspModal |
| Modal Size | modal-lg | modal-xl |
| Show Type | Single AHSP only new | Side-by-side local vs external |
| Use Case | New AHSP from external | Sync existing AHSP |
| Button | Lanjutkan Sync | Update dengan Data Eksternal |
| Data Source | getAhspDetails (same endpoint) | getAhspDetails (same endpoint) |

## Visual Structure

```
AHSP Berbeda Section
├── Card 1 (AHSP with difference)
│   ├── Header
│   │   ├── Checkbox
│   │   ├── AHSP Code
│   │   └── [Eye Button Preview] ← Klik ini
│   └── Body (inline comparison)
├── Card 2
└── Card N

When Eye Button Clicked:
┌─────────────────────────────────────┐
│ Perbandingan Detail AHSP            │
├─────────────────────────────────────┤
│ ⚠ Alert Info                        │
│                                     │
│ Informasi Header                    │
│ ┌──────────────┬──────────────┐     │
│ │ Lokal        │ Eksternal    │     │
│ ├──────────────┼──────────────┤     │
│ │ Kode         │ Kode         │     │
│ │ Nama         │ Nama         │     │
│ │ Satuan       │ Satuan       │     │
│ │ Total Harga  │ Total Harga  │     │
│ └──────────────┴──────────────┘     │
│                                     │
│ Komponen Penyusun                   │
│ [Tab: Lokal (n)] [Tab: Eksternal]   │
│ ┌─────────────────────────────────┐ │
│ │ Table with details rows         │ │
│ └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│ [Tutup] [Update dengan Data Eksternal]
└─────────────────────────────────────┘
```

## Notes

1. **Same Endpoint Reuse**: Uses existing `datasync.get-ahsp-details` endpoint
2. **Consistent Pattern**: Follows same pattern as resync preview
3. **AHSP Only**: Preview button only shows for AHSP items in "Different" section
4. **Material/Upah Skip**: Other types update directly without preview
5. **Error Handling**: Shows error alert if fetch fails
6. **Loading State**: Shows spinner while fetching details

## Testing Commands

```bash
# Check if modal renders correctly
# 1. Go to datasync/AHSP tab
# 2. Filter to "Berbeda" section
# 3. Look for eye icon on AHSP items
# 4. Click eye icon
# 5. Verify modal opens with correct data

# Check functionality
# 1. Make AHSP items different intentionally
# 2. Click preview button
# 3. Verify data matches database
# 4. Click "Update dengan Data Eksternal"
# 5. Confirm in dialog
# 6. Verify AHSP updated in list
```
