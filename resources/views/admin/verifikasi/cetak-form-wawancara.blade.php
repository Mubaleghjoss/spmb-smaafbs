<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Wawancara - {{ $peserta->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; }
        .page { width: 210mm; min-height: 297mm; padding: 10mm; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { font-size: 14px; margin-bottom: 3px; }
        .header h2 { font-size: 12px; font-weight: normal; }
        .info-row { display: flex; margin-bottom: 8px; }
        .info-row .label { width: 120px; }
        .info-row .value { flex: 1; border-bottom: 1px dotted #000; }
        .info-row .label2 { width: 80px; margin-left: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; font-weight: bold; }
        .no-col { width: 30px; text-align: center; }
        .jawaban-col { width: 40%; }
        .pertanyaan-col { width: 55%; }
        .section-title { font-weight: bold; margin: 15px 0 10px; font-size: 12px; }
        @media print {
            .page { width: 100%; padding: 5mm; }
            .no-print { display: none; }
        }
        .print-btn { position: fixed; top: 10px; right: 10px; padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Cetak</button>
    
    {{-- HALAMAN 1: FORM ORANG TUA --}}
    <div class="page">
        <div class="header">
            <h1>FORMULIR INTERVIEW UNTUK ORANG TUA/WALI SISWA/I</h1>
            <h2>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</h2>
        </div>
        
        <div style="display: flex; margin-bottom: 15px;">
            <div style="flex: 1;">
                <div class="info-row">
                    <span class="label">Nama Calon Siswa</span>
                    <span>: {{ $peserta->nama }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Nama Orang Tua</span>
                    <span>: {{ $peserta->formulirSpmb?->nama_ayah ?? '................................' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Nama Interviewer</span>
                    <span>: ................................</span>
                </div>
            </div>
            <div style="width: 200px;">
                <div class="info-row">
                    <span class="label2">Umur</span>
                    <span>: {{ $peserta->formulirSpmb?->tanggal_lahir?->age ?? '.....' }}</span>
                </div>
                <div class="info-row">
                    <span class="label2">Kelompok</span>
                    <span>: ............</span>
                </div>
                <div class="info-row">
                    <span class="label2">Hari/tgl</span>
                    <span>: ............</span>
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th class="no-col">NO.</th>
                    <th class="pertanyaan-col">PERTANYAAN UNTUK ORTU</th>
                    <th class="jawaban-col">TANGGAPAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pertanyaanOrtu as $no => $pertanyaan)
                <tr>
                    <td class="no-col">{{ $no }}</td>
                    <td>{!! nl2br(e($pertanyaan)) !!}</td>
                    <td style="height: 50px;"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- HALAMAN 2: FORM SISWA --}}
    <div class="page" style="page-break-before: always;">
        <div class="header">
            <h1>FORMULIR INTERVIEW UNTUK CALON SISWA/I</h1>
            <h2>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</h2>
        </div>
        
        <div style="display: flex; margin-bottom: 15px;">
            <div style="flex: 1;">
                <div class="info-row">
                    <span class="label">Nama Calon Siswa</span>
                    <span>: {{ $peserta->nama }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Nama Orang Tua</span>
                    <span>: {{ $peserta->formulirSpmb?->nama_ayah ?? '................................' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Nama Interviewer</span>
                    <span>: ................................</span>
                </div>
            </div>
            <div style="width: 200px;">
                <div class="info-row">
                    <span class="label2">Umur</span>
                    <span>: {{ $peserta->formulirSpmb?->tanggal_lahir?->age ?? '.....' }}</span>
                </div>
                <div class="info-row">
                    <span class="label2">Kelompok</span>
                    <span>: ............</span>
                </div>
                <div class="info-row">
                    <span class="label2">Hari/tgl</span>
                    <span>: ............</span>
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th class="no-col">NO.</th>
                    <th class="pertanyaan-col">PERTANYAAN</th>
                    <th class="jawaban-col">TANGGAPAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pertanyaanSiswa as $no => $pertanyaan)
                <tr>
                    <td class="no-col">{{ $no }}</td>
                    <td>{!! nl2br(e($pertanyaan)) !!}</td>
                    <td style="height: 50px;"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
