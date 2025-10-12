<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A4 portrait; margin: 18mm 16mm 18mm 16mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color:#333; }
    h2 { text-align:center; margin:0 0 6px; font-size:18px; font-weight:700; }
    h3 { text-align:center; margin:0 0 14px; font-size:13px; font-weight:600; }
    .meta { width:100%; margin-bottom:10px; }
    .meta td { vertical-align:top; padding:2px 0; }
    .bold { font-weight:700; }
    .table { width:100%; border-collapse:collapse; margin:10px 0 14px; }
    .table th, .table td { border:1px solid #444; padding:6px 8px; }
    .table th { background:#eee; }
    .right { text-align:right; }
    .center { text-align:center; }
    .no-border td { border:none; padding:2px 0; }
    .sign { width:100%; margin-top:24px; }
    .sign td { width:50%; vertical-align:top; }
    .small { font-size:10px; color:#555; }
  </style>
</head>
<body>
  @php
    $fmt = fn($n)=> number_format((float)$n, 0, ',', '.');
    $bapp = $sp->bapp;
    $proyekNama = optional($bapp->proyek)->nama_proyek ?? '';
  @endphp

  <h2>SERTIFIKAT PEMBAYARAN</h2>
  <h3>{{ strtoupper($proyekNama ?: 'PENGADAAN / PEKERJAAN') }}</h3>

  <table class="meta no-border">
    <tr>
      <td class="bold" style="width:18%">Nomor</td><td style="width:2%">:</td><td>{{ $sp->nomor }}</td>
      <td class="bold" style="width:16%">Tanggal</td><td style="width:2%">:</td><td>{{ \Carbon\Carbon::parse($sp->tanggal)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td class="bold">BAPP</td><td>:</td><td>#{{ $sp->bapp_id }} (Approved)</td>
      <td class="bold">Termin</td><td>:</td><td>Progress Ke-{{ $sp->termin_ke }} — {{ rtrim(rtrim(number_format($sp->persen_progress,4,',','.'),'0'),',') }}%</td>
    </tr>
  </table>

  <table class="table">
    <tr><th colspan="4" class="center">RINCIAN</th></tr>
    <tr>
      <td style="width:55%">1. Nilai WO Material / Upah / Total</td>
      <td class="right" style="width:15%">Rp. {{ $fmt($sp->nilai_wo_material) }}</td>
      <td class="right" style="width:15%">Rp. {{ $fmt($sp->nilai_wo_jasa) }}</td>
      <td class="right" style="width:15%">Rp. {{ $fmt($sp->nilai_wo_total) }}</td>
    </tr>
    <tr>
      <td>2. Uang Muka: {{ rtrim(rtrim(number_format($sp->uang_muka_persen,2,',','.'),'0'),',') }} % dari Nilai WO<br>
          &nbsp;&nbsp;&nbsp;Pemotongan Uang Muka Progress ke-{{ $sp->termin_ke }}
          {{ rtrim(rtrim(number_format($sp->pemotongan_um_persen,2,',','.'),'0'),',') }} % dari Nilai Uang Muka</td>
      <td class="right">Rp. {{ $fmt($sp->uang_muka_nilai) }}</td>
      <td class="right">Rp. {{ $fmt($sp->pemotongan_um_nilai) }} (-)</td>
      <td class="right">Rp. {{ $fmt($sp->sisa_uang_muka) }}</td>
    </tr>
    <tr>
      <td>3. Nilai Progress ({{ rtrim(rtrim(number_format($sp->persen_progress,4,',','.'),'0'),',') }} % × Nilai WO)</td>
      <td></td><td></td>
      <td class="right">Rp. {{ $fmt($sp->nilai_progress_rp) }}</td>
    </tr>
    <tr>
      <td>4. Retensi {{ rtrim(rtrim(number_format($sp->retensi_persen,2,',','.'),'0'),',') }} % dari Nilai Progress</td>
      <td></td><td></td>
      <td class="right">Rp. {{ $fmt($sp->retensi_nilai) }}</td>
    </tr>
    <tr>
      <td>5. Nilai Progress Saat Ini:</td>
      <td colspan="3">
        <table class="no-border" style="width:100%">
          <tr>
            <td style="width:65%">- Progress Ke-{{ $sp->termin_ke }}</td>
            <td class="right" style="width:35%">Rp. {{ $fmt($sp->nilai_progress_rp) }}</td>
          </tr>
          <tr>
            <td>- Pemotongan Uang Muka ({{ rtrim(rtrim(number_format($sp->pemotongan_um_persen,2,',','.'),'0'),',') }} %)</td>
            <td class="right">Rp. {{ $fmt($sp->pemotongan_um_nilai) }}</td>
          </tr>
          <tr>
            <td>- Retensi ({{ rtrim(rtrim(number_format($sp->retensi_persen,2,',','.'),'0'),',') }} %)</td>
            <td class="right">Rp. {{ $fmt($sp->retensi_nilai) }} (-)</td>
          </tr>
          <tr>
            <td class="bold">Total yang dibayar</td>
            <td class="right bold">Rp. {{ $fmt($sp->total_dibayar) }}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>6. Tagihan Saat Ini:</td>
      <td colspan="3">
        <table class="no-border" style="width:100%">
          <tr>
            <td style="width:65%">- Nilai Progress Saat Ini</td>
            <td class="right" style="width:35%">Rp. {{ $fmt($sp->total_dibayar) }}</td>
          </tr>
          <tr>
            <td>- PPN {{ rtrim(rtrim(number_format($sp->ppn_persen,2,',','.'),'0'),',') }} %</td>
            <td class="right">Rp. {{ $fmt($sp->ppn_nilai) }} (+)</td>
          </tr>
          <tr>
            <td>- DPP Material (acuan PPh)</td>
            <td class="right">Rp. {{ $fmt($sp->dpp_material) }}</td>
          </tr>
          <tr>
            <td>- DPP Jasa (acuan PPh)</td>
            <td class="right">Rp. {{ $fmt($sp->dpp_jasa) }}</td>
          </tr>
          <tr>
            <td class="bold">Total Tagihan Saat Ini</td>
            <td class="right bold">Rp. {{ $fmt($sp->total_tagihan) }}</td>
          </tr>
        </table>
      </td>
    </tr>


  </table>

  <p><span class="bold">Terbilang:</span> {{ $sp->terbilang }}</p>

  <table class="sign">
    <tr>
      <td class="center">
        <div class="bold">Dibuat Oleh,</div>
        <div>{{ $sp->pemberi_tugas_perusahaan ?? 'Pihak Pertama' }}</div>
        <br><br><br>
        <div class="bold">{{ $sp->pemberi_tugas_nama ?? '' }}</div>
        <div>{{ $sp->pemberi_tugas_jabatan ?? '' }}</div>
      </td>
      <td class="center">
        <div class="bold">Diterima Oleh,</div>
        <div>{{ $sp->penerima_tugas_perusahaan ?? 'Pihak Kedua' }}</div>
        <br><br><br>
        <div class="bold">{{ $sp->penerima_tugas_nama ?? '' }}</div>
        <div>{{ $sp->penerima_tugas_jabatan ?? '' }}</div>
      </td>
    </tr>
  </table>

  <p class="small" style="margin-top:10px">
    Catatan: Angka disimpan sebagai snapshot saat sertifikat diterbitkan.
  </p>
</body>
</html>
