# 💡 SOLUSI: Handle Item dengan Profit/Overhead Berbeda dari Section

## Analisis Masalah

Skenario yang bisa terjadi:

```
Section Header: Profit=10%, Overhead=5%
├─ Item A: dibuat dengan Profit=10%, Overhead=5% ✓ (sesuai)
├─ Item B: dibuat dengan Profit=15%, Overhead=3% ✗ (berbeda - untuk negosiasi khusus)
└─ Item C: dibuat dengan Profit=10%, Overhead=5% ✓ (sesuai)
```

**Pertanyaan:** Bagaimana membedakan item yang intentionally berbeda vs item yang buggy/tidak sinkron?

---

## 🎯 5 Approach Solusi

### **APPROACH 1: Item-Level Override dengan Setting Explicit**

**Konsep:**
- Setiap item punya **checkbox "Override Profit/Overhead"**
- Jika di-check → user bisa input profit/overhead custom untuk item itu
- Jika tidak di-check → pakai section punya

**Struktur Database:**
```sql
ALTER TABLE rab_penawaran_items ADD COLUMN profit_percentage_override DECIMAL(5,2) NULL;
ALTER TABLE rab_penawaran_items ADD COLUMN overhead_percentage_override DECIMAL(5,2) NULL;
ALTER TABLE rab_penawaran_items ADD COLUMN use_item_level_margins BOOLEAN DEFAULT 0;
```

**UI di Edit Penawaran:**
```blade
<tr>
  <td>
    <div class="form-check">
      <input type="checkbox" class="form-check-input toggle-override" 
             name="items[0][use_item_level_margins]">
      <label class="form-check-label">Override?</label>
    </div>
  </td>
  <td>
    <div class="override-fields" style="display:none;">
      <input type="number" name="items[0][profit_percentage_override]" 
             placeholder="Profit %" step="0.01">
    </div>
  </td>
  <td>
    <div class="override-fields" style="display:none;">
      <input type="number" name="items[0][overhead_percentage_override]" 
             placeholder="Overhead %" step="0.01">
    </div>
  </td>
  <td>
    <span class="profit-used">10%</span> / <span class="overhead-used">5%</span>
  </td>
</tr>
```

**Keuntungan:**
- ✅ Explicit & User-controlled (tidak ada bug yang tersembunyi)
- ✅ Mudah di-audit (ada checkbox yang terlihat)
- ✅ Bisa di-query: "Show all items dengan override margin"
- ✅ User tahu apa yang dia lakukan

**Kerugian:**
- ❌ Menambah kompleksitas UI
- ❌ Memerlukan user awareness (harus paham kapan harus override)
- ❌ Bisa ada user yang override tanpa alasan jelas

**Implementasi Effort: MEDIUM**

---

### **APPROACH 2: Automatic Detection + Warning System**

**Konsep:**
- **Tidak ada override** - semua item HARUS mpakai section punya
- Tapi ada **validation & warning** jika ketemu mismatch:
  ```
  ⚠️ ALERT: Item 2.1.69 punya harga yang tidak sesuai dengan 
     Profit 10% + Overhead 5% section ini.
     
     Expected Harga Upah: 1176.47
     Actual Harga Upah:   1282.05
     Selisih: +105.58 (9%)
     
     [🔄 Recalculate] [❌ Remove] [ℹ️ Details]
  ```

**Struktur Database:**
```sql
-- Tambah kolom tracking
ALTER TABLE rab_penawaran_items ADD COLUMN 
  harga_material_expected DECIMAL(16,2) NULL; -- expected berdasarkan section
ALTER TABLE rab_penawaran_items ADD COLUMN 
  harga_upah_expected DECIMAL(16,2) NULL;
ALTER TABLE rab_penawaran_items ADD COLUMN 
  last_verified_at TIMESTAMP NULL;
ALTER TABLE rab_penawaran_items ADD COLUMN 
  verification_status ENUM('ok', 'mismatch', 'unchecked') DEFAULT 'unchecked';
```

