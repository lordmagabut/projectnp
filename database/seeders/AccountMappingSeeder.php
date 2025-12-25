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
        
        // 1. Hutang Usaha (2101)
        $hutangUsaha = Coa::where('no_akun', '2101')
                         ->orWhere('nama_akun', 'Hutang Usaha')
                         ->first();
        
        if ($hutangUsaha) {
            AccountMapping::setCoa('hutang_usaha', $hutangUsaha->id, 'Seeder');
        }

        // 2. PPN Masukan (1104)
        $ppnMasukan = Coa::where('no_akun', '1104')
                        ->orWhere('nama_akun', 'PPN Masukan')
                        ->first();
        
        if ($ppnMasukan) {
            AccountMapping::setCoa('ppn_masukan', $ppnMasukan->id, 'Seeder');
        }

        // 3. Kas (1101)
        $kas = Coa::where('no_akun', '1101')
                 ->orWhere('nama_akun', 'Kas')
                 ->first();
        
        if ($kas) {
            AccountMapping::setCoa('kas', $kas->id, 'Seeder');
        }

        // 4. Persediaan Bahan Baku (1102)
        $persediaanBahanBaku = Coa::where('no_akun', '1102')
                                  ->orWhere('nama_akun', 'Persediaan Bahan Baku')
                                  ->first();
        
        if ($persediaanBahanBaku) {
            AccountMapping::setCoa('persediaan_bahan_baku', $persediaanBahanBaku->id, 'Seeder');
        }

        // 5. Beban Bahan Baku (5101)
        $bebanBahanBaku = Coa::where('no_akun', '5101')
                             ->orWhere('nama_akun', 'Beban Bahan Baku')
                             ->first();
        
        if ($bebanBahanBaku) {
            AccountMapping::setCoa('beban_bahan_baku', $bebanBahanBaku->id, 'Seeder');
        }

        $this->command->info('Default account mappings seeded successfully!');
    }
}
