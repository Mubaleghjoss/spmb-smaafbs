@extends('layouts.admin')

@section('title', 'Lihat Semua Soal')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.soal.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Bank Soal
            </a>
            <h1 class="h3 mb-0">Lihat Semua Soal</h1>
        </div>
        <div>
            <a href="{{ route('admin.soal.preview', request()->query()) }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-eye"></i> Preview Soal
            </a>
            <a href="{{ route('admin.soal.ekspor', request()->query()) }}" class="btn btn-outline-info">
                <i class="bi bi-download"></i> Ekspor
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Filter Aktif --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <strong>Filter Aktif:</strong>
                @if($filter['topik_id'])
                    @php $topikNama = $topik->firstWhere('id', $filter['topik_id'])?->nama ?? 'Unknown'; @endphp
                    <span class="badge bg-primary">Topik: {{ $topikNama }}</span>
                @endif
                @if($filter['tipe'])
                    <span class="badge bg-info">Tipe: {{ ucfirst(str_replace('_', ' ', $filter['tipe'])) }}</span>
                @endif
                @if($filter['aktif'] !== null)
                    <span class="badge {{ $filter['aktif'] ? 'bg-success' : 'bg-secondary' }}">
                        Status: {{ $filter['aktif'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                @endif
                @if($filter['cari'])
                    <span class="badge bg-warning text-dark">Cari: "{{ $filter['cari'] }}"</span>
                @endif
                @if(!$filter['topik_id'] && !$filter['tipe'] && $filter['aktif'] === null && !$filter['cari'])
                    <span class="text-muted">Tidak ada filter</span>
                @endif
                <span class="ms-auto badge bg-dark fs-6">Total: {{ $soal->count() }} soal</span>
            </div>
        </div>
    </div>

    {{-- Toolbar Urutan --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Pilih Semua</label>
                </div>
                <div class="vr mx-2"></div>
                <span class="text-muted" id="selectedCount">0 soal dipilih</span>
                <div class="vr mx-2"></div>
                <button type="button" class="btn btn-danger btn-sm" id="btnHapusTerpilih" disabled>
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btnSimpanUrutan" disabled>
                    <i class="bi bi-save me-1"></i>Simpan Urutan
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetUrutan" disabled>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <div class="ms-auto">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Drag baris untuk mengubah urutan, lalu klik "Simpan Urutan"
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus Massal --}}
    <div class="modal fade" id="modalHapusMassal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus <strong id="jumlahTerpilih">0</strong> soal yang dipilih?</p>
                    <p class="text-danger mb-0"><i class="bi bi-exclamation-circle me-1"></i>Tindakan ini tidak dapat dibatalkan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnKonfirmasiHapus">
                        <i class="bi bi-trash me-1"></i>Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Soal --}}
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Daftar Semua Soal</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0" id="tableSoal">
                    <thead class="table-dark">
                        <tr>
                            <th width="40" class="text-center">
                                <i class="bi bi-grip-vertical text-muted"></i>
                            </th>
                            <th width="40" class="text-center">
                                <i class="bi bi-check-square"></i>
                            </th>
                            <th width="50" class="text-center">#</th>
                            <th>Pertanyaan</th>
                            <th width="150">Topik</th>
                            <th width="120">Tipe</th>
                            <th width="80" class="text-center">Bobot</th>
                            <th width="80" class="text-center">Status</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="sortableBody">
                        @forelse($soal as $index => $s)
                            <tr data-id="{{ $s->id }}" data-urutan="{{ $s->urutan }}">
                                <td class="text-center drag-handle" style="cursor: grab;">
                                    <i class="bi bi-grip-vertical text-secondary"></i>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input soal-checkbox" value="{{ $s->id }}">
                                </td>
                                <td class="text-center nomor-urut">{{ $index + 1 }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 350px;">
                                        {!! strip_tags($s->pertanyaan) !!}
                                    </div>
                                    @if($s->memiliki_media)
                                        <span class="badge bg-info"><i class="bi bi-image"></i> Media</span>
                                    @endif
                                </td>
                                <td>{{ $s->topik?->nama ?? '-' }}</td>
                                <td>
                                    @switch($s->tipe)
                                        @case('pilihan_ganda')
                                            <span class="badge bg-primary">Pilihan Ganda</span>
                                            @break
                                        @case('jawaban_ganda')
                                            <span class="badge bg-info">Jawaban Ganda</span>
                                            @break
                                        @case('esai')
                                            <span class="badge bg-warning">Esai</span>
                                            @break
                                        @case('benar_salah')
                                            <span class="badge bg-secondary">Benar/Salah</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-center">{{ $s->bobot }}</td>
                                <td class="text-center">
                                    @if($s->aktif)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.soal.show', $s) }}" class="btn btn-info text-white" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.soal.edit', $s) }}" class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    Tidak ada soal yang ditemukan dengan filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                Menampilkan {{ $soal->count() }} soal
            </small>
        </div>
    </div>

    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan:</strong>
                <span class="ms-3"><i class="bi bi-grip-vertical"></i> Drag untuk mengubah urutan</span>
                <span class="ms-3"><i class="bi bi-eye text-info"></i> Lihat Detail</span>
                <span class="ms-3"><i class="bi bi-pencil text-warning"></i> Edit</span>
            </small>
        </div>
    </div>
