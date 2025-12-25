<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountMapping;
use App\Models\Coa;

class AccountMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Cari COA berdasarkan nomor akun atau nama yang sudah di-seed
        
        // 1. Hutang Usaha (2-110)
        $hutangUsaha = Coa::where('no_akun', '2-110')
                         ->orWhere('nama_akun', 'Hutang Usaha')
                         ->first();
        
        if ($hutangUsaha) {
            AccountMapping::setCoa('hutang_usaha', $hutangUsaha->id, 'Seeder');
        }

        // 2. PPN Masukan (1-170)
        $ppnMasukan = Coa::where('no_akun', '1-170')
                        ->orWhere('nama_akun', 'PPN Masukan')
                        ->first();
        
        if ($ppnMasukan) {
            AccountMapping::setCoa('ppn_masukan', $ppnMasukan->id, 'Seeder');
        }

        // 3. Kas & Setara Kas (1-110) atau Bank (1-120)
        $kas = Coa::where('no_akun', '1-120')
                 ->orWhere('nama_akun', 'Bank')
                 ->orWhere('no_akun', '1-110')
                 ->orWhere('nama_akun', 'Kas & Setara Kas')
                 ->first();
        
        if ($kas) {
            AccountMapping::setCoa('kas', $kas->id, 'Seeder');
        }

        // 4. Persediaan Material Proyek (1-160) – sebagai pengganti Persediaan Bahan Baku
        $persediaanBahanBaku = Coa::where('no_akun', '1-160')
                                  ->orWhere('nama_akun', 'Persediaan Material Proyek')
                                  ->first();
        
        if ($persediaanBahanBaku) {
            AccountMapping::setCoa('persediaan_bahan_baku', $persediaanBahanBaku->id, 'Seeder');
        }

        // 5. HPP Proyek - Material (5-110) – sebagai pengganti Beban Bahan Baku
        $bebanBahanBaku = Coa::where('no_akun', '5-110')
                             ->orWhere('nama_akun', 'HPP Proyek - Material')
                             ->first();
        
        if ($bebanBahanBaku) {
            AccountMapping::setCoa('beban_bahan_baku', $bebanBahanBaku->id, 'Seeder');
        }

        // 6. Uang Muka ke Vendor (1-150) – untuk Advance Payment Pembelian
        $uangMukaVendor = Coa::where('no_akun', '1-150')
                             ->orWhere('nama_akun', 'Uang Muka ke Vendor')
                             ->first();
        
        if ($uangMukaVendor) {
            AccountMapping::setCoa('uang_muka_vendor', $uangMukaVendor->id, 'Seeder');
        }

        // 7. Bank untuk pembayaran UM (1-120)
        $bank = Coa::where('no_akun', '1-120')
                   ->orWhere('nama_akun', 'Bank')
                   ->first();
        
        if ($bank) {
            AccountMapping::setCoa('kas_bank', $bank->id, 'Seeder');
        }

        $this->command->info('Default account mappings seeded successfully!');
    }
}
