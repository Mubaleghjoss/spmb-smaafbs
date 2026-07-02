<div wire:poll.10s="refreshStatus">
    <div class="list-group list-group-flush">
        @foreach($statusTahapan as $nomor => $item)
        <div class="list-group-item d-flex align-items-center py-3">
            <div class="flex-shrink-0 me-3">
                @if($item['selesai'])
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                @elseif($nomor == $tahapSaatIni)
                    <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        {{ $nomor }}
                    </div>
                @else
                    <div class="rounded-circle bg-secondary bg-opacity-25 text-muted d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        {{ $nomor }}
                    </div>
                @endif
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-0 {{ $item['selesai'] ? 'text-success' : '' }}">
                    <i class="bi bi-{{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                </h6>
                @if($item['selesai'])
                    <small class="text-success"><i class="bi bi-check-circle me-1"></i>Selesai</small>
                @elseif($nomor == $tahapSaatIni)
                    <small class="text-warning">Sedang berlangsung</small>
                @else
                    <small class="text-muted">Belum dibuka</small>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