</div>

{{-- SortableJS CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortableBody = document.getElementById('sortableBody');
    const selectAllCheckbox = document.getElementById('selectAll');
    const btnSimpanUrutan = document.getElementById('btnSimpanUrutan');
    const btnResetUrutan = document.getElementById('btnResetUrutan');
    const btnHapusTerpilih = document.getElementById('btnHapusTerpilih');
    const selectedCountEl = document.getElementById('selectedCount');
    const modalHapusMassal = new bootstrap.Modal(document.getElementById('modalHapusMassal'));
    const btnKonfirmasiHapus = document.getElementById('btnKonfirmasiHapus');
    const jumlahTerpilihEl = document.getElementById('jumlahTerpilih');
    
    let hasChanges = false;
    let originalOrder = [];
    
    // Simpan urutan awal
    function saveOriginalOrder() {
        originalOrder = [];
        sortableBody.querySelectorAll('tr[data-id]').forEach((row, index) => {
            originalOrder.push({
                id: row.dataset.id,
                index: index
            });
        });
    }
    saveOriginalOrder();
    
    // Initialize SortableJS
    const sortable = new Sortable(sortableBody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function(evt) {
            updateNomorUrut();
            hasChanges = true;
            updateButtons();
        }
    });
    
    // Update nomor urut setelah drag
    function updateNomorUrut() {
        sortableBody.querySelectorAll('.nomor-urut').forEach((el, index) => {
            el.textContent = index + 1;
        });
    }
    
    // Update status tombol
    function updateButtons() {
        btnSimpanUrutan.disabled = !hasChanges;
        btnResetUrutan.disabled = !hasChanges;
        
        if (hasChanges) {
            btnSimpanUrutan.classList.remove('btn-success');
            btnSimpanUrutan.classList.add('btn-warning');
        } else {
            btnSimpanUrutan.classList.remove('btn-warning');
            btnSimpanUrutan.classList.add('btn-success');
        }
    }
    
    // Update jumlah yang dipilih
    function updateSelectedCount() {
        const checked = document.querySelectorAll('.soal-checkbox:checked').length;
        selectedCountEl.textContent = checked + ' soal dipilih';
        btnHapusTerpilih.disabled = checked === 0;
    }
    
    // Select All checkbox
    selectAllCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.soal-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateSelectedCount();
    });
    
    // Individual checkbox
    document.querySelectorAll('.soal-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.soal-checkbox').length === 
                              document.querySelectorAll('.soal-checkbox:checked').length;
            selectAllCheckbox.checked = allChecked;
            updateSelectedCount();
        });
    });
    
    // Tombol Hapus Terpilih - buka modal
    btnHapusTerpilih.addEventListener('click', function() {
        const checked = document.querySelectorAll('.soal-checkbox:checked').length;
        jumlahTerpilihEl.textContent = checked;
        modalHapusMassal.show();
    });
    
    // Konfirmasi hapus massal
    btnKonfirmasiHapus.addEventListener('click', function() {
        const ids = [];
        document.querySelectorAll('.soal-checkbox:checked').forEach(cb => {
            ids.push(parseInt(cb.value));
        });
        
        if (ids.length === 0) return;
        
        btnKonfirmasiHapus.disabled = true;
        btnKonfirmasiHapus.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menghapus...';
        
        fetch('{{ route("admin.soal.hapus-massal") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(res => res.json())
        .then(data => {
            modalHapusMassal.hide();
            
            if (data.sukses) {
                // Hapus baris dari tabel
                ids.forEach(id => {
                    const row = sortableBody.querySelector(`tr[data-id="${id}"]`);
                    if (row) row.remove();
                });
                
                // Update nomor urut
                updateNomorUrut();
                
                // Reset checkbox
                selectAllCheckbox.checked = false;
                updateSelectedCount();
                
                // Update total di footer
                const totalBadge = document.querySelector('.badge.bg-dark.fs-6');
                if (totalBadge) {
                    const remaining = sortableBody.querySelectorAll('tr[data-id]').length;
                    totalBadge.textContent = 'Total: ' + remaining + ' soal';
                }
                
                // Show success toast
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '11';
                toast.innerHTML = `
                    <div class="toast show bg-success text-white" role="alert">
                        <div class="toast-body">
                            <i class="bi bi-check-circle me-2"></i>${data.pesan}
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            } else {
                alert('Gagal menghapus soal: ' + (data.pesan || 'Terjadi kesalahan'));
            }
            
            btnKonfirmasiHapus.disabled = false;
            btnKonfirmasiHapus.innerHTML = '<i class="bi bi-trash me-1"></i>Ya, Hapus';
        })
        .catch(err => {
            modalHapusMassal.hide();
            alert('Gagal menghapus soal. Silakan coba lagi.');
            btnKonfirmasiHapus.disabled = false;
            btnKonfirmasiHapus.innerHTML = '<i class="bi bi-trash me-1"></i>Ya, Hapus';
        });
    });
    
    // Simpan urutan
    btnSimpanUrutan.addEventListener('click', function() {
        const urutan = [];
        sortableBody.querySelectorAll('tr[data-id]').forEach((row, index) => {
            urutan.push({
                id: parseInt(row.dataset.id),
                urutan: index + 1
            });
        });
        
        btnSimpanUrutan.disabled = true;
        btnSimpanUrutan.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        
        fetch('{{ route("admin.soal.update-urutan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ urutan: urutan })
        })
        .then(res => res.json())
        .then(data => {
            if (data.sukses) {
                hasChanges = false;
                saveOriginalOrder();
                updateButtons();
                
                // Show success toast
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '11';
                toast.innerHTML = `
                    <div class="toast show bg-success text-white" role="alert">
                        <div class="toast-body">
                            <i class="bi bi-check-circle me-2"></i>${data.pesan}
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
            btnSimpanUrutan.innerHTML = '<i class="bi bi-save me-1"></i>Simpan Urutan';
        })
        .catch(err => {
            alert('Gagal menyimpan urutan. Silakan coba lagi.');
            btnSimpanUrutan.disabled = false;
            btnSimpanUrutan.innerHTML = '<i class="bi bi-save me-1"></i>Simpan Urutan';
        });
    });
    
    // Reset urutan
    btnResetUrutan.addEventListener('click', function() {
        // Restore original order
        const tbody = sortableBody;
        const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
        
        originalOrder.forEach(item => {
            const row = rows.find(r => r.dataset.id === item.id);
            if (row) {
                tbody.appendChild(row);
            }
        });
        
        updateNomorUrut();
        hasChanges = false;
        updateButtons();
    });
});
</script>
@endpush

<style>
.drag-handle:hover {
    background-color: #f8f9fa;
}
.drag-handle:active {
    cursor: grabbing !important;
}
tr.sortable-ghost {
    opacity: 0.4;
}
tr.sortable-chosen {
    background-color: #fff3cd !important;
}
</style>
@endsection
