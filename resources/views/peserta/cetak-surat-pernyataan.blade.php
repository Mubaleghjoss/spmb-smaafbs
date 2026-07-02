<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pernyataan - {{ $peserta->nama }}</title>
    <style>
        @page { size: A4; margin: 2cm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 13px; line-height: 1.5; color: #000; background: #fff; }
        .page { max-width: 210mm; margin: 0 auto; padding: 2cm; }
        .page + .page { page-break-before: always; }
        h3 { text-align: center; text-decoration: underline; font-size: 15px; margin-bottom: 20px; }
        .field-row { margin-bottom: 3px; display: flex; }
        .field-label { width: 200px; font-weight: bold; }
        .field-sep { width: 15px; text-align: center; }
        .field-value { flex: 1; border-bottom: 1px dotted #000; min-height: 20px; }
        .content { margin-top: 15px; }
        .content ol { margin-left: 20px; }
        .content ol li { margin-bottom: 6px; }
        .content ol ol { margin-top: 4px; }
        .content ol ol li { margin-bottom: 3px; }
        .closing { margin-top: 20px; }
        .signature-area { text-align: right; margin-top: 20px; }
        .signature-area .ttd-box { display: inline-block; text-align: center; }
        .signature-area img { max-width: 200px; max-height: 80px; display: block; margin: 5px auto; }
        .no-print { position: fixed; top: 15px; right: 15px; z-index: 999; }
        .no-print button { padding: 10px 20px; background: #198754; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; margin-left: 5px; box-shadow: 0 2px 8px rgba(0,0,0,.2); }
        .no-print button:hover { opacity: .85; }
        .no-print .btn-wa { background: #25D366; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    @php
        $formatTanggalSurat = function ($value = null) {
            try {
                $tanggal = $value ? \Illuminate\Support\Carbon::parse($value) : now();
            } catch (\Throwable $e) {
                $tanggal = now();
            }

            $bulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            return $tanggal->format('d') . ' ' . $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');
        };
    @endphp

    <div class="no-print">
        <button onclick="window.print()">🖨️ Cetak / PDF</button>
        <a href="{{ route('peserta.wawancara.surat-pernyataan.pdf') }}">
            <button style="background:#dc3545">📄 Download PDF</button>
        </a>
        @php
            $noHp = $peserta->wawancara?->surat_pernyataan_siswa['no_telp_ortu']
                ?? $peserta->wawancara?->surat_pernyataan_ortu['no_hp']
                ?? $peserta->formulirSpmb?->no_hp_ortu
                ?? '';
            $noHpWa = preg_replace('/^0/', '62', preg_replace('/\D/', '', $noHp));
            $pdfLink = route('peserta.wawancara.surat-pernyataan.pdf');
            $pesan = urlencode("Assalamu'alaikum,\n\nBerikut surat pernyataan SPMB SMA AFBS atas nama:\nNama Siswa: {$peserta->nama}\n\nSilakan download PDF surat pernyataan melalui link berikut:\n{$pdfLink}\n\nAtau buka halaman cetak:\n" . url()->current());
        @endphp
        @if($noHpWa)
        <a href="https://wa.me/{{ $noHpWa }}?text={{ $pesan }}" target="_blank">
            <button class="btn-wa">📱 Kirim via WhatsApp ({{ $noHp }})</button>
        </a>
        @endif
    </div>

    {{-- ============ SURAT PERNYATAAN SISWA ============ --}}
    @php $sp = $peserta->wawancara?->surat_pernyataan_siswa ?? []; @endphp
    @php $tanggalSuratSiswa = $formatTanggalSurat($sp['tanggal_surat'] ?? $peserta->wawancara?->diisi_peserta_pada ?? null); @endphp
    <div class="page">
        <h3>SURAT PERNYATAAN SISWA/I</h3>

        <p style="margin-bottom:10px">Yang bertandatangan di bawah ini,</p>

        <div class="field-row"><div class="field-label">Nama Lengkap</div><div class="field-sep">:</div><div class="field-value">{{ $sp['nama_lengkap'] ?? $peserta->nama ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Tempat, Tanggal Lahir</div><div class="field-sep">:</div><div class="field-value">{{ $sp['tempat_tgl_lahir'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Alamat</div><div class="field-sep">:</div><div class="field-value">{{ $sp['alamat'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Nama Orangtua/Wali</div><div class="field-sep">:</div><div class="field-value">{{ $sp['nama_ortu'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">No.Telp/HP Orang tua/Wali</div><div class="field-sep">:</div><div class="field-value">{{ $sp['no_telp_ortu'] ?? '' }}</div></div>

        <div class="content">
            <p>Menyatakan dengan sungguh-sungguh, setelah memahami isi, maksud dan tujuan surat pernyataan ini. Maka selama menjadi siswa/i di SMA Al Furqon Boarding School, sanggup menetapi dan menjalankan hal-hal sebagai berikut:</p>
            <ol>
                @foreach($spSiswaPoin as $poin)
                <li>{!! nl2br(e($poin)) !!}</li>
                @endforeach
            </ol>
        </div>

        <div class="closing">
            <p>Demikian Pernyataan ini saya buat dengan sebenar-benarnya dengan penuh tanggung jawab dan tidak ada paksaan dari pihak manapun.</p>
        </div>

        <div class="signature-area">
            <div class="ttd-box">
                <p>Tangerang, {{ $tanggalSuratSiswa }}</p>
                <p>Hormat Kami</p>
                @if($peserta->wawancara?->tanda_tangan_peserta)
                <img src="{{ $peserta->wawancara->tanda_tangan_peserta }}" alt="TTD">
                @else
                <div style="height:60px"></div>
                @endif
                <p style="border-top:1px solid #000;padding-top:3px">{{ $sp['nama_lengkap'] ?? $peserta->nama ?? '.......................' }}</p>
            </div>
        </div>
    </div>

    {{-- ============ SURAT PERNYATAAN ORANGTUA ============ --}}
    @php $spo = $peserta->wawancara?->surat_pernyataan_ortu ?? []; @endphp
    @php $tanggalSuratOrtu = $formatTanggalSurat($spo['tanggal_surat'] ?? $peserta->wawancara?->diisi_peserta_pada ?? null); @endphp
    <div class="page">
        <h3>SURAT PERNYATAAN ORANGTUA</h3>

        <p style="margin-bottom:10px">Saya yang bertanda tangan di bawah ini:</p>

        <div class="field-row"><div class="field-label">Nama Lengkap</div><div class="field-sep">:</div><div class="field-value">{{ $spo['nama_lengkap'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Alamat</div><div class="field-sep">:</div><div class="field-value">{{ $spo['alamat'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Kelompok</div><div class="field-sep">:</div><div class="field-value">{{ $spo['kelompok'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Nama KI Kelompok + No. HP</div><div class="field-sep">:</div><div class="field-value">{{ $spo['nama_ki'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Desa</div><div class="field-sep">:</div><div class="field-value">{{ $spo['desa'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Daerah</div><div class="field-sep">:</div><div class="field-value">{{ $spo['daerah'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">No. HP Orang tua/Wali</div><div class="field-sep">:</div><div class="field-value">{{ $spo['no_hp'] ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Nama Siswa</div><div class="field-sep">:</div><div class="field-value">{{ $spo['nama_siswa'] ?? $peserta->nama ?? '' }}</div></div>
        <div class="field-row"><div class="field-label">Asal Sekolah</div><div class="field-sep">:</div><div class="field-value">{{ $spo['asal_sekolah'] ?? '' }}</div></div>

        <div class="content">
            <p>Saya dengan ini menyatakan menyetujui peraturan SPMB SMA AFBS yang telah ditetapkan yaitu:</p>
            <ol>
                @foreach($spOrtuPoin as $poin)
                <li>{!! nl2br(e($poin)) !!}</li>
                @endforeach
            </ol>
        </div>

        <div class="closing">
            <p>Demikian Pernyataan ini saya buat dengan sebenar-benarnya dengan penuh tanggung jawab dan tidak ada paksaan dari pihak manapun.</p>
        </div>

        <div style="display:flex;justify-content:space-between;margin-top:20px">
            <div class="ttd-box" style="text-align:center">
                <p>Mengetahui,</p>
                <p>Tim SPMB SMA AFBS</p>
                <div style="height:60px"></div>
                <p style="border-top:1px solid #000;padding-top:3px">................................</p>
            </div>
            <div class="ttd-box" style="text-align:center">
                <p>Tangerang, {{ $tanggalSuratOrtu }}</p>
                <p>Hormat Kami</p>
                @if($peserta->wawancara?->tanda_tangan_ortu)
                <img src="{{ $peserta->wawancara->tanda_tangan_ortu }}" alt="TTD" style="max-width:200px;max-height:60px;display:block;margin:5px auto">
                @else
                <div style="height:60px"></div>
                @endif
                <p style="border-top:1px solid #000;padding-top:3px">Orangtua/Wali Murid</p>
                <p>{{ $spo['nama_lengkap'] ?? '.......................' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
