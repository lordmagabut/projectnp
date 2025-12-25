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
        // Ambil semua mapping yang sudah ada (keyed by 'key') beserta COA
        $mappings = AccountMapping::with('coa')->get()->keyBy('key');
        
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
            $mapping = $mappings->get($key);
            $currentCoaId = $mapping?->coa_id;
            $currentCoa = $mapping?->coa;
            $data[$key] = [
                'label' => $label,
                'current_coa_id' => $currentCoaId,
                'current_coa' => $currentCoa,
                'source' => $mapping?->description,
                'updated_at' => $mapping?->updated_at,
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
                    // Tandai sumber sebagai Manual
                    AccountMapping::setCoa($key, (int)$coaId, 'Manual');
                } else {
                    // Hapus mapping jika tidak dipilih
                    AccountMapping::where('key', $key)->delete();
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
