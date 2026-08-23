<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal Tes Pegon - {{ $peserta->nama ?? '' }} - {{ $branding['nama_singkat'] ?? 'SPMB' }}</title>
    @if(!empty($branding['favicon']) && !($isPdf ?? false))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    <style>
        @page { size: A4 portrait; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @if(!($isPdf ?? false))
        html { width: 210mm; }
        @endif
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            background: #fff;
        }
        .header-fields { margin-bottom: 10px; }
        .header-fields table { width: 60%; }
        .header-fields td { padding: 1px 0; font-weight: bold; vertical-align: bottom; }
        .header-fields td:first-child { width: 135px; }
        .header-fields td:nth-child(2) { width: 10px; text-align: center; }
        .header-fields .isian {
            border-bottom: 1px dotted #000;
            min-height: 16px;
            padding: 1px 4px;
            font-weight: normal;
        }
        h2 { text-align: center; font-size: 14px; margin-bottom: 8px; text-decoration: underline; }
        .soal-section { margin-bottom: 8px; }
        .soal-section p { margin-bottom: 4px; }
        .soal-list { margin-left: 30px; }
        .soal-list li { margin-bottom: 3px; }
        .soal-list li b { font-weight: 900; }
        .jawaban-section { margin-top: 10px; }
        .jawaban-section h3 { font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .jawaban-line {
            border-bottom: 1px dotted #000;
            height: 19px;
            margin-bottom: 2px;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        .toolbar {
            position: fixed; top: 16px; right: 16px; z-index: 1000;
            display: flex; gap: 8px;
        }
        .toolbar a, .toolbar button {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 18px; color: #fff; text-decoration: none;
            border: none; border-radius: 8px; font-size: 14px; cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif; font-weight: 600;
        }
        .btn-pdf { background: #dc3545; }
        .btn-print { background: #198754; }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
    <div class="toolbar no-print">
        <a href="{{ route('peserta.wawancara.download-pegon.pdf') }}" class="btn-pdf">
            &#128196; Download PDF
        </a>
        <button class="btn-print" onclick="window.print()" type="button">
            &#128424; Cetak
        </button>
    </div>
    @endif

    <div class="header-fields">
        <table>
            <tr>
                <td>NAMA LENGKAP</td>
                <td>:</td>
                <td><div class="isian">{{ $peserta->nama ?? '' }}</div></td>
            </tr>
            <tr>
                <td>KELOMPOK / DESA</td>
                <td>:</td>
                <td><div class="isian">{{ $kelompokDesa ?? '' }}</div></td>
            </tr>
            <tr>
                <td>NO. HP</td>
                <td>:</td>
                <td><div class="isian">{{ $noHp ?? '' }}</div></td>
            </tr>
        </table>
    </div>

    <h2>UBAHLAH KALIMAT DIBAWAH INI MENJADI PEGON</h2>

    <div class="soal-section">
        <ol class="soal-list">
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
