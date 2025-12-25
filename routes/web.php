<?php

use Illuminate\Support\Facades\Route;

// ==== Controllers ====
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\TemplateDokumenController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PemberiKerjaController;
use App\Http\Controllers\ProyekController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\PoController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\FakturController;
use App\Http\Controllers\RabController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\RabScheduleController;
use App\Http\Controllers\RabPenawaranController;
use App\Http\Controllers\RabProgressController;
use App\Http\Controllers\AhspController;
use App\Http\Controllers\HsdMaterialController;
use App\Http\Controllers\HsdUpahController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProyekTaxProfileController;
use App\Http\Controllers\BappController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SertifikatPembayaranController;
use App\Http\Controllers\PembayaranPembelianController;
use App\Http\Controllers\PenerimaanPembelianController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountMappingController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\UangMukaPembelianController;


// =========================
// Login (tanpa proteksi)
// =========================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// =========================
/* Routes yang diproteksi */
// =========================
Route::middleware(['auth'])->group(function () {
    // Kalkulasi ulang harga RAB dari AHSP
    Route::post('rab/{proyek}/recalc-ahsp', [App\Http\Controllers\RabController::class, 'recalcAhsp'])->name('rab.recalc-ahsp');
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ========== User Management & RBAC ==========
    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/user/store', [UserController::class, 'store'])->name('user.store');
    Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}/delete', [UserController::class, 'destroy'])->name('user.destroy');
    Route::get('/user/{user}/logs', [UserController::class, 'logs'])->name('user.logs');

    Route::resource('roles', RoleController::class);
    Route::get('users/{user}/roles', [UserController::class, 'editRoles'])->name('users.editRoles');
    Route::put('users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.updateRoles');
    Route::resource('permissions', PermissionController::class);

    // ========== Master Data ==========
    Route::resource('perusahaan', PerusahaanController::class);
    Route::get('/template-dokumen', [TemplateDokumenController::class, 'index'])->name('template-dokumen.index');
    Route::post('/template-dokumen', [TemplateDokumenController::class, 'store'])->name('template-dokumen.store');
    
    // Account Mapping (Global COA Settings)
    Route::get('/account-mapping', [AccountMappingController::class, 'index'])->name('account-mapping.index');
    Route::put('/account-mapping', [AccountMappingController::class, 'update'])->name('account-mapping.update');
    
    Route::resource('supplier',   SupplierController::class)->middleware('cek_akses_supplier');
    Route::resource('coa',        CoaController::class)->middleware('cek_akses_coa');
    Route::resource('barang',     BarangController::class)->middleware('cek_akses_barang');
    Route::resource('pemberiKerja', PemberiKerjaController::class)->middleware('cek_akses_pemberi_kerja');

    // ========== Proyek ==========
    Route::resource('proyek', ProyekController::class)->middleware('cek_akses_proyek');
    Route::post('proyek/generate-ulang/{proyek_id}', [ProyekController::class, 'generateUlang'])->name('proyek.generateUlang');

    // Kalender & Ringkasan Schedule (di tab Schedule)
    Route::get('/proyek/{proyek}/calendar-events', [ProyekController::class, 'calendarEvents'])->name('proyek.calendar.events');
    Route::get('/proyek/{proyek}/schedule-summary-tree', [ProyekController::class, 'scheduleSummaryTree'])->name('proyek.schedule.summary.tree');

    // Generate Schedule mingguan dari Proyek (legacy)
    Route::post('/proyek/{id}/generate-schedule', [ProyekController::class, 'generateSchedule'])->name('proyek.generateSchedule');
    Route::delete('/proyek/{id}/rab-reset', [ProyekController::class, 'resetRab'])->name('proyek.resetRab');

    // ========== PO ==========
    Route::get('/po/{id}/print',  [PoController::class, 'print'])->name('po.print');
    Route::put('/po/revisi/{id}', [PoController::class, 'revisi'])->name('po.revisi');
    Route::resource('po', PoController::class)->middleware('cek_akses_po');

    // ========== Penerimaan Pembelian ==========
    Route::get('/penerimaan', [PenerimaanPembelianController::class, 'index'])->name('penerimaan.index');
    Route::get('/penerimaan/create/{po_id}', [PenerimaanPembelianController::class, 'create'])->name('penerimaan.create');
    Route::post('/penerimaan/store', [PenerimaanPembelianController::class, 'store'])->name('penerimaan.store');
    Route::get('/penerimaan/{id}', [PenerimaanPembelianController::class, 'show'])->name('penerimaan.show');
    Route::post('/penerimaan/{id}/approve', [PenerimaanPembelianController::class, 'approve'])->name('penerimaan.approve');
    Route::post('/penerimaan/{id}/revisi', [PenerimaanPembelianController::class, 'revisi'])->name('penerimaan.revisi');
    Route::delete('/penerimaan/{id}', [PenerimaanPembelianController::class, 'destroy'])->name('penerimaan.destroy');

    // ========== Retur Pembelian ==========
    Route::get('/retur', [ReturPembelianController::class, 'index'])->name('retur.index');
    Route::get('/retur/create/{penerimaan_id}', [ReturPembelianController::class, 'create'])->name('retur.create');
    Route::post('/retur/store', [ReturPembelianController::class, 'store'])->name('retur.store');
    Route::get('/retur/{id}', [ReturPembelianController::class, 'show'])->name('retur.show');
    Route::post('/retur/{id}/approve', [ReturPembelianController::class, 'approve'])->name('retur.approve');
    Route::post('/retur/{id}/revisi', [ReturPembelianController::class, 'revisi'])->name('retur.revisi');
    Route::delete('/retur/{id}', [ReturPembelianController::class, 'destroy'])->name('retur.destroy');

    // ========== Uang Muka Pembelian ==========
    Route::get('/uang-muka-pembelian', [UangMukaPembelianController::class, 'index'])->name('uang-muka-pembelian.index');
    Route::get('/uang-muka-pembelian/create', [UangMukaPembelianController::class, 'create'])->name('uang-muka-pembelian.create');
    Route::post('/uang-muka-pembelian/store', [UangMukaPembelianController::class, 'store'])->name('uang-muka-pembelian.store');
    Route::get('/uang-muka-pembelian/{id}', [UangMukaPembelianController::class, 'show'])->name('uang-muka-pembelian.show');
    Route::get('/uang-muka-pembelian/{id}/edit', [UangMukaPembelianController::class, 'edit'])->name('uang-muka-pembelian.edit');
    Route::put('/uang-muka-pembelian/{id}', [UangMukaPembelianController::class, 'update'])->name('uang-muka-pembelian.update');
    Route::post('/uang-muka-pembelian/{id}/approve', [UangMukaPembelianController::class, 'approve'])->name('uang-muka-pembelian.approve');
    Route::post('/uang-muka-pembelian/{id}/revisi', [UangMukaPembelianController::class, 'revisi'])->name('uang-muka-pembelian.revisi');
    Route::get('/uang-muka-pembelian/{id}/edit-paid', [UangMukaPembelianController::class, 'editPaid'])->name('uang-muka-pembelian.edit-paid');
    Route::put('/uang-muka-pembelian/{id}/update-paid', [UangMukaPembelianController::class, 'updatePaid'])->name('uang-muka-pembelian.update-paid');
    Route::post('/uang-muka-pembelian/{id}/cancel-payment', [UangMukaPembelianController::class, 'cancelPayment'])->name('uang-muka-pembelian.cancel-payment');
    Route::delete('/uang-muka-pembelian/{id}', [UangMukaPembelianController::class, 'destroy'])->name('uang-muka-pembelian.destroy');
    Route::get('/uang-muka-pembelian/{id}/bkk', [UangMukaPembelianController::class, 'printBkk'])->name('uang-muka-pembelian.bkk');
    Route::get('/uang-muka-pembelian/{id}/bkk/create', [UangMukaPembelianController::class, 'createBkk'])->name('uang-muka-pembelian.bkk.create');
    Route::post('/uang-muka-pembelian/{id}/bkk', [UangMukaPembelianController::class, 'storeBkk'])->name('uang-muka-pembelian.bkk.store');

    // ========== Faktur Pembelian ==========
    Route::get('/faktur/create-from-po/{po}', [FakturController::class, 'createFromPo'])->name('faktur.createFromPo');
    Route::get('/faktur/create-from-penerimaan/{penerimaan}', [FakturController::class, 'createFromPenerimaan'])->name('faktur.createFromPenerimaan');
    Route::get('/faktur/create',              [FakturController::class, 'create'])->name('faktur.create');
    Route::post('/faktur/store',              [FakturController::class, 'store'])->name('faktur.store');
    Route::get('/faktur',                     [FakturController::class, 'index'])->name('faktur.index');
    Route::get('/faktur/{id}',                [FakturController::class, 'show'])->name('faktur.show');
    Route::delete('/faktur/{id}',             [FakturController::class, 'destroy'])->name('faktur.destroy');
    Route::post('/faktur/{id}/revisi',        [FakturController::class, 'revisi'])->name('faktur.revisi');
    Route::post('/faktur/{id}/approve',       [FakturController::class, 'approve'])->name('faktur.approve');


    Route::get('/pembayaran', [PembayaranPembelianController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/{id}', [PembayaranPembelianController::class, 'show'])->name('pembayaran.show');
    Route::get('/pembayaran/create/{faktur_id}', [PembayaranPembelianController::class, 'create'])->name('pembayaran.create');
    Route::post('/pembayaran/store', [PembayaranPembelianController::class, 'store'])->name('pembayaran.store');
    Route::delete('/pembayaran/{id}', [PembayaranPembelianController::class, 'destroy'])->name('pembayaran.destroy');

    // ========== RAB & AHSP ==========
    Route::get('/ahsp/search',                 [AhspController::class, 'search'])->name('ahsp.search');
    Route::post('/ahsp/{ahsp}/duplicate',      [AhspController::class, 'duplicate'])->name('ahsp.duplicate');
    Route::resource('ahsp', AhspController::class);
    Route::resource('hsd-material', HsdMaterialController::class);
    Route::resource('hsd-upah',     HsdUpahController::class);
    Route::get('/hsd-material/{id}/history', [HsdMaterialController::class, 'history']);
    Route::get('/hsd-upah/{id}/history', [HsdUpahController::class, 'history']);


    // RAB umum
    Route::get('/rab/{proyek_id}',                 [RabController::class, 'index'])->name('rab.index');
    Route::get('/proyek/{proyek_id}/rab',          [RabController::class, 'input'])->name('rab.input');
    Route::post('/rab/import',                     [RabController::class, 'import'])->name('rab.import');
    Route::delete('/rab/reset/{proyek_id}',        [RabController::class, 'reset'])->name('rab.reset');

    // Template import RAB
    Route::get('/rab/import/template', [RabController::class, 'downloadTemplate'])->name('rab.template');
    Route::get('/rab/import/template-readme', [RabController::class, 'downloadTemplateReadme'])->name('rab.template.readme');

    // ========== RAB Penawaran ==========
    Route::prefix('proyek/{proyek}/penawaran')->name('proyek.penawaran.')->group(function () {
        Route::get('/',         [RabPenawaranController::class, 'index'])->name('index');
        Route::get('/data',     [RabPenawaranController::class, 'data'])->name('data');

        // AJAX search untuk form create/edit
        Route::get('/search-rab-headers', [RabPenawaranController::class, 'searchRabHeaders'])->name('searchRabHeaders');
        Route::get('/search-rab-details', [RabPenawaranController::class, 'searchRabDetails'])->name('searchRabDetails');

        Route::get('/create',   [RabPenawaranController::class, 'create'])->name('create');
        Route::post('/',        [RabPenawaranController::class, 'store'])->name('store');
        Route::get('/{penawaran}',        [RabPenawaranController::class, 'show'])->name('show');
        Route::get('/{penawaran}/edit',   [RabPenawaranController::class, 'edit'])->name('edit');
        Route::put('/{penawaran}',        [RabPenawaranController::class, 'update'])->name('update');
        Route::delete('/{penawaran}',     [RabPenawaranController::class, 'destroy'])->name('destroy');

        Route::get('/{penawaran}/pdf',        [RabPenawaranController::class, 'generatePdf'])->name('generatePdf');
        Route::get('/{penawaran}/pdf-split',  [RabPenawaranController::class, 'generatePdfSplit'])->name('generatePdfSplit');
        Route::get('/{penawaran}/PdfSinglePrice',  [RabPenawaranController::class, 'generatePdfSinglePrice'])->name('generatePdfSinglePrice');
        Route::get('/{penawaran}/show-gab',   [RabPenawaranController::class, 'showGab'])->name('showGab');

        // Approve & Snapshot schedule penawaran
        Route::post('/{penawaran}/approve',  [RabPenawaranController::class, 'approve'])->name('approve');
        Route::post('/{penawaran}/snapshot', [RabScheduleController::class,   'snapshot'])->name('snapshot');
    });

    // ========== Tab RAB Schedule (setup & generate) ==========
    Route::prefix('/proyek/{proyek}/rab-schedule')->group(function () {
        Route::get('/',               [RabScheduleController::class, 'index'])->name('rabSchedule.index');
        Route::get('/{penawaran}',    [RabScheduleController::class, 'edit'])->name('rabSchedule.edit');
        Route::post('/{penawaran}/save',     [RabScheduleController::class, 'saveSetup'])->name('rabSchedule.save');
        Route::post('/{penawaran}/generate', [RabScheduleController::class, 'generate'])->name('rabSchedule.generate');
    });

    // ========== Input Schedule Manual (opsional lama) ==========
    Route::get('proyek/{proyek}/schedule-input',  [ScheduleController::class, 'create'])->name('schedule.create');
    Route::post('proyek/{proyek}/schedule-input', [ScheduleController::class, 'store'])->name('schedule.store');

    // ========== PROGRESS ==========
    Route::prefix('proyek/{proyek}/progress')->name('proyek.progress.')->group(function () {
        Route::get('input',                [RabProgressController::class, 'create'])->name('create');
        Route::post('/',                   [RabProgressController::class, 'store'])->name('store');
        Route::get('{progress}',           [RabProgressController::class, 'detail'])->name('detail');
        Route::get('{progress}/edit',      [RabProgressController::class, 'edit'])->name('edit');      // ← baru
        Route::post('{progress}/save',     [RabProgressController::class, 'saveDraft'])->name('save'); // ← baru
        Route::post('{progress}/finalize', [RabProgressController::class, 'finalize'])->name('finalize');
        Route::delete('{progress}',        [RabProgressController::class, 'destroy'])->name('destroy');
        Route::post('{progress}/revisi',   [RabProgressController::class, 'revisi'])->name('revisi');  // perbaiki name
    });    

    Route::prefix('sertifikat')->name('sertifikat.')->group(function () {
        Route::get('/',            [SertifikatPembayaranController::class,'index'])->name('index');
        Route::get('/create',      [SertifikatPembayaranController::class,'create'])->name('create');
        Route::post('/',           [SertifikatPembayaranController::class,'store'])->name('store');
        Route::get('/{id}',        [SertifikatPembayaranController::class,'show'])->name('show');
        Route::get('/{id}/cetak',  [SertifikatPembayaranController::class,'cetak'])->name('cetak'); // PDF portrait
        Route::resource('sertifikat', SertifikatPembayaranController::class);
        Route::get('sertifikat/{id}/pdf', [SertifikatPembayaranController::class, 'generatePdf'])
    ->name('sertifikat.pdf');


    });

    Route::get('/proyek/{proyek}/penawaran/{penawaran}/pdf-mixed',
        [RabPenawaranController::class, 'generatePdfMixed']
    )->name('proyek.penawaran.pdf-mixed');

    Route::get('/proyek/{proyek}/penawaran/{penawaran}/pdf-PdfSinglePrice',
        [RabPenawaranController::class, 'generatePdfSinglePrice']
    )->name('proyek.penawaran.PdfSinglePrice');

    // ========== Akuntansi & Laporan ==========
    Route::get('/jurnal', [\App\Http\Controllers\JurnalController::class, 'index'])->name('jurnal.index');

    Route::get('/jurnal/detail/{id}', [JurnalController::class, 'showDetail'])->name('jurnal.showDetail');

    Route::get('/jurnal/create', [JurnalController::class, 'create'])->name('jurnal.create');
    Route::get('/jurnal/edit', [JurnalController::class, 'edit'])->name('jurnal.edit');
    Route::delete('jurnal/destroy/{id}', [JurnalController::class, 'destroy'])->name('jurnal.destroy');
    Route::post('/jurnal',        [JurnalController::class, 'store'])->name('jurnal.store');
    Route::get('/buku-besar', [\App\Http\Controllers\BukuBesarController::class, 'index'])->name('buku-besar.index');

    Route::get('/laporan/neraca',    [LaporanController::class, 'neraca'])->name('laporan.neraca');
    Route::get('/laporan/laba-rugi', [LaporanController::class, 'labaRugi'])->name('laporan.labaRugi');
    Route::get('/laporan/general-ledger', [LaporanController::class, 'generalLedger'])->name('laporan.general-ledger');

    // ========== Saldo Awal ==========
    Route::get('/saldo-awal', [OpeningBalanceController::class, 'index'])->name('opening-balance.index');
    Route::get('/saldo-awal/create', [OpeningBalanceController::class, 'create'])->name('opening-balance.create');
    Route::post('/saldo-awal', [OpeningBalanceController::class, 'store'])->name('opening-balance.store');
    Route::delete('/saldo-awal/{id}', [OpeningBalanceController::class, 'destroy'])->name('opening-balance.destroy');

    // ========== Profil Pajak Proyek ==========
    Route::resource('proyek-tax-profiles', ProyekTaxProfileController::class)
        ->parameters(['proyek-tax-profiles' => 'profile'])
        ->only(['index','create','store','edit','update']);
    // Jika ingin mengizinkan hapus, ganti baris di atas dengan:
    // ->only(['index','create','store','edit','update','destroy']);

    Route::put(
    '/proyek/{proyek}/penawaran/{penawaran}/keterangan',
    [\App\Http\Controllers\RabPenawaranController::class, 'updateKeterangan']
    )->name('proyek.penawaran.updateKeterangan');
    
    Route::get('/proyek/{proyek}/penawaran/{penawaran}/approval/view/{encoded}',
        [RabPenawaranController::class, 'viewApproval'])
        ->where('encoded', '.*')
        ->name('proyek.penawaran.approval.view');

    Route::get('/proyek/{proyek}/penawaran/{penawaran}/approval/download/{encoded}',
        [RabPenawaranController::class, 'downloadApproval'])
        ->where('encoded', '.*')
        ->name('proyek.penawaran.approval.download');

    // Sales Order (SO) - daftar berasal dari penawaran yang telah disetujui (status = 'final')
    Route::get('/so', [SalesOrderController::class, 'index'])->name('so.index');
    Route::get('/so/{id}', [SalesOrderController::class, 'show'])->name('so.show');


    Route::prefix('proyek/{proyek}')->group(function () {
        Route::get('bapp',                [BappController::class,'index'])->name('bapp.index');
        Route::get('bapp/create',         [BappController::class, 'create'])->name('bapp.create');
        Route::post('bapp',               [BappController::class,'store'])->name('bapp.store');
        Route::get('bapp/{bapp}',         [BappController::class, 'show'])->name('bapp.show');
        Route::get('bapp/{bapp}/pdf',     [BappController::class,'pdf'])->name('bapp.pdf'); // view/download PDF
        Route::post('bapp/{bapp}/submit', [BappController::class,'submit'])->name('bapp.submit');
        Route::post('bapp/{bapp}/approve',[BappController::class,'approve'])->name('bapp.approve');
        Route::delete('bapp/{bapp}',      [BappController::class, 'destroy'])->name('bapp.destroy');
        });

    // ========== Profil & Ganti Password ==========
    Route::get('/general/profile', [\App\Http\Controllers\ProfileController::class,'show'])
    ->name('profile.show')
    ->middleware('auth');
    
    // ========== API Endpoints ==========
    Route::get('/api/uang-muka-by-supplier/{supplier_id}', function ($supplier_id) {
        $umList = \App\Models\UangMukaPembelian::where('id_supplier', $supplier_id)
            ->where('status', 'approved')
            ->select('id', 'no_uang_muka', 'nominal', 'nominal_digunakan')
            ->get()
            ->map(function ($um) {
                return [
                    'id' => $um->id,
                    'no_uang_muka' => $um->no_uang_muka,
                    'nominal' => $um->nominal,
                    'nominal_digunakan' => $um->nominal_digunakan,
                ];
            });
        return response()->json($umList);
    });
});
