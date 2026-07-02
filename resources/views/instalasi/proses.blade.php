@extends('instalasi.layout')

@section('content')
<h5 class="mb-4"><i class="bi bi-gear-wide-connected"></i> Proses Instalasi</h5>

<div id="instalasi-status">
    <div class="text-center py-4">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Mempersiapkan instalasi...</p>
    </div>
</div>

<div id="instalasi-log" class="bg-dark text-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 13px;">
</div>

<div class="mt-4 d-flex justify-content-between">
    <a href="{{ route('instalasi.admin') }}" class="btn btn-outline-secondary" id="btn-back">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <a href="{{ route('instalasi.selesai') }}" class="btn btn-success d-none" id="btn-finish">
        Selesai <i class="bi bi-check-lg"></i>
    </a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const log = document.getElementById('instalasi-log');
    const status = document.getElementById('instalasi-status');
    const btnBack = document.getElementById('btn-back');
    const btnFinish = document.getElementById('btn-finish');

    function addLog(message, type = 'info') {
        const colors = { success: '#28a745', error: '#dc3545', info: '#17a2b8' };
        const icons = { success: '✓', error: '✗', info: '→' };
        log.innerHTML += `<div style="color: ${colors[type]}">${icons[type]} ${message}</div>`;
        log.scrollTop = log.scrollHeight;
    }

    addLog('Memulai proses instalasi...');

    fetch('{{ route("instalasi.jalankan") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        data.hasil.forEach(item => {
            addLog(item.pesan, item.status);
        });

        if (data.success) {
            status.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> Instalasi berhasil!
                </div>
            `;
            btnBack.classList.add('d-none');
            btnFinish.classList.remove('d-none');
        } else {
            status.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill"></i> Instalasi gagal. Silakan cek log di atas.
                </div>
            `;
        }
    })
    .catch(error => {
        addLog('Error: ' + error.message, 'error');
        status.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-x-circle-fill"></i> Terjadi kesalahan. Silakan coba lagi.
            </div>
        `;
    });
});
</script>
@endpush
