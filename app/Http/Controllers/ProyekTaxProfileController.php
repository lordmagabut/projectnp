<?php
// app/Http/Controllers/ProyekTaxProfileController.php

namespace App\Http\Controllers;


use App\Models\Proyek;
use App\Models\ProyekTaxProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class ProyekTaxProfileController extends Controller
{
// (Opsional) aktifkan bila butuh proteksi halaman
// public function __construct()
// {
// $this->middleware(['auth']);
// // $this->middleware(['permission:kelola profil pajak'])->only(['create','store','edit','update','destroy']);
// }


/**
* Tampilkan daftar profil pajak (paginate).
*/
public function index()
{
$profiles = ProyekTaxProfile::with('proyek')
->orderByDesc('id')
->paginate(15);


return view('proyek_tax_profiles.index', compact('profiles'));
}


/**
* Form tambah profil pajak.
*/
public function create()
{
$proyekList = Proyek::orderBy('nama_proyek')->get(['id','nama_proyek']);
return view('proyek_tax_profiles.create', compact('proyekList'));
}


/**
* Validasi umum (store/update).
*/
protected function rules(): array
{
return [
'proyek_id' => ['required','integer','exists:proyek,id'],
'is_taxable' => ['nullable','boolean'],
'ppn_mode' => ['required', Rule::in(['include','exclude'])],
'ppn_rate' => ['required','numeric','min:0'],
'apply_pph' => ['nullable','boolean'],
'pph_rate' => ['required','numeric','min:0'],
'pph_base' => ['required', Rule::in(['dpp','subtotal'])],
'rounding' => ['required', Rule::in(['HALF_UP','FLOOR','CEIL'])],
'effective_from' => ['nullable','date'],
'effective_to' => ['nullable','date','after_or_equal:effective_from'],
'aktif' => ['required','boolean'],
'extra_options' => ['nullable','string'], // JSON string opsional
];
}


/**
* Decode JSON extra_options menjadi array (null bila kosong / invalid).
*/
private function parseExtraOptions(?string $json): ?array
{
if ($json === null || trim($json) === '') return null;
try {
$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
return is_array($decoded) ? $decoded : null;
} catch (\Throwable $e) {
return null;
}
}


/**
* Normalisasi field pajak agar konsisten saat disimpan.
*/
private function normalize(array $data): array
{
$data['is_taxable'] = (bool)($data['is_taxable'] ?? false);
$data['apply_pph'] = (bool)($data['apply_pph'] ?? false);
$data['aktif'] = (bool)($data['aktif'] ?? false);


if (!$data['is_taxable']) {
// Jika tidak kena PPN, paksa mode exclude & tarif 0 supaya bersih
$data['ppn_mode'] = 'exclude';
$data['ppn_rate'] = 0;
}
if (!$data['apply_pph']) {
}
}}