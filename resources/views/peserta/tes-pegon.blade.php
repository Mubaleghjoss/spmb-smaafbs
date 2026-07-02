<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal Tes Pegon - {{ $peserta->nama ?? '' }} - {{ $branding['nama_singkat'] ?? 'SPMB' }}</title>
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    <style>
        @page { size: A4 portrait; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { width: 210mm; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            background: #fff;
        }
        .header-fields { margin-bottom: 10px; }
        .header-fields table { width: 50%; }
        .header-fields td { padding: 1px 0; font-weight: bold; }
        .header-fields td:first-child { width: 135px; }
        .header-fields td:nth-child(2) { width: 10px; text-align: center; }
        .header-fields input {
            border: none; border-bottom: 1px dotted #000;
            width: 100%; font-size: 12px; font-family: inherit;
            padding: 1px 4px; background: transparent;
        }
        h2 { text-align: center; font-size: 14px; margin-bottom: 8px; text-decoration: underline; }
        .soal-section { margin-bottom: 8px; }
        .soal-section p { margin-bottom: 4px; }
        .soal-arabic { font-size: 14px; margin: 3px 0 3px 24px; }
        .soal-list { margin-left: 20px; }
        .soal-list li { margin-bottom: 2px; }
        .soal-list li b { font-weight: 900; }
        .jawaban-section { margin-top: 10px; }
        .jawaban-section h3 { font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .jawaban-line {
            border-bottom: 1px dotted #000;
            height: 19px;
            margin-bottom: 2px;
        }
        @media print {
            body { width: 186mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        .print-btn {
            position: fixed; top: 20px; right: 20px; z-index: 1000;
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: #198754; color: #fff;
            border: none; border-radius: 8px; font-size: 16px; cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .print-btn:hover { background: #146c43; }
        .print-btn svg { width: 18px; height: 18px; flex: 0 0 auto; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()" type="button">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 9V3h12v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 14h12v7H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>Cetak / Print</span>
    </button>

    <div class="header-fields">
        <table>
            <tr>
                <td>NAMA LENGKAP</td>
                <td>:</td>
                <td><input type="text" value="{{ $peserta->nama ?? '' }}"></td>
            </tr>
            <tr>
                <td>KELOMPOK / DESA</td>
                <td>:</td>
                <td><input type="text"></td>
            </tr>
            <tr>
                <td>NO. HP</td>
                <td>:</td>
                <td><input type="text"></td>
            </tr>
        </table>
    </div>

    <h2>UBAHLAH KALIMAT DIBAWAH INI MENJADI PEGON</h2>

    <div class="soal-section">
        <ol class="soal-list" style="margin-left:30px">
            @foreach($teksPegon as $teks)
            <li><b>{{ $teks }}</b></li>
            @endforeach
        </ol>
    </div>

    <div class="jawaban-section">
        <h3>JAWABAN</h3>
        @for($i = 0; $i < 20; $i++)
        <div class="jawaban-line"></div>
        @endfor
    </div>
</body>
</html>
