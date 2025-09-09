<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\RABImport;
use App\Models\RabHeader;
use App\Models\RabDetail;
use App\Models\RabKategori;
use App\Models\Proyek;
use Maatwebsite\Excel\Facades\Excel;

class RabController extends Controller
{

    public function input($proyek_id) // Parameter dari rute web.php
    {
        // Ambil data proyek jika diperlukan di tampilan induk
        $proyek = Proyek::findOrFail($proyek_id);

        // Tentukan kategori_id. Ini bisa dari request, nilai default, atau logika lain.
        // Contoh sederhana:
        $kategoris = RabKategori::all();

        // Render tampilan induk yang akan memanggil komponen Livewire
        return view('rab.index', [ // Kita akan membuat file ini selanjutnya
            'proyek_id' => $proyek_id, // Variabel ini akan dilewatkan ke tampilan induk
            'proyek' => $proyek, // Jika Anda ingin menampilkan detail proyek di parent view
            'kategoris' => $kategoris,
        ]);
    }

    public function index($proyek_id)
    {
        $proyek = Proyek::findOrFail($proyek_id);
        $headers = RabHeader::where('proyek_id', $proyek_id)->orderBy('kode_sort')->get();
        $details = RabDetail::where('proyek_id', $proyek_id)->orderBy('kode_sort')->get();
        $kategoris = RabKategori::all();

        return view('rab.index', compact('proyek', 'headers', 'details', 'kategoris'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'proyek_id' => 'required|exists:proyek,id',
        ]);

        RabHeader::where('proyek_id', $request->proyek_id)->delete();
        RabDetail::where('proyek_id', $request->proyek_id)->delete();

        Excel::import(new RABImport($request->proyek_id), $request->file('file'));

        return redirect()->route('proyek.show', $request->proyek_id)->with('success', 'RAB berhasil diimport!');
    }

    public function reset($proyek_id)
    {
        // Hapus detail dulu baru header
        \App\Models\RabDetail::whereIn('rab_header_id', function($q) use ($proyek_id) {
            $q->select('id')->from('rab_header')->where('proyek_id', $proyek_id);
        })->delete();

        \App\Models\RabHeader::where('proyek_id', $proyek_id)->delete();

        return redirect()->back()->with('success', 'Data RAB berhasil direset.');
    }


}
