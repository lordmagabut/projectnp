<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAST - {{ $bast->nomor ?? 'N/A' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 15mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 100%;
            background: white;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 12pt;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .header p {
            font-size: 10pt;
            font-weight: bold;
            margin: 3px 0 0 0;
        }

        /* Info Grid */
        .info-table {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: top;
            width: 50%;
            font-size: 9pt;
        }
        .label {
            display: inline-block;
            width: 65px;
            font-weight: bold;
        }

        /* Content */
        .content {
            text-align: justify;
            font-size: 9pt;
        }
        .section-party {
            margin: 8px 0;
        }
        .party-details {
            margin-left: 15px;
            margin-bottom: 8px;
            width: 100%;
        }
        .party-details td {
            padding: 0;
            font-size: 9pt;
        }

        /* List Styling */
        .list-container {
            margin: 8px 0 8px 15px;
        }
        .list-item {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .list-char {
            display: table-cell;
            width: 18px;
            font-weight: bold;
        }
        .list-text {
            display: table-cell;
            font-size: 9pt;
        }

        /* Table Retensi */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .table-data th {
            background-color: #f2f2f2;
            text-align: center;
            border: 1px solid #000;
            padding: 4px;
            font-size: 9pt;
        }
        .table-data td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 9pt;
        }
        .text-right { text-align: right; }

        /* Signature Section */
        .signature-wrapper {
            margin-top: 15px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            border: none;
            font-size: 9pt;
        }
        .signature-space {
            height: 45px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Helper */
        .bold { font-weight: bold; }
        .mt-10 { margin-top: 10px; }
        p { margin: 0 0 5px 0; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h2>BERITA ACARA</h2>
            <p>
                {{ match($bast->jenis_bast) {
                    'bast_1' => 'SERAH TERIMA I / PERTAMA',
                    'bast_2' => 'SERAH TERIMA II / KEDUA',
                    default => strtoupper($bast->jenis_bast ?? 'SERAH TERIMA PEKERJAAN')
                } }}
            </p>
        </div>

        <table class="info-table">
            <tr>
                <td>
                    <span class="label">Nomor</span>: {{ $bast->nomor ?? '-' }}
                </td>
                <td>
                    <span class="label">Proyek</span>: {{ $bast->sertifikatPembayaran?->penawaran?->nama_penawaran ?? '-' }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Tanggal</span>: {{ optional($bast->tanggal_bast)->format('d F Y') ?? '-' }}
                </td>
                <td>
                    <span class="label">SPK No.</span>: {{ $bast->proyek?->no_spk ?? '-' }}
                </td>
            </tr>
        </table>

        <div class="content">
            <p>Pada hari ini tanggal <span class="bold">{{ optional($bast->tanggal_bast)->translatedFormat('d F Y') ?? '—' }}</span>, yang bertanda tangan di bawah ini:</p>

            <div class="section-party">
                <table class="party-details">
                    <tr>
                        <td width="100" class="bold">I. Nama</td>
                        <td>: {{ $bast->sertifikatPembayaran?->pemberi_tugas_nama ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bold">Jabatan</td>
                        <td>: {{ $bast->sertifikatPembayaran?->pemberi_tugas_jabatan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bold">Perusahaan</td>
                        <td>: {{ $bast->sertifikatPembayaran?->pemberi_tugas_perusahaan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 3px;">Selanjutnya disebut <span class="bold">PIHAK PERTAMA</span></td>
                    </tr>
                </table>

                <table class="party-details">
                    <tr>
                        <td width="100" class="bold">II. Nama</td>
                        <td>: {{ $bast->sertifikatPembayaran?->penerima_tugas_nama ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bold">Jabatan</td>
                        <td>: {{ $bast->sertifikatPembayaran?->penerima_tugas_jabatan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bold">Perusahaan</td>
                        <td>: {{ $bast->sertifikatPembayaran?->penerima_tugas_perusahaan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 3px;">Selanjutnya disebut <span class="bold">PIHAK KEDUA</span></td>
                    </tr>
                </table>
            </div>

            <p>Berdasarkan referensi sebagai berikut:</p>
            <div class="list-container">
                <div class="list-item">
                    <div class="list-char">1.</div>
                    <div class="list-text">Work Order / SPK No. {{ $bast->proyek?->no_spk ?? '—' }}</div>
                </div>
                @if($bast->jenis_bast === 'bast_1')
                    <div class="list-item">
                        <div class="list-char">2.</div>
                        <div class="list-text">Berita Acara Progress Pekerjaan 100%, Nomor {{ $bast->sertifikatPembayaran?->bapp?->nomor_bapp ?? '—' }}, tanggal {{ optional($bast->sertifikatPembayaran?->bapp?->tanggal_bapp)->format('d F Y') ?? '—' }}</div>
                    </div>
                @else
                    @php
                        $bast1 = $bast->parent; // BAST 1 (parent dari BAST 2)
                    @endphp
                    <div class="list-item">
                        <div class="list-char">2.</div>
                        <div class="list-text">Berita Acara Serah Terima I (Pertama), Nomor {{ $bast1?->nomor ?? '—' }}, tanggal {{ optional($bast1?->tanggal_bast)->format('d F Y') ?? '—' }}</div>
                    </div>
                @endif
            </div>

            <p>Dengan ini menyatakan setuju dan sepakat melakukan Serah Terima Pekerjaan dengan ketentuan:</p>

            @php
                $jenis = $bast->jenis_bast ?? 'bast_1';
                $ketentuanKey = $jenis === 'bast_2' ? 'bast_2' : 'bast_1';
                
                $defaultKetentuan = [
                    'bast_1' => [
                        'a' => 'PIHAK KEDUA menyerahkan hasil pekerjaan kepada PIHAK PERTAMA dan PIHAK PERTAMA menyatakan menerima hasil pekerjaan tersebut dengan baik dan lengkap.',
                        'b' => 'Sebagai kelengkapan dari Berita Acara Serah Terima Pertama dilampirkan Berita Acara Penyelesaian Pekerjaan yang telah disetujui oleh PIHAK PERTAMA.',
                        'c' => 'Sesuai dengan Surat Kontrak, maka PIHAK KEDUA setelah Serah Terima Pekerjaan ini, PIHAK KEDUA tetap bertanggung jawab atas segala cacat Pekerjaan yang ada di RAB selama masa pemeliharaan yaitu 90 Hari terhitung sejak tanggal Serah Terima Pekerjaan, dan masa garansi pekerjaan 12 bulan sejak tanggal Serah Terima Pekerjaan.',
                    ],
                    'bast_2' => [
                        'a' => 'PIHAK PERTAMA telah menerima hasil pekerjaan dari PIHAK KEDUA sesuai BAST 1 dan menyatakan pekerjaan telah selesai dengan baik.',
                        'b' => 'Masa pemeliharaan telah berakhir dan PIHAK PERTAMA menyatakan tidak ada cacat atau kerusakan yang perlu diperbaiki.',
                        'c' => 'Dengan selesainya masa pemeliharaan, maka retensi yang ditahan akan dikembalikan kepada PIHAK KEDUA sesuai ketentuan yang berlaku.',
                    ],
                ];

                $ketentuan = $bast->ketentuan[$ketentuanKey] ?? $defaultKetentuan[$ketentuanKey];
            @endphp

            <div class="list-container">
                <div class="list-item">
                    <div class="list-char">a.</div>
                    <div class="list-text">{{ $ketentuan['a'] }}</div>
                </div>
                <div class="list-item">
                    <div class="list-char">b.</div>
                    <div class="list-text">{{ $ketentuan['b'] }}</div>
                </div>
                <div class="list-item">
                    <div class="list-char">c.</div>
                    <div class="list-text">{{ $ketentuan['c'] }}</div>
                </div>
            </div>

            @if($bast->jenis_bast === 'bast_2')
                <div style="margin-top: 10px;">
                    <p class="bold" style="margin-bottom: 3px;">Rincian Retensi:</p>
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th width="35%">Nilai / Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Persentase Retensi</td>
                                <td class="text-right">{{ $bast->persen_retensi !== null ? number_format((float)$bast->persen_retensi, 2, ',', '.') . '%' : '-' }}</td>
                            </tr>
                            <tr>
                                <td>Nilai Retensi Akumulasi</td>
                                <td class="text-right">
                                    @php
                                        $penawaranId = optional($bast->sertifikatPembayaran)->penawaran_id;
                                        $totalRetensi = 0;
                                        if ($penawaranId) {
                                            $totalRetensi = \DB::table('sertifikat_pembayaran')
                                                ->where('penawaran_id', $penawaranId)
                                                ->sum('retensi_nilai');
                                        }
                                    @endphp
                                    Rp {{ number_format($totalRetensi, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td>Durasi & Jatuh Tempo</td>
                                <td class="text-right">
                                    {{ $bast->durasi_retensi_hari ?? '-' }} Hari 
                                    ({{ optional($bast->tanggal_jatuh_tempo_retensi)->format('d/m/Y') ?? '-' }})
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <p class="mt-10">Demikian Berita Acara Serah Terima ini dibuat dalam rangkap 2 (dua) untuk dipergunakan sebagaimana mestinya.</p>

        <div class="signature-wrapper">
            <table class="signature-table">
                <tr>
                    <td>
                        <p style="margin-bottom: 3px;">PIHAK PERTAMA<br>Pemberi Tugas</p>
                        <div class="signature-space"></div>
                        <p class="signature-name">{{ $bast->sertifikatPembayaran?->pemberi_tugas_nama ?? '..........................' }}</p>
                        <p style="font-size: 8pt; margin-top: 0;">{{ $bast->sertifikatPembayaran?->pemberi_tugas_perusahaan ?? '' }}</p>
                    </td>
                    <td>
                        <p style="margin-bottom: 3px;">PIHAK KEDUA<br>Penerima Tugas</p>
                        <div class="signature-space"></div>
                        <p class="signature-name">{{ $bast->sertifikatPembayaran?->penerima_tugas_nama ?? '..........................' }}</p>
                        <p style="font-size: 8pt; margin-top: 0;">{{ $bast->sertifikatPembayaran?->penerima_tugas_perusahaan ?? '' }}</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>