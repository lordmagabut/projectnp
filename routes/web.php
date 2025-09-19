<?php

use Illuminate\Support\Facades\Route;

// ==== Controllers ====
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerusahaanController;
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

    // Dashboard
    Route::get('/', fn () => view('dashboard'))->name('dashboard');

    // ========== User Management & RBAC ==========
    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/user/store', [UserController::class, 'store'])->name('user.store');
    Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}/delete', [UserController::class, 'destroy'])->name('user.destroy');

    Route::resource('roles', RoleController::class);
    Route::get('users/{user}/roles', [UserController::class, 'editRoles'])->name('users.editRoles');
    Route::put('users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.updateRoles');
    Route::resource('permissions', PermissionController::class);

    // ========== Master Data ==========
    Route::resource('perusahaan', PerusahaanController::class);
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

    // ========== Faktur Pembelian ==========
    Route::get('/faktur/create-from-po/{po}', [FakturController::class, 'createFromPo'])->name('faktur.createFromPo');
    Route::get('/faktur/create',              [FakturController::class, 'create'])->name('faktur.create');
    Route::post('/faktur/store',              [FakturController::class, 'store'])->name('faktur.store');
    Route::get('/faktur',                     [FakturController::class, 'index'])->name('faktur.index');
    Route::get('/faktur/{id}',                [FakturController::class, 'show'])->name('faktur.show');
    Route::delete('/faktur/{id}',             [FakturController::class, 'destroy'])->name('faktur.destroy');
    Route::post('/faktur/{id}/revisi',        [FakturController::class, 'revisi'])->name('faktur.revisi');
    Route::post('/faktur/{id}/approve',       [FakturController::class, 'approve'])->name('faktur.approve');

    // ========== RAB & AHSP ==========
    Route::get('/ahsp/search',                 [AhspController::class, 'search'])->name('ahsp.search');
    Route::post('/ahsp/{ahsp}/duplicate',      [AhspController::class, 'duplicate'])->name('ahsp.duplicate');
    Route::resource('ahsp', AhspController::class);
    Route::resource('hsd-material', HsdMaterialController::class);
    Route::resource('hsd-upah',     HsdUpahController::class);

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
        Route::get('input',               [RabProgressController::class, 'create'])->name('create');
        Route::post('/',                  [RabProgressController::class, 'store'])->name('store');
        Route::get('{progress}',          [RabProgressController::class, 'detail'])->name('detail');
        Route::post('{progress}/finalize',[RabProgressController::class, 'finalize'])->name('finalize');
        Route::delete('{progress}',       [RabProgressController::class, 'destroy'])->name('destroy');
    });

    Route::get('/proyek/{proyek}/penawaran/{penawaran}/pdf-mixed',
        [RabPenawaranController::class, 'generatePdfMixed']
    )->name('proyek.penawaran.pdf-mixed');

    // ========== Akuntansi & Laporan ==========
    Route::get('/jurnal/detail/{id}', [JurnalController::class, 'showDetail'])->name('jurnal.showDetail');
    Route::resource('jurnal', JurnalController::class)->middleware('cek_akses_jurnal');

    Route::get('/buku-besar', [\App\Http\Controllers\BukuBesarController::class, 'index'])->name('buku-besar.index');

    Route::get('/laporan/neraca',    [LaporanController::class, 'neraca'])->name('laporan.neraca');
    Route::get('/laporan/laba-rugi', [LaporanController::class, 'labaRugi'])->name('laporan.labaRugi');

    // ========== Profil Pajak Proyek ==========
    Route::resource('proyek-tax-profiles', ProyekTaxProfileController::class)
        ->parameters(['proyek-tax-profiles' => 'profile'])
        ->only(['index','create','store','edit','update']);
    // Jika ingin mengizinkan hapus, ganti baris di atas dengan:
    // ->only(['index','create','store','edit','update','destroy']);

    // routes/web.php
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
});