**Proses Verificasi (background atau on-demand):**
```php
// Di controller, buat method verifyItemMargins()
public function verifyItemMargins(RabPenawaranItem $item)
{
    $section = $item->section;
    $rabDetail = $item->rabDetail;
    
    // Hitung expected harga
    [$matDasar, $upahDasar] = $this->deriveMaterialUpahFromDetail($rabDetail);
    $denom = 1 - ($section->profit_percentage/100) - ($section->overhead_percentage/100);
    
    $expectedMat = $matDasar / $denom;
    $expectedUpah = $upahDasar / $denom;
    
    // Banding dengan actual
    $matActual = (float)$item->harga_material_penawaran_item;
    $upahActual = (float)$item->harga_upah_penawaran_item;
    
    $tolerance = 0.01; // 1% tolerance untuk rounding
    $matDeviation = abs($expectedMat - $matActual) / $expectedMat;
    $upahDeviation = abs($expectedUpah - $upahActual) / $expectedUpah;
    
    if ($matDeviation > $tolerance || $upahDeviation > $tolerance) {
        $item->update([
            'verification_status' => 'mismatch',
            'harga_material_expected' => $expectedMat,
            'harga_upah_expected' => $expectedUpah,
            'last_verified_at' => now(),
        ]);
        
        return 'mismatch';
    } else {
        $item->update([
            'verification_status' => 'ok',
            'last_verified_at' => now(),
        ]);
        
        return 'ok';
    }
}
```

**UI Perubahan Penawaran:**
```blade
<div class="alert alert-warning">
  <strong>⚠️ {{ $mismatchCount }} items punya harga tidak sesuai section</strong>
  
  <table class="table table-sm mt-2">
    <tr>
      <td>Item 2.1.69</td>
      <td>Upah Actual: 1282.05 | Expected: 1176.47 | +8.3%</td>
      <td>
        <button onclick="recalculateItem(69)">🔄 Recalc</button>
      </td>
    </tr>
  </table>
</div>
```

**Keuntungan:**
- ✅ Automatic detection (tidak perlu manual)
- ✅ Transparansi penuh (user lihat ada mismatch)
- ✅ Mudah untuk mass-recalculate
- ✅ Audit trail (verification_status history)

**Kerugian:**
- ❌ Bisa "false positive" jika ada rounding di SAP lain
- ❌ User harus tahu tolerance berapa
- ❌ Tidak support intentional override (jika ada special pricing)

**Implementasi Effort: MEDIUM**

---

### **APPROACH 3: Section-Level Enforcement (Simple & Strict)**

**Konsep:**
- **Strict rule:** Semua item dalam section HARUS pakai profit/overhead section
- **Enforcement:** 
  1. Saat item dibuat → auto-calculate dengan section punya
  2. Saat section profit/overhead diubah → ask user: "Recalc all items?" atau "Keep old prices?"
  3. Saat edit item → **disable manual price edit** (read-only atau dynamic)

**Struktur Database:**
```sql
-- Cukup tracking kapan section profit/overhead terakhir diubah
ALTER TABLE rab_penawaran_sections ADD COLUMN 
  last_profit_overhead_change_at TIMESTAMP NULL;
```

**Implementasi:**

1. **Saat Create Item:**
```php
// Automatic pakai section punya
$denom = 1 - ($section->profit_percentage/100) - ($section->overhead_percentage/100);
$item->harga_material_penawaran_item = $matDasar / $denom;
$item->harga_upah_penawaran_item = $upahDasar / $denom;
$item->save();
```

2. **Saat Edit Section Profit/Overhead:**
```php
// Dialog ke user
if ($oldProfit != $newProfit || $oldOverhead != $newOverhead) {
    // Show modal: "Profit/Overhead berubah dari 10%/5% menjadi 15%/3%
    // Apakah Anda ingin recalculate semua items?"
    
    if (user_click_yes) {
        recalculateSectionItems($section);
    }
    // Jika no → items tetap pakai harga lama (tapi ada flag warning)
}
```

3. **UI Edit Item:**
```blade
<!-- Harga bersifat READ-ONLY / DYNAMIC -->
<td class="bg-light">
  <!-- Read-only display, calculated from section -->
  Rp {{ number_format($item->harga_upah_penawaran_item, 0) }}
  <small class="text-muted">(auto-calculated)</small>
</td>
```

**Keuntungan:**
- ✅ **Simplest approach** - paling straightforward
- ✅ No ambiguity - item selalu sesuai section
- ✅ Automatic recalc - less manual work
- ✅ Audit trail jelas (ada explicit recalc event)

**Kerugian:**
- ❌ Tidak support special/negotiated pricing per item
- ❌ Less flexible untuk complex scenarios
- ❌ User control terbatas

**Implementasi Effort: EASY**

---

### **APPROACH 4: Snapshot + Flexible Override (Best of Both Worlds)**

**Konsep (Hybrid):**
- **Default behavior:** Item pakai section punya (auto-calculated)
- **Special case:** User bisa "lock/override" profit/overhead untuk specific item (dengan reason/note)
- **Verification:** System track mana item yang override vs default
- **Dashboard:** Visual distinction antara normal items vs override items

