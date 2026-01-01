@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Detail Sertifikat Pembayaran</h5>
    <div class="d-flex gap-2">
      @php
        $redir = route('proyek.show', optional(optional($sp->bapp)->proyek)->id ?? 0) . '?tab=sertifikat' . (optional($sp->bapp)->penawaran_id ? ('&penawaran_id=' . optional($sp->bapp)->penawaran_id) : '');
      @endphp
      <a href="{{ route('sertifikat.edit', $sp->id) }}?redirect_to={{ urlencode($redir) }}" class="btn btn-outline-primary">Edit</a>
      <a href="{{ route('sertifikat.create', ['bapp_id' => $sp->bapp_id]) }}?redirect_to={{ urlencode($redir) }}" class="btn btn-outline-warning">Revisi</a>
      @if(($sp->status ?? 'draft') !== 'approved')
        <form method="POST" action="{{ route('sertifikat.approve', $sp->id) }}" onsubmit="return confirm('Setujui sertifikat ini dan buat faktur penjualan?');">
          @csrf
          <input type="hidden" name="redirect_to" value="{{ $redir }}">
          <button class="btn btn-success" type="submit">Setujui</button>
        </form>
      @endif
      @if(($sp->status ?? 'draft') === 'approved')
        <a href="{{ route('sertifikat.cetak', $sp->id) }}" class="btn btn-primary">Cetak PDF</a>
      @endif
    </div>
  </div>
  <div class="card-body">
    @php
      $fmtPct = function ($v) {
        return rtrim(rtrim(number_format($v ?? 0, 2, ',', '.'), '0'), ',');
      };
      $umMode = strtolower($sp->uang_muka_mode ?? 'proporsional');
      $um = $sp->uangMukaPenjualan;
      $sisaAfter = $um ? $um->getSisaUangMuka() : 0;
      $potNow    = (float)($sp->pemotongan_um_nilai ?? 0);
      $sisaBefore= $um ? max(0, round($sisaAfter + $potNow, 2)) : 0;
    @endphp
    <table class="table table-borderless">
      <tr>
        <th style="width:220px">Nomor Sertifikat</th>
        <td>{{ $sp->nomor ?? '-' }}</td>
      </tr>
      <tr>
        <th>Tanggal</th>
        <td>{{ \Carbon\Carbon::parse($sp->tanggal)->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <th>Nomor BAPP</th>
        <td>{{ optional($sp->bapp)->nomor_bapp ?? '-' }}</td>
      </tr>
      <tr>
        <th>Proyek</th>
        <td>{{ optional(optional($sp->bapp)->proyek)->nama_proyek ?? '-' }}</td>
      </tr>
      <tr>
        <th>Termin / Progress</th>
        <td>Ke-{{ $sp->termin_ke }} — Kumulatif {{ rtrim(rtrim(number_format($sp->persen_progress,4,',','.'),'0'),',') }}% — Periode ini {{ rtrim(rtrim(number_format($sp->persen_progress_delta,4,',','.'),'0'),',') }}%</td>
      </tr>
      @php
        $priceMode = strtolower(optional(optional($sp->bapp)->proyek)->penawaran_price_mode ?? 'pisah');
      @endphp
      @if($priceMode !== 'gabung')
      <tr>
        <th>WO Material</th>
        <td>Rp {{ number_format($sp->nilai_wo_material, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>WO Upah</th>
        <td>Rp {{ number_format($sp->nilai_wo_jasa, 2, ',', '.') }}</td>
      </tr>
      @endif
      <tr>
        <th>Nilai WO Total</th>
        <td>Rp {{ number_format($sp->nilai_wo_total, 2, ',', '.') }}</td>
      </tr>

      <tr>
        <th>Uang Muka Kontrak</th>
        <td>{{ $fmtPct($sp->uang_muka_persen) }}% — Rp {{ number_format(($sp->nilai_wo_total * ($sp->uang_muka_persen ?? 0) / 100), 2, ',', '.') }}</td>
      </tr>

      @if($sp->uangMukaPenjualan)
      <tr style="background-color: #f9f9f9;">
        <th>Uang Muka Penjualan</th>
        <td>
          <strong>{{ optional($sp->uangMukaPenjualan)->nomor_bukti ?? '-' }}</strong><br>
          Nominal: Rp {{ number_format(optional($sp->uangMukaPenjualan)->nominal, 2, ',', '.') }}<br>
          Digunakan: Rp {{ number_format(optional($sp->uangMukaPenjualan)->nominal_digunakan, 2, ',', '.') }}<br>
          Sisa setelah potongan ini: Rp {{ number_format($sisaAfter, 2, ',', '.') }}<br>
          Sisa sebelum potongan ini: Rp {{ number_format($sisaBefore, 2, ',', '.') }}<br>
          Status: <span class="badge bg-info">{{ optional($sp->uangMukaPenjualan)->status ?? 'diterima' }}</span>
        </td>
      </tr>
      @endif

      <tr>
        <th>Rule Pemotongan UM</th>
        <td>
          Mode {{ strtoupper($umMode) }} —
          @if($umMode === 'utuh')
            pemotongan penuh atas sisa UM pada sertifikat ini.
          @else
            pemotongan mengikuti persentase progres kumulatif (proporsional).
          @endif
        </td>
      </tr>
      <tr>
        <th>Pemotongan UM %</th>
        <td>{{ $fmtPct($sp->pemotongan_um_persen) }}%</td>
      </tr>
      <tr>
        <th>Pemotongan UM</th>
        <td>Rp {{ number_format($sp->pemotongan_um_nilai, 2, ',', '.') }}</td>
      </tr>
    </table>

    {{-- Breakdown Perhitungan Nilai Tagihan --}}
    <div class="card mt-4" style="background-color: #f8f9fa;">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0">Breakdown Perhitungan Nilai Tagihan (Periode Ini)</h6>
      </div>
      <div class="card-body">
        @php
          // Persen progress periode ini (delta)
          $pctNow = (float)($sp->persen_progress_delta ?? 0);
          
          // Nilai progress periode ini (total)
          $nilaiProgress = (float)($sp->nilai_progress_rp ?? 0);
          
          // Check toggle uang muka & retensi
          $proyek = optional($sp->bapp)->proyek;
          $gunakanUM = (bool)($proyek->gunakan_uang_muka ?? false);
          $gunakanRetensi = (bool)($proyek->gunakan_retensi ?? false);
          $pphDipungut = ($proyek->pph_dipungut ?? 'ya') === 'ya';
          
          // Hitung breakdown progress Material & Jasa periode ini dari nilai_progress_rp
          $woMat = (float)$sp->nilai_wo_material;
          $woJas = (float)$sp->nilai_wo_jasa;
          $woTot = (float)$sp->nilai_wo_total;
          $woTotSafe = $woTot > 0 ? $woTot : 0.0001;
          
          $progressMat = round($nilaiProgress * ($woMat / $woTotSafe), 2);
          $progressJas = round($nilaiProgress * ($woJas / $woTotSafe), 2);
          
          // Potongan periode ini
          $potUM = $gunakanUM ? (float)($sp->pemotongan_um_nilai ?? 0) : 0;
          $potRetensi = $gunakanRetensi ? (float)($sp->retensi_nilai ?? 0) : 0;
          
          // Split UM periode ini sesuai proporsi
          $umTotal = (float)($sp->uang_muka_nilai ?? 0);
          $umPct = $woTotSafe > 0 ? round($umTotal / $woTotSafe * 100, 4) : 0;
          $umMatTotal = round($woMat * $umPct/100, 2);
          $umJasTotal = round($woJas * $umPct/100, 2);
          $ratioM = $umTotal > 0 ? round($umMatTotal / $umTotal, 6) : 0.0;
          $ratioJ = $umTotal > 0 ? round($umJasTotal / $umTotal, 6) : 0.0;
          $potUMMat = round($potUM * $ratioM, 2);
          $potUMJas = round($potUM * $ratioJ, 2);
          
          // Split retensi periode ini
          $retPct = (float)$sp->retensi_persen;
          $potRetensiMat = $gunakanRetensi ? round($progressMat * $retPct/100, 2) : 0;
          $potRetensiJas = $gunakanRetensi ? round($progressJas * $retPct/100, 2) : 0;
          
          // DPP (Dasar Pengenaan Pajak)
          $dppMaterial = (float)($sp->dpp_material ?? 0);
          $dppJasa = (float)($sp->dpp_jasa ?? 0);
          $totalDPP = $dppMaterial + $dppJasa;
          
          // Total dibayar (sebelum PPN)
          $totalDibayar = (float)($sp->total_dibayar ?? 0);
          
          // PPN
          $ppnNilai = (float)($sp->ppn_nilai ?? 0);
          
          // Total tagihan
          $totalTagihan = (float)($sp->total_tagihan ?? 0);
          
          $priceMode = strtolower(optional(optional($sp->bapp)->proyek)->penawaran_price_mode ?? 'pisah');
        @endphp

        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th>Keterangan</th>
              <th width="20%" class="text-end">Nilai (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="text-center">1</td>
              <td><strong>Nilai Progress Periode Ini</strong> ({{ $fmtPct($pctNow) }}%)</td>
              <td class="text-end">{{ number_format($nilaiProgress, 2, ',', '.') }}</td>
            </tr>
            
            @if($priceMode === 'pisah')
            <tr>
              <td></td>
              <td style="padding-left: 30px;">- Progress Material</td>
              <td class="text-end">{{ number_format($progressMat, 2, ',', '.') }}</td>
            </tr>
            <tr>
              <td></td>
              <td style="padding-left: 30px;">- Progress Jasa</td>
              <td class="text-end">{{ number_format($progressJas, 2, ',', '.') }}</td>
            </tr>
            @endif
            
            <tr class="table-secondary">
              <td class="text-center">2</td>
              <td colspan="2"><strong>Pengurangan:</strong></td>
            </tr>
            
            @if($gunakanUM)
            <tr>
              <td></td>
              <td style="padding-left: 30px;">a. Pemotongan Uang Muka</td>
              <td class="text-end text-danger">-{{ number_format($potUM, 2, ',', '.') }}</td>
            </tr>
            
            @if($priceMode === 'pisah')
            <tr>
              <td></td>
              <td style="padding-left: 50px;">· UM Material</td>
              <td class="text-end text-danger">-{{ number_format($potUMMat, 2, ',', '.') }}</td>
            </tr>
            <tr>
              <td></td>
              <td style="padding-left: 50px;">· UM Jasa</td>
              <td class="text-end text-danger">-{{ number_format($potUMJas, 2, ',', '.') }}</td>
            </tr>
            @endif
            @endif
            
            @if($gunakanRetensi)
            <tr>
              <td></td>
              <td style="padding-left: 30px;">{{ $gunakanUM ? 'b' : 'a' }}. Retensi {{ $fmtPct($sp->retensi_persen) }}%</td>
              <td class="text-end text-danger">-{{ number_format($potRetensi, 2, ',', '.') }}</td>
            </tr>
            
            @if($priceMode === 'pisah')
            <tr>
              <td></td>
              <td style="padding-left: 50px;">· Retensi Material</td>
              <td class="text-end text-danger">-{{ number_format($potRetensiMat, 2, ',', '.') }}</td>
            </tr>
            <tr>
              <td></td>
              <td style="padding-left: 50px;">· Retensi Jasa</td>
              <td class="text-end text-danger">-{{ number_format($potRetensiJas, 2, ',', '.') }}</td>
            </tr>
            @endif
            @endif
            
            @if(!$gunakanUM && !$gunakanRetensi)
            <tr>
              <td></td>
              <td style="padding-left: 30px;" class="text-muted">Tidak ada pengurangan</td>
              <td class="text-end">0,00</td>
            </tr>
            @endif
            
            <tr class="table-info">
              <td class="text-center">3</td>
              <td><strong>Nilai Dasar Tagihan / DPP</strong> (1 - 2)</td>
              <td class="text-end"><strong>{{ number_format($totalDPP, 2, ',', '.') }}</strong></td>
            </tr>
            
            @if($priceMode === 'pisah')
            <tr>
              <td></td>
              <td style="padding-left: 30px;">- DPP Material</td>
              <td class="text-end">{{ number_format($dppMaterial, 2, ',', '.') }}</td>
            </tr>
            <tr>
              <td></td>
              <td style="padding-left: 30px;">- DPP Jasa</td>
              <td class="text-end">{{ number_format($dppJasa, 2, ',', '.') }}</td>
            </tr>
            @endif
            
            <tr>
              <td class="text-center">4</td>
              <td><strong>PPN {{ $fmtPct($sp->ppn_persen) }}%</strong></td>
              <td class="text-end">{{ number_format($ppnNilai, 2, ',', '.') }}</td>
            </tr>
            
            <tr class="table-warning">
              <td class="text-center">5</td>
              <td><strong>TOTAL TAGIHAN (3 + 4)</strong></td>
              <td class="text-end"><strong style="font-size: 1.1em;">{{ number_format($totalTagihan, 2, ',', '.') }}</strong></td>
            </tr>
            
            @php
              $proyek = optional($sp->bapp)->proyek;
              $tax = optional($proyek->taxProfileAktif);
              $applyPph = (int)($tax->apply_pph ?? 0) === 1;
              $pphRate = (float)($tax->pph_rate ?? 0);
            @endphp
            
            @if($applyPph && $pphRate > 0 && $pphDipungut)
            <tr>
              <td class="text-center">6</td>
              <td><strong>PPh {{ $fmtPct($pphRate) }}%</strong> (dipotong)</td>
              <td class="text-end text-danger">-{{ number_format(($totalDibayar * $pphRate / 100), 2, ',', '.') }}</td>
            </tr>
            
            <tr class="table-success">
              <td class="text-center">7</td>
              <td><strong>TOTAL DITERIMA (5 - 6)</strong></td>
              <td class="text-end"><strong style="font-size: 1.1em;">{{ number_format($totalTagihan - ($totalDibayar * $pphRate / 100), 2, ',', '.') }}</strong></td>
            </tr>
            @elseif($applyPph && $pphRate > 0 && !$pphDipungut)
            <tr>
              <td class="text-center">6</td>
              <td><strong>PPh {{ $fmtPct($pphRate) }}%</strong> (dibayar sendiri, tidak dipotong)</td>
              <td class="text-end text-muted">{{ number_format(($totalDibayar * $pphRate / 100), 2, ',', '.') }}</td>
            </tr>
            
            <tr class="table-success">
              <td class="text-center">7</td>
              <td><strong>TOTAL DITERIMA</strong> (sama dengan Total Tagihan)</td>
              <td class="text-end"><strong style="font-size: 1.1em;">{{ number_format($totalTagihan, 2, ',', '.') }}</strong></td>
            </tr>
            @endif
          </tbody>
        </table>
        
        @php
          // Hitung total diterima yang sebenarnya untuk terbilang
          $totalDiterima = $totalTagihan;
          if ($applyPph && $pphRate > 0 && $pphDipungut) {
            $totalDiterima = $totalTagihan - ($totalDibayar * $pphRate / 100);
          }
          
          // Fungsi terbilang
          function terbilangRupiah($angka) {
              $angka = abs($angka);
              $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
              if ($angka < 12) return $huruf[$angka];
              if ($angka < 20) return terbilangRupiah($angka - 10) . ' Belas';
              if ($angka < 100) return terbilangRupiah($angka / 10) . ' Puluh ' . terbilangRupiah($angka % 10);
              if ($angka < 200) return 'Seratus ' . terbilangRupiah($angka - 100);
              if ($angka < 1000) return terbilangRupiah($angka / 100) . ' Ratus ' . terbilangRupiah($angka % 100);
              if ($angka < 2000) return 'Seribu ' . terbilangRupiah($angka - 1000);
              if ($angka < 1000000) return terbilangRupiah($angka / 1000) . ' Ribu ' . terbilangRupiah($angka % 1000);
              if ($angka < 1000000000) return terbilangRupiah($angka / 1000000) . ' Juta ' . terbilangRupiah($angka % 1000000);
              if ($angka < 1000000000000) return terbilangRupiah($angka / 1000000000) . ' Milyar ' . terbilangRupiah(fmod($angka, 1000000000));
              return terbilangRupiah($angka / 1000000000000) . ' Triliun ' . terbilangRupiah(fmod($angka, 1000000000000));
          }
          $terbilangShow = trim(terbilangRupiah($totalDiterima));
        @endphp
        
        <div class="alert alert-info mt-3 mb-0">
          <strong>Terbilang:</strong> {{ $terbilangShow }} Rupiah
        </div>
      </div>
    </div>

    <table class="table table-borderless" style="margin-top: 20px;">
      <tr>
        <th style="width:220px">Total Dibayar (sebelum PPN)</th>
        <td>Rp {{ number_format($sp->total_dibayar, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>PPN {{ rtrim(rtrim(number_format($sp->ppn_persen,2,',','.'),'0'),',') }}%</th>
        <td>Rp {{ number_format($sp->ppn_nilai, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>Total Tagihan</th>
        <td><strong>Rp {{ number_format($sp->total_tagihan, 2, ',', '.') }}</strong></td>
      </tr>
    </table>

    <div class="mt-3">
      <a href="{{ $redir }}" class="btn btn-secondary">Kembali</a>
      @if(($sp->status ?? 'draft') === 'approved')
        <a href="{{ route('sertifikat.cetak', $sp->id) }}" class="btn btn-outline-primary">Cetak PDF</a>
      @endif
    </div>
  </div>
</div>
@endsection
