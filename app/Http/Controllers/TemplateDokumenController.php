<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Storage;

class TemplateDokumenController extends Controller
{
    public function index()
    {
        $perusahaans = Perusahaan::all();
        return view('template_dokumen.index', compact('perusahaans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'perusahaan_id' => 'required|exists:perusahaan,id',
            'template_po' => 'nullable|file|mimes:doc,docx|max:20480',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
        ]);

        $perusahaan = Perusahaan::findOrFail($request->perusahaan_id);

        // Handle template PO upload (optional)
        if ($request->hasFile('template_po')) {
            if ($perusahaan->template_po && Storage::disk('public')->exists($perusahaan->template_po)) {
                Storage::disk('public')->delete($perusahaan->template_po);
            }

            $file = $request->file('template_po');
            $filename = time() . '_templatePO_' . strtoupper(str_replace(' ', '_', $perusahaan->nama_perusahaan)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('template_po', $filename, 'public');
            $perusahaan->template_po = $filePath;
        }

        // Handle logo upload (optional)
        if ($request->hasFile('logo')) {
            if ($perusahaan->logo_path && Storage::disk('public')->exists($perusahaan->logo_path)) {
                Storage::disk('public')->delete($perusahaan->logo_path);
            }

            $logo = $request->file('logo');
            $logoName = time() . '_logo_' . strtoupper(str_replace(' ', '_', $perusahaan->nama_perusahaan)) . '.' . $logo->getClientOriginalExtension();
            $logoPath = $logo->storeAs('perusahaan_logo', $logoName, 'public');
            $perusahaan->logo_path = $logoPath;
        }

        $perusahaan->save();

        return redirect()->route('template-dokumen.index')->with('success', 'Perubahan template dan/atau logo berhasil disimpan.');
    }
}