**Struktur Database:**
```sql
ALTER TABLE rab_penawaran_items ADD COLUMN 
  profit_percentage_applied DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE rab_penawaran_items ADD COLUMN 
  overhead_percentage_applied DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE rab_penawaran_items ADD COLUMN 
  is_margin_override BOOLEAN DEFAULT 0;
ALTER TABLE rab_penawaran_items ADD COLUMN 
  margin_override_reason VARCHAR(255) NULL; -- "Negosiasi khusus", "VIP client", etc
ALTER TABLE rab_penawaran_items ADD COLUMN 
  margin_override_by INT NULL REFERENCES users(id);
ALTER TABLE rab_penawaran_items ADD COLUMN 
  margin_override_at TIMESTAMP NULL;
```

**Workflow:**

1. **Normal Flow (Item Default):**
```
User create item
  ↓
Backend auto-calculate: profit = section punya
  ↓
Save:
  - profit_percentage_applied = section punya
  - overhead_percentage_applied = section punya
  - is_margin_override = false
```

2. **Override Flow (Item Special):**
```
User check "Override profit/overhead"
  ↓
Input custom profit/overhead + reason
  ↓
Backend recalculate dengan custom value
  ↓
Save:
  - profit_percentage_applied = custom value
  - overhead_percentage_applied = custom value
  - is_margin_override = true
  - margin_override_reason = "Negosiasi khusus"
  - margin_override_by = auth()->id()
  - margin_override_at = now()
```

**UI Dashboard/Table:**
```blade
<tr class="@if($item->is_margin_override) table-warning @endif">
  <td>
    {{ $item->kode }}
    @if($item->is_margin_override)
      <span class="badge bg-warning text-dark ms-2">
        ⚡ Override: {{ $item->margin_override_reason }}
      </span>
    @endif
  </td>
  <td>Profit: {{ $item->profit_percentage_applied }}% 
      @if($item->is_margin_override)
        (Custom - {{ $item->marginOverrideBy->name ?? 'System' }})
      @else
        (Section Default)
      @endif
  </td>
  <td>{{ number_format($item->harga_upah_penawaran_item, 0) }}</td>
  <td>
    @if($item->is_margin_override)
      <button onclick="removeOverride({{ $item->id }})">Hapus Override</button>
    @endif
  </td>
</tr>
```

**Keuntungan:**
- ✅ Flexible - support both default & special cases
- ✅ Clear audit trail (track siapa override kapan dan kenapa)
- ✅ Snapshot stored - bisa revert atau compare
- ✅ Visual distinction - mudah identify override items
- ✅ Enterprise-ready (dengan approval workflow bisa)

**Kerugian:**
- ❌ Most complex approach
- ❌ Mehr database fields
- ❌ UI lebih complicated
- ❌ QA effort lebih besar

**Implementasi Effort: MEDIUM-HIGH**

---

### **APPROACH 5: Two-Phase Approval (Most Controlled)**

**Konsep (Enterprise-grade):**
- **Phase 1 - Draft:** Item dibuat dengan default section punya, tapi bisa ada suggested override
- **Phase 2 - Review & Approve:** Manager review, approve/reject overrides, baru finalize

**Struktur Database:**
```sql
ALTER TABLE rab_penawaran_items ADD COLUMN 
  profit_percentage_applied DECIMAL(5,2);
ALTER TABLE rab_penawaran_items ADD COLUMN 
  overhead_percentage_applied DECIMAL(5,2);
ALTER TABLE rab_penawaran_items ADD COLUMN 
  margin_approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
ALTER TABLE rab_penawaran_items ADD COLUMN 
  margin_approved_by INT REFERENCES users(id);
ALTER TABLE rab_penawaran_items ADD COLUMN 
  margin_approved_at TIMESTAMP;
```

**Workflow:**
```
Salesman create penawaran dengan Draft items
  ↓
  [DRAFT - Harga semua items based on section]
  
Manager review → spot ada item yang harga aneh
  ↓
Manager bisa suggest override margin
  ↓
System recalculate item dengan custom margin
  ↓
Manager set status = 'pending approval'
  ↓
Director review & approve/reject overrides
  ↓
[APPROVED] → Penawaran siap kirim customer
```

**UI Manager:**
```blade
<div class="card">
  <h6>Items dengan Margin Alert</h6>
  <table>
    <tr>
      <td>Item 2.1.69</td>
      <td>Current: Profit 10%, OH 5% (Section default)</td>
      <td>
        <input type="number" id="override_profit" placeholder="Profit custom %">
        <button onclick="suggestOverride()">Suggest Override</button>
      </td>
      <td>
        <small class="text-muted">Reason: ____________</small>
      </td>
    </tr>
  </table>
  <button class="btn btn-primary">Submit for Approval</button>
</div>
```

