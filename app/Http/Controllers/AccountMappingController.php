<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountMapping;
use App\Models\Coa;
use DB;

class AccountMappingController extends Controller
{
    public function index()
    {
        // Ambil semua mapping yang sudah ada
        $mappings = AccountMapping::all()->pluck('coa_id', 'account_key');
        
        // Daftar account yang perlu di-mapping
        $accountKeys = [
            'hutang_usaha' => 'Hutang Usaha',
            'ppn_masukan' => 'PPN Masukan',
            'kas' => 'Kas/Bank',
            'persediaan_bahan_baku' => 'Persediaan Bahan Baku',
            'beban_bahan_baku' => 'Beban Bahan Baku',
        ];
        
        // Ambil semua COA untuk dropdown
        $coaList = Coa::orderBy('no_akun')->get();
        
        // Build data untuk view
        $data = [];
        foreach ($accountKeys as $key => $label) {
            $currentCoaId = $mappings->get($key);
            $currentCoa = $currentCoaId ? Coa::find($currentCoaId) : null;
            
            $data[$key] = [
                'label' => $label,
                'current_coa_id' => $currentCoaId,
                'current_coa' => $currentCoa,
            ];
        }
        
        return view('account-mapping.index', compact('data', 'coaList'));
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'mappings' => 'required|array',
            'mappings.*' => 'nullable|exists:coa,id',
        ]);
        
        DB::beginTransaction();
        try {
            foreach ($request->mappings as $key => $coaId) {
                if ($coaId) {
                    AccountMapping::setCoa($key, $coaId);
                } else {
                    // Hapus mapping jika tidak dipilih
                    AccountMapping::where('account_key', $key)->delete();
                }
            }
            
            DB::commit();
            return redirect()->route('account-mapping.index')
                           ->with('success', 'Mapping COA berhasil diupdate!');
                           
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Gagal update mapping: ' . $e->getMessage());
        }
    }
}
