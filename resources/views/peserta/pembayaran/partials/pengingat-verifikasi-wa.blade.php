@if($pembayaran->status === \App\Enums\StatusPembayaran::MENUNGGU->value)
    @php
        $timList = collect($kontakTimSpmb ?? [])->filter(fn ($tim) => !empty($tim['whatsapp']))->values();
        $template = $spmb[$templateKey] ?? '';
        $pesanPengingat = \App\Services\PengaturanService::renderTemplatePengingatPembayaran(
            $template,
            $peserta->nama,
            $peserta->nomor_pendaftaran,
            $pembayaran->nominal,
            $jenisPembayaran,
        );
    @endphp
    @if($timList->isNotEmpty())
        <div class="alert alert-success text-start mt-3 mb-0">
            <div class="fw-semibold mb-2"><i class="bi bi-whatsapp me-1"></i>Sudah transfer? Kabari Tim SPMB agar bukti segera diverifikasi.</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($timList as $tim)
                    <a href="https://wa.me/{{ \App\Services\PengaturanService::nomorWhatsAppInternasional($tim['whatsapp']) }}?text={{ urlencode($pesanPengingat) }}"
                       target="_blank" rel="noopener" class="btn btn-success btn-sm">
                        <i class="bi bi-whatsapp me-1"></i>Kabari {{ $tim['nama'] ?: 'Tim SPMB' }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endif
