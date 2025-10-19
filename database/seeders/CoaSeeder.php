<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Coa;

class CoaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ========= ROOT =========
            $this->root('1-000', 'ASET',        'Aset');
            $this->root('2-000', 'LIABILITAS',  'Liabilitas');
            $this->root('3-000', 'EKUITAS',     'Ekuitas');
            $this->root('4-000', 'PENDAPATAN',  'Pendapatan');
            $this->root('5-000', 'HPP',         'HPP');
            $this->root('6-000', 'BEBAN',       'Beban');

            // ========= ASET =========
            $this->add('1-100', 'Aset Lancar',                        'Aset',       '1-000');
            $this->add('1-110', 'Kas & Setara Kas',                   'Aset',       '1-100');
            $this->add('1-111', 'Kas Kecil',                          'Aset',       '1-110');
            $this->add('1-120', 'Bank',                               'Aset',       '1-110');

            $this->add('1-130', 'Piutang Usaha',                      'Aset',       '1-100');
            $this->add('1-131', 'Piutang Usaha Proyek',               'Aset',       '1-130');
            $this->add('1-140', 'Piutang Retensi',                    'Aset',       '1-130');
            $this->add('1-141', 'Piutang Retensi Pelanggan',          'Aset',       '1-140'); // granular opsional

            $this->add('1-150', 'Uang Muka ke Vendor',                'Aset',       '1-100');
            $this->add('1-151', 'Uang Muka ke Subkon',                'Aset',       '1-150');

            $this->add('1-160', 'Persediaan Material Proyek',         'Aset',       '1-100'); // opsional
            $this->add('1-170', 'PPN Masukan',                        'Aset',       '1-100');

            $this->add('1-180', 'Konstruksi dalam Pengerjaan (WIP)',  'Aset',       '1-100');

            $this->add('1-200', 'Aset Tidak Lancar',                  'Aset',       '1-000');
            $this->add('1-210', 'Aset Tetap',                         'Aset',       '1-200');
            $this->add('1-211', 'Akumulasi Penyusutan',               'Aset',       '1-210');

            // ========= LIABILITAS =========
            $this->add('2-100', 'Liabilitas Jangka Pendek',           'Liabilitas', '2-000');
            $this->add('2-110', 'Hutang Usaha',                       'Liabilitas', '2-100');
            $this->add('2-120', 'Hutang Retensi',                     'Liabilitas', '2-100');
            $this->add('2-121', 'Hutang Retensi Subkon',              'Liabilitas', '2-120'); // granular opsional

            $this->add('2-130', 'Uang Muka Pelanggan (DP Owner)',     'Liabilitas', '2-100');
            $this->add('2-140', 'PPN Keluaran',                       'Liabilitas', '2-100');
            $this->add('2-150', 'Progress Billing / Pendapatan Diterima Dimuka', 'Liabilitas', '2-100');

            // Pajak penghasilan (umum di proyek)
            $this->add('2-160', 'Utang PPh 23',                       'Liabilitas', '2-100');
            $this->add('2-161', 'Utang PPh 4(2)',                     'Liabilitas', '2-100');
            $this->add('2-162', 'Utang PPh 21',                       'Liabilitas', '2-100');

            $this->add('2-200', 'Liabilitas Jangka Panjang',          'Liabilitas', '2-000');
            $this->add('2-210', 'Pinjaman Bank Jangka Panjang',       'Liabilitas', '2-200');

            // ========= EKUITAS =========
            $this->add('3-100', 'Modal Disetor',                      'Ekuitas',    '3-000');
            $this->add('3-200', 'Laba Ditahan',                       'Ekuitas',    '3-000');
            $this->add('3-300', 'Prive / Dividen',                    'Ekuitas',    '3-000');

            // ========= PENDAPATAN =========
            $this->add('4-100', 'Pendapatan Proyek',                  'Pendapatan', '4-000');
            $this->add('4-900', 'Pendapatan Lain-lain',               'Pendapatan', '4-000');

            // ========= HPP (COGS) =========
            $this->add('5-100', 'HPP Proyek',                         'HPP',        '5-000');
            $this->add('5-110', 'HPP Proyek - Material',              'HPP',        '5-100');
            $this->add('5-120', 'HPP Proyek - Jasa/Subkon',           'HPP',        '5-100');

            // ========= BEBAN OPERASIONAL =========
            $this->add('6-100', 'Beban Operasional',                  'Beban',      '6-000');
            $this->add('6-110', 'Beban Gaji & Tunjangan',             'Beban',      '6-100');
            $this->add('6-120', 'Beban Transportasi',                 'Beban',      '6-100');
            $this->add('6-130', 'Beban Sewa',                         'Beban',      '6-100');
            $this->add('6-140', 'Beban Utilitas',                     'Beban',      '6-100');
            $this->add('6-150', 'Beban Administrasi Umum',            'Beban',      '6-100');
        });
    }

    // ===== Helpers ==========================================================

    private function root(string $no, string $nama, string $tipe): Coa
    {
        $node = Coa::where('no_akun', $no)->first();

        if (!$node) {
            $node = new Coa([
                'no_akun'   => $no,
                'nama_akun' => $nama,
                'tipe'      => $tipe,
                'suspended' => 0,
            ]);
            $node->saveAsRoot();
            return $node;
        }

        // Pastikan jadi root & info terbaru
        $node->fill([
            'nama_akun' => $nama,
            'tipe'      => $tipe,
            'suspended' => $node->suspended ?? 0,
        ]);

        if (!$node->isRoot()) {
            $node->saveAsRoot();
        } else {
            $node->save();
        }

        return $node;
    }

    private function add(string $no, string $nama, string $tipe, string $parentNo): Coa
    {
        $parent = Coa::where('no_akun', $parentNo)->firstOrFail();

        $node = Coa::where('no_akun', $no)->first();
        if (!$node) {
            $node = new Coa([
                'no_akun'   => $no,
                'nama_akun' => $nama,
                'tipe'      => $tipe,
                'suspended' => 0,
            ]);
            $node->appendToNode($parent)->save();
            return $node;
        }

        // Update + relokasi jika parent berubah
        $node->fill([
            'nama_akun' => $nama,
            'tipe'      => $tipe,
            'suspended' => $node->suspended ?? 0,
        ]);

        if (!$node->isDescendantOf($parent)) {
            $node->appendToNode($parent)->save();
        } else {
            $node->save();
        }

        return $node;
    }
}
