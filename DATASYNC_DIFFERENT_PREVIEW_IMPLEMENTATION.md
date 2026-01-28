# Modal Preview untuk AHSP Data Berbeda - Implementasi

## Ringkasan
Fitur preview modal telah ditambahkan untuk AHSP items dalam datasync yang memiliki perbedaan antara data lokal dan eksternal. User dapat melihat perbandingan detail sebelum melakukan update.

## Perubahan yang Dilakukan

### 1. Modal Baru: `previewDifferentAhspModal`
**Lokasi**: [resources/views/datasync/index.blade.php](resources/views/datasync/index.blade.php#L173-L189)

Menambahkan modal baru dengan struktur:
- **ID**: `previewDifferentAhspModal` (size: `modal-xl` untuk tampilan lebar)
- **Konten**: `id="previewDifferentContent"` untuk dynamic content
- **Button**: "Tutup" dan "Update dengan Data Eksternal"
- **Button Action**: `onclick="confirmDifferentSync()"`

```html
<div class="modal fade" id="previewDifferentAhspModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Perbandingan Detail AHSP</h5>
      </div>
      <div class="modal-body" id="previewDifferentContent">...</div>
      <div class="modal-footer">
        <button onclick="confirmDifferentSync()">Update dengan Data Eksternal</button>
      </div>
    </div>
  </div>
</div>
```

### 2. Update Function: `renderDifferent()`
**Lokasi**: [resources/views/datasync/index.blade.php](resources/views/datasync/index.blade.php#L461-L505)

Menambahkan preview button untuk items AHSP:
- Button hanya tampil ketika `type === 'ahsp'`
- Icon: eye (mata)
- Action: `onclick="showAhspDifferentPreview(externalId, localId)"`
- Posisi: Header card, sebelah kanan checkbox

```javascript
// Tombol preview hanya untuk AHSP
const previewBtn = type === 'ahsp' ? `
  <button class="btn btn-xs btn-info" title="Lihat Detail" 
    onclick="showAhspDifferentPreview('${pair.external.id}', '${pair.local.id}')">
    <i data-feather="eye" style="width:14px;height:14px;"></i>
  </button>
` : '';
```

### 3. JavaScript Function: `showAhspDifferentPreview()`
**Lokasi**: [resources/views/datasync/index.blade.php](resources/views/datasync/index.blade.php#L967-L1175)

Fungsi untuk menampilkan preview modal dengan perbandingan detail:

**Parameter**:
- `externalId`: ID AHSP di database eksternal
- `localId`: ID AHSP di database lokal

**Proses**:
1. Buka modal `previewDifferentAhspModal`
2. Store pending IDs: `window.pendingDifferentExternalId` dan `window.pendingDifferentLocalId`
3. Fetch data via `getAhspDetails` endpoint (existing endpoint)
4. Render perbandingan side-by-side:
   - Header comparison (2 columns)
   - Details in tabs (Local vs External)
5. Tampilkan data dengan formatting:
   - Tipe item: Badge (Mat/Upah)
   - Harga: Formatted dengan separator
   - Diskon/PPN: 2 decimal places
   - Final: Bold untuk highlight

**HTML Structure**:
```
Alert Info (bahwa data berbeda)
├── Header Comparison (row)
│   ├── Lokal Card
│   └── Eksternal Card
└── Details Tabs
    ├── Tab Local
    │   └── Table dengan 10 kolom
    └── Tab External
        └── Table dengan 10 kolom
```

### 4. JavaScript Function: `confirmDifferentSync()`
**Lokasi**: [resources/views/datasync/index.blade.php](resources/views/datasync/index.blade.php#L1181-L1209)

Fungsi untuk confirm dan execute update:

**Proses**:
1. Validasi `pendingDifferentExternalId`
2. Close modal
3. Ask confirmation user
4. POST ke `datasync.resync-ahsp` endpoint dengan external ID
5. Handle response:
   - Success: Alert success, reload comparison
   - Error: Alert error message

**Endpoint**: 
- Route: `datasync.resync-ahsp`
- Method: POST
- Payload: `{ id: externalId }`

## Cara Kerja Flow

### User Flow untuk "Different" AHSP:
```
1. User lihat AHSP dalam tab "Berbeda"
   ↓
2. User klik tombol Eye (preview)
   ↓
3. Modal "Perbandingan Detail AHSP" terbuka
   ↓
4. Lihat perbandingan header dan details (tabs)
   ↓
5. User klik "Update dengan Data Eksternal"
   ↓
6. Modal close, confirm dialog
   ↓
7. POST resync to external ID
   ↓
8. Success: Modal tutup, data di-reload
```

## Integrasi dengan Existing Code

### Dependencies:
- **Modal Framework**: Bootstrap 5 (existing)
- **Icons**: Feather (existing)
- **Endpoint**: `datasync.get-ahsp-details` (existing)
- **Endpoint**: `datasync.resync-ahsp` (existing)
- **Format Helper**: `parseFloat().toLocaleString('id-ID')` (existing)

### Reusable Components:
- **Fetching**: Sama dengan `showAhspPreview()`
- **Table Rendering**: Sama dengan preview resync
- **Modal Structure**: Bootstrap 5 standard pattern
- **Confirmation**: Same confirm/sync pattern

## Testing Checklist

- [ ] Click eye button pada AHSP berbeda → Modal terbuka
- [ ] Modal menampilkan header local vs external dengan benar
- [ ] Tab details menampilkan item-item dengan formatting benar
- [ ] "Update dengan Data Eksternal" button berfungsi
- [ ] Confirm dialog muncul sebelum sync
- [ ] Data berhasil di-update (check di database)
- [ ] Comparison di-reload setelah update
- [ ] Preview button hanya tampil untuk AHSP (bukan material/upah)
- [ ] Modal close/button berfungsi dengan baik
- [ ] Error handling jika fetch gagal

## Performance Notes

- Modal hanya fetch ketika user klik (lazy loading)
- Menggunakan existing endpoint, no additional database queries
- HTML generation di client-side dengan string interpolation
- Formatter function reuse untuk consistency

## Browser Compatibility

- Bootstrap 5: Modern browsers
- Fetch API: IE11+ (upgrade if needed)
- Dynamic feature graceful (no polyfill required)

## Future Improvements

1. Bisa add "Quick Update All" button untuk update semua AHSP berbeda
2. Bisa highlight field yang berbeda dengan warna khusus
3. Bisa add versioning/history untuk track changes
4. Bisa add conflict resolution UI jika ada update dari berbeda source
