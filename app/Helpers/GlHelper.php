<?php

use App\Models\GlTransaksi;

if (!function_exists('syncToGL')) {
    function syncToGL($jurnal, $tipe_transaksi, $id_transaksi)
    {
        foreach ($jurnal->jurnalDetails as $detail) {
            GlTransaksi::create([
                'tanggal' => $jurnal->tanggal,
                'jurnal_id' => $jurnal->id,
                'tipe_transaksi' => $tipe_transaksi,
                'id_transaksi' => $id_transaksi,
                'coa_id' => $detail->coa_id,
                'debit' => $detail->debit,
                'kredit' => $detail->kredit,
                'keterangan' => $jurnal->keterangan,
                'id_perusahaan' => $jurnal->id_perusahaan,
            ]);
        }
    }
}
