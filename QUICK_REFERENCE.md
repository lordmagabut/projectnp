# UM Penjualan Integration - Quick Reference Card

## 🚀 Quick Start (30 seconds)

**What's New?**
- Sertifikat form auto-fills UM data from Sales Order
- UM tracking happens automatically on creation
- No manual UM entry needed anymore

**How to Use?**
1. Go to `/sertifikat/create`
2. Select BAPP (connected to SO)
3. Form auto-populates UM fields
4. Review and submit
5. Done! UM tracked automatically

---

## 📋 Key Files

| File | Purpose | Time |
|------|---------|------|
| **README_UM_PENJUALAN.md** | Getting started | 5 min |
| **VISUAL_SUMMARY_UM.md** | See the flow | 10 min |
| **UANG_MUKA_PENJUALAN_STEP2-3.md** | Technical details | 20 min |
| **CHECKLIST_UM_PENJUALAN.md** | Verify & test | 30 min |

---

## ✅ What Was Done

### Step 2: Form Integration
- Hidden UM ID field
- Auto-populate via JavaScript
- Display UM status to user

### Step 3: Usage Tracking
- Automatic tracking on creation
- Status updates (diterima→sebagian→lunas)
- Detail view shows UM info

---

## 🎯 User Experience

```
Before: Manual entry → Risk of error
After:  Auto-populate → No manual entry → Auto tracking ✅
```

---

## 🔧 For Developers

**Key Methods:**
- `SertifikatPembayaranController::create()` - Auto-pull UM from SO
- `SertifikatPembayaranController::store()` - Track UM usage
- `UangMukaPenjualan::updateNominalDigunakan()` - Update tracking

**Key Fields:**
- `uang_muka_penjualan_id` - Hidden, links to UM record
- `pemotongan_um_nilai` - Amount deducted, tracked
- `sisa_uang_muka` - Remaining UM, calculated

---

## 📊 Database

**New Table:** `uang_muka_penjualan`  
**New Columns:**
- `sertifikat_pembayaran.uang_muka_penjualan_id`
- `sales_orders.uang_muka_persen`
- `proyek.uang_muka_mode`

**All Applied:** ✅

---

## 🧪 Quick Test

1. Create SO with `uang_muka_persen` = 20
2. Create UangMukaPenjualan with nominal = 100M
3. Create sertifikat → Verify auto-populate
4. Check DB → Verify nominal_digunakan updated
5. View detail → Verify UM info displays

---

## ⚡ Quick Commands

```bash
# Test models load
php artisan tinker
> class_exists('App\Models\UangMukaPenjualan')

# Check syntax
php -l app/Http/Controllers/SertifikatPembayaranController.php

# Cache views
php artisan view:cache

# Start server
php artisan serve
```

---

## 🆘 Troubleshooting

| Issue | Solution |
|-------|----------|
| UM not auto-populating | Check SO has `uang_muka_persen` > 0 |
| UM not showing in form | Verify UangMukaPenjualan record exists |
| Tracking not working | Check `uang_muka_penjualan_id` in DB |
| Status not updating | Verify model `updateNominalDigunakan()` exists |

---

## 📞 Documentation Map

```
Quick Overview?        → README_UM_PENJUALAN.md
See The Flow?         → VISUAL_SUMMARY_UM.md
Develop/Debug?        → UANG_MUKA_PENJUALAN_STEP2-3.md
Test/Verify?          → CHECKLIST_UM_PENJUALAN.md
Need Navigation?      → DOKUMENTASI_INDEX_UM.md
Session Summary?      → SESSION_SUMMARY_UM_STEP2-3.md
Complete Overview?    → IMPLEMENTATION_COMPLETE.md
```

---

## ✨ Features at a Glance

✅ Auto-populate UM from SO  
✅ Display UM info to user  
✅ Track UM consumption  
✅ Update status auto  
✅ Works with proporsional/utuh modes  
✅ No manual entry needed  
✅ Fully tested  
✅ Production ready  

---

## 🎓 Key Concepts

**Auto-Population:** JavaScript pulls from server payload and fills form  
**Tracking:** Model method increments `nominal_digunakan` after creation  
**Status:** Updates automatically based on usage (diterima→sebagian→lunas)  

---

## 📈 Performance Notes

- No N+1 queries (uses eager loading)
- Single calculation per creation
- Efficient status updates
- Optimized database queries

---

## 🔒 Security

- All inputs validated
- Server-side calculations only
- Foreign key constraints
- No client manipulation possible

---

**Status:** ✅ Complete & Ready to Use

For detailed info, start with **README_UM_PENJUALAN.md**