**Keuntungan:**
- ✅ Full control & visibility
- ✅ Approval trail (untuk compliance)
- ✅ Multiple stakeholder involvement
- ✅ Reduces manual errors
- ✅ Good untuk complex org

**Kerugian:**
- ❌ Slowest approach
- ❌ Most complex system
- ❌ Workflow overhead
- ❌ Not for small teams

**Implementasi Effort: HIGH**

---

## 📊 Comparison Matrix

| Approach | Complexity | Flexibility | Audit Trail | Risk | Best For |
|----------|-----------|-------------|-------------|------|----------|
| **1. Item Override** | Medium | ✅✅✅ High | Manual | Medium | Small teams, custom pricing |
| **2. Auto Detection** | Medium | ✅ Low | Auto | Low | Catching bugs, QA |
| **3. Strict Enforcement** | Easy | ✗ Very Low | Auto | Lowest | Simple projects |
| **4. Snapshot + Override** | Medium-High | ✅✅ Medium | Auto | Low | Most projects |
| **5. Two-Phase Approval** | High | ✅✅✅ High | Full | Lowest | Enterprise, compliance |

---

## 🎯 Rekomendasi (by use case)

### **Jika:** Proyek sederhana, team kecil, jarang ada special pricing
→ **Gunakan APPROACH 3 (Strict Enforcement)**
```
Alasan:
- Simple & maintainable
- Minimal manual work
- No ambiguity
```

### **Jika:** Proyek medium, ada beberapa special cases, perlu audit trail
→ **Gunakan APPROACH 4 (Snapshot + Override)**
```
Alasan:
- Good balance flexibility vs simplicity
- Clear tracking siapa override kapan kenapa
- Easy to spot override items
- Can handle most real-world scenarios
```

### **Jika:** Proyek kompleks, multiple stakeholders, strict compliance needed
→ **Gunakan APPROACH 5 (Two-Phase Approval)**
```
Alasan:
- Full visibility & control
- Approval workflow
- Good for enterprise/large org
```

### **Jika:** Need bisnis logic yang fleksibel, tapi catch bugs
→ **Kombinasi APPROACH 2 + 4:**
```
- Use Approach 4 untuk handle intentional overrides
- Add Approach 2 verification untuk catch unintended mismatches
```

---

## Pilihan untuk Aplikasi Ini

Mengingat konteks aplikasi Anda (RAB proyek konstruksi, needs audit trail), saya rekomendasikan:

### **PRIMARY: APPROACH 4 (Snapshot + Override)** ⭐

**Alasan:**
1. Balance antara flexibility dan simplicity
2. Enterprise-ready (ada audit trail)
3. Support both common case (default) dan edge case (override)
4. UI/UX cukup straightforward
5. Database changes manageable
6. Migration path jelas (backward compatible)

**Implementasi Next Steps:**
1. Add 4-5 kolom di `rab_penawaran_items`
2. Create migration untuk new columns
3. Update RabPenawaranController:
   - Saat create: set `profit_percentage_applied` = section punya
   - Handle override di update
4. Update UI edit penawaran:
   - Tampilkan profit/overhead applied (dengan visual indicator)
   - Add override toggle section
5. Add verification query untuk audit:
   - "Show all override items"
   - "Show items by override reason"
   - "Show override history"

---

## Alternative: Light Version dari Approach 4

Jika ingin lebih simple dulu, bisa **start dengan versi light**:

```sql
-- Cukup 2 kolom dulu (bukan 5)
ALTER TABLE rab_penawaran_items ADD COLUMN 
  profit_percentage_applied DECIMAL(5,2) DEFAULT 0;
ALTER TABLE rab_penawaran_items ADD COLUMN 
  overhead_percentage_applied DECIMAL(5,2) DEFAULT 0;

-- (Kolom override_reason, override_by, override_at bisa ditambah nanti)
```

**Di fase 1:** Hanya track applied margins, no override yet.
**Di fase 2:** Nanti bisa add override fields kalau butuh.

---

## 🔧 Implementation Risk

| Approach | Risk Level | Mitigation |
|----------|------------|-----------|
| 1. Item Override | Medium | Clear UI, good documentation |
| 2. Auto Detection | Low | Set proper tolerance, good alerting |
| 3. Strict Enforcement | Low | Simple logic, less edge cases |
| 4. Snapshot + Override | Low-Medium | Good testing, clear migration |
| 5. Two-Phase Approval | Medium-High | Workflow testing, change mgmt |

---

## Mana yang mau dicoba dulu? 👀

Saya ready untuk:
- ✅ Design database migration
- ✅ Write controller logic
- ✅ Build UI component
- ✅ Create unit tests

Tapi mule dengan mana dulu?

