<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - {{ $kwitansi['nomor_kwitansi'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .kwitansi-container {
            width: 180mm;
            height: auto;
            max-height: 250mm;
            margin: 0 auto;
            padding: 10mm 15mm;
            position: relative;
            background: #fff;
            border: 2px solid #333;
            page-break-inside: avoid;
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            z-index: 0;
            pointer-events: none;
        }
        
        .watermark.center {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .watermark.top-left {
            top: 10mm;
            left: 15mm;
        }
        
        .watermark.top-right {
            top: 10mm;
            right: 15mm;
        }
        
        .watermark.bottom-left {
            bottom: 10mm;
            left: 15mm;
        }
        
        .watermark.bottom-right {
            bottom: 10mm;
            right: 15mm;
        }
        
        .watermark img {
            max-width: 100%;
            height: auto;
        }
        
        .content {
            position: relative;
            z-index: 1;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 3px double #333;
        }
        
        .header .logo {
            max-height: 50px;
            margin-bottom: 8px;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .header .alamat {
            font-size: 9pt;
            color: #666;
            line-height: 1.3;
        }
        
        /* Judul Kwitansi */
        .judul-kwitansi {
            text-align: center;
            margin: 12px 0;
        }
        
        .judul-kwitansi h2 {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        
        .judul-kwitansi .nomor {
            font-size: 10pt;
            margin-top: 3px;
        }
        
        /* Detail */
        .detail {
            margin: 12px 0;
        }
        
        .detail table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .detail td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 10pt;
        }
        
        .detail td:first-child {
            width: 35%;
        }
        
        .detail td:nth-child(2) {
            width: 5%;
        }
        
        .detail .nominal {
            font-size: 12pt;
            font-weight: bold;
        }
        
        .detail .terbilang {
            font-style: italic;
            font-size: 9pt;
            color: #666;
        }
        
        /* Footer */
        .footer-text {
            text-align: center;
            font-size: 9pt;
            color: #666;
            margin: 12px 0;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        /* Tanda Tangan */
        .ttd-section {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }
        
        .ttd-box {
            text-align: center;
            width: 180px;
        }
        
        .ttd-box .tanggal {
            margin-bottom: 5px;
            font-size: 10pt;
        }
        
        .ttd-box .space {
            height: 50px;
            position: relative;
        }
        
        .ttd-box .stempel {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            max-height: 45px;
            opacity: 0.8;
        }
        
        .ttd-box .nama {
            font-weight: bold;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
            font-size: 10pt;
        }
        
        .ttd-box .jabatan {
            font-size: 9pt;
            color: #666;
        }
        
        /* Print Styles */
        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }
            
            .kwitansi-container {
                width: 190mm;
                max-height: 270mm;
                margin: 10mm auto;
                padding: 8mm 12mm;
                border: 2px solid #333;
                box-shadow: none;
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
        }
        
        /* Action Buttons */
        .action-buttons {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f0f0f0;
        }
        
        .action-buttons button {
            padding: 10px 30px;
            font-size: 14px;
            margin: 0 10px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }
        
        .btn-print {
            background: #0d6efd;
            color: #fff;
        }
        
        .btn-back {
            background: #6c757d;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Kwitansi
        </button>
        <button class="btn-back" onclick="window.history.back()">
            ← Kembali
        </button>
    </div>

    <div class="kwitansi-container">
        {{-- Watermark --}}
        @if($kwitansi['template']['tampilkan_watermark'] && !empty($kwitansi['template']['watermark_path']))
        <div class="watermark {{ $kwitansi['template']['watermark_posisi'] }}" 
             style="opacity: {{ $kwitansi['template']['watermark_opacity'] }}; width: {{ $kwitansi['template']['watermark_ukuran'] }}%;">
            <img src="{{ Storage::url($kwitansi['template']['watermark_path']) }}" alt="Watermark">
        </div>
        @endif

        <div class="content">
            {{-- Header --}}
            <div class="header">
                @if($kwitansi['template']['tampilkan_logo'] && !empty($kwitansi['template']['logo_path']))
                <img src="{{ Storage::url($kwitansi['template']['logo_path']) }}" alt="Logo" class="logo">
                @endif
                <h1>{{ $kwitansi['template']['nama_institusi'] }}</h1>
                @if($kwitansi['template']['alamat'])
                <div class="alamat">{{ $kwitansi['template']['alamat'] }}</div>
                @endif
                @if($kwitansi['template']['telepon'])
                <div class="alamat">Telp: {{ $kwitansi['template']['telepon'] }}</div>
                @endif
            </div>

            {{-- Judul --}}
            <div class="judul-kwitansi">
                <h2>{{ $kwitansi['template']['judul_kwitansi'] }}</h2>
                <div class="nomor">No: <strong>{{ $kwitansi['nomor_kwitansi'] }}</strong></div>
            </div>

            {{-- Detail --}}
            <div class="detail">
                <table>
                    <tr>
                        <td>Telah diterima dari</td>
                        <td>:</td>
                        <td><strong>{{ $kwitansi['nama_peserta'] }}</strong></td>
                    </tr>
                    <tr>
                        <td>No. Pendaftaran</td>
                        <td>:</td>
                        <td>{{ $kwitansi['nomor_pendaftaran'] }}</td>
                    </tr>
                    <tr>
                        <td>Untuk Pembayaran</td>
                        <td>:</td>
                        <td>{{ $kwitansi['jenis_pembayaran'] }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Pembayaran</td>
                        <td>:</td>
                        <td>{{ $kwitansi['tanggal_bayar']->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Jumlah Uang</td>
                        <td>:</td>
                        <td class="nominal">Rp {{ number_format($kwitansi['nominal'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Terbilang</td>
                        <td>:</td>
                        <td class="terbilang">{{ ucwords(terbilang($kwitansi['nominal'])) }} Rupiah</td>
                    </tr>
                </table>
            </div>

            {{-- Footer Text --}}
            @if($kwitansi['template']['teks_footer'])
            <div class="footer-text">
                {{ $kwitansi['template']['teks_footer'] }}
            </div>
            @endif

            {{-- Tanda Tangan --}}
            <div class="ttd-section">
                <div class="ttd-box">
                    <div class="tanggal">{{ $kwitansi['tanggal_verifikasi']->format('d F Y') }}</div>
                    <div class="space">
                        @if($kwitansi['template']['tampilkan_stempel'] && !empty($kwitansi['template']['stempel_path']))
                        <img src="{{ Storage::url($kwitansi['template']['stempel_path']) }}" alt="Stempel" class="stempel">
                        @endif
                    </div>
                    <div class="nama">{{ $kwitansi['template']['nama_penandatangan'] }}</div>
                    <div class="jabatan">{{ $kwitansi['template']['jabatan_penandatangan'] }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

@php
/**
 * Helper function untuk mengubah angka menjadi terbilang
 */
function terbilang($angka) {
    $angka = abs($angka);
    $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    $temp = '';
    
    if ($angka < 12) {
        $temp = ' ' . $huruf[$angka];
    } elseif ($angka < 20) {
        $temp = terbilang($angka - 10) . ' belas';
    } elseif ($angka < 100) {
        $temp = terbilang($angka / 10) . ' puluh' . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $temp = ' seratus' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $temp = terbilang($angka / 100) . ' ratus' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $temp = ' seribu' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $temp = terbilang($angka / 1000) . ' ribu' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $temp = terbilang($angka / 1000000) . ' juta' . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $temp = terbilang($angka / 1000000000) . ' milyar' . terbilang(fmod($angka, 1000000000));
    } elseif ($angka < 1000000000000000) {
        $temp = terbilang($angka / 1000000000000) . ' trilyun' . terbilang(fmod($angka, 1000000000000));
    }
    
    return $temp;
}
@endphp
