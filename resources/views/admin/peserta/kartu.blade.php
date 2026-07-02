<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Peserta - {{ $peserta->nomor_pendaftaran }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .card-container {
            width: 350px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #1a5f2a 0%, #2e8b57 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .card-header h1 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .card-header p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .photo-section {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .photo {
            width: 100px;
            height: 120px;
            background: #e9ecef;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 12px;
            overflow: hidden;
        }
        
        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .nomor-pendaftaran {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .nomor-pendaftaran .label {
            font-size: 10px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nomor-pendaftaran .value {
            font-size: 18px;
            font-weight: 700;
            color: #1a5f2a;
            font-family: 'Courier New', monospace;
        }
        
        .info-table {
            width: 100%;
            font-size: 12px;
        }
        
        .info-table tr td {
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-table tr:last-child td {
            border-bottom: none;
        }
        
        .info-table .label {
            color: #6c757d;
            width: 35%;
        }
        
        .info-table .value {
            font-weight: 500;
        }
        
        .card-footer {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .qr-placeholder {
            width: 80px;
            height: 80px;
            background: #e9ecef;
            border: 1px dashed #adb5bd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .footer-text {
            font-size: 10px;
            color: #6c757d;
        }
        
        .print-btn {
            display: block;
            width: 350px;
            margin: 20px auto;
            padding: 12px;
            background: #1a5f2a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .print-btn:hover {
            background: #155724;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #1a5f2a;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .card-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .print-btn, .back-link {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card-header">
            <h1>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</h1>
            <p>Kartu Peserta {{ $branding['nama_singkat'] ?? 'SPMB' }} {{ $branding['tahun_ajaran'] ?? date('Y') }}</p>
        </div>
        
        <div class="card-body">
            <div class="photo-section">
                <div class="photo">
                    @if($peserta->foto)
                        <img src="{{ asset('storage/' . $peserta->foto) }}" alt="Foto Peserta">
                    @else
                        Pas Foto<br>3x4
                    @endif
                </div>
            </div>
            
            <div class="nomor-pendaftaran">
                <div class="label">Nomor Pendaftaran</div>
                <div class="value">{{ $peserta->nomor_pendaftaran }}</div>
            </div>
            
            <table class="info-table">
                <tr>
                    <td class="label">Nama</td>
                    <td class="value">{{ $peserta->nama }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td class="value">{{ $peserta->email }}</td>
                </tr>
                <tr>
                    <td class="label">Telepon</td>
                    <td class="value">{{ $peserta->telepon ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Asal Sekolah</td>
                    <td class="value">{{ $peserta->asal_sekolah ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Tahap</td>
                    <td class="value">Tahap {{ $peserta->tahap_saat_ini }}</td>
                </tr>
            </table>
        </div>
        
        <div class="card-footer">
            <div class="qr-placeholder">QR Code</div>
            <div class="footer-text">
                Kartu ini wajib dibawa saat mengikuti tes dan wawancara<br>
                Dicetak: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">
        🖨️ Cetak Kartu
    </button>
    
    <a href="{{ route('admin.peserta.show', $peserta) }}" class="back-link">
        ← Kembali ke Detail Peserta
    </a>
</body>
</html>
