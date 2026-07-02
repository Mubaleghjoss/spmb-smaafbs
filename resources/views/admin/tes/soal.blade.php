@extends('layouts.admin')

@section('title', 'Atur Soal Tes')

@push('styles')
<style>
    .sortable-ghost { opacity: 0.4; background-color: #e3f2fd; }
    .sortable-drag { background-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
    .drag-handle { cursor: grab; color: #adb5bd; }
    .drag-handle:active { cursor: grabbing; }
    .soal-row:hover .drag-handle { color: #0d6efd; }
    .table-sm td, .table-sm th { padding: 0.4rem 0.5rem; font-size: 0.875rem; }
    .batch-actions, .batch-actions-tersedia { display: none; }
    .batch-actions.show, .batch-actions-tersedia.show { display: flex; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Atur Soal Tes</h1>
            <small class="text-muted">{{ $tes->nama }}</small>
        </div>
        <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses') && !str_contains(session('sukses'), 'Urutan'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <small>{{ session('sukses') }}</small>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Soal Terpilih -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Soal Terpilih ({{ $soalTerpilih->count() }})</span>
                        <span class="badge bg-primary">Bobot: {{ $totalBobot }}</span>
                    </div>
                </div>
                
                @if($soalTerpilih->count() > 0)
                    <!-- Batch Actions -->
                    <div class="card-body py-2 border-bottom batch-actions" id="batchActions">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><span id="selectedCount">0</span> dipilih</span>
                            <form action="{{ route('admin.tes.hapus-soal-batch', $tes) }}" method="POST" id="formHapusBatch">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="soal_ids" id="selectedSoalIds">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus soal yang dipilih?')">
                                    <i class="bi bi-trash"></i> Hapus Terpilih
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
                
                <div class="card-body p-0">
                    @if($soalTerpilih->count() > 0)
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" class="form-check-input" id="checkAll" title="Pilih Semua">
                                        </th>
                                        <th width="25"></th>
                                        <th width="30">#</th>
                                        <th>Pertanyaan</th>
                                        <th class="text-center" width="55">Bobot</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody id="soalTerpilihList">
                                    @foreach($soalTerpilih->sortBy('pivot.urutan') as $soal)
                                        <tr class="soal-row" data-id="{{ $soal->id }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input soal-check" value="{{ $soal->id }}">
                                            </td>
                                            <td class="drag-handle text-center">
                                                <i class="bi bi-grip-vertical"></i>
                                            </td>
                                            <td class="urutan-number text-muted">{{ $soal->pivot->urutan }}</td>
                                            <td>
                                                <span title="{{ strip_tags($soal->pertanyaan) }}">{!! Str::limit(strip_tags($soal->pertanyaan), 40) !!}</span>
                                                <br><small class="text-muted">{{ $soal->topik?->nama ?? '-' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" class="form-control form-control-sm text-center p-0" 
                                                       style="width: 45px; height: 24px; font-size: 12px;"
                                                       value="{{ $soal->pivot->bobot_custom ?? $soal->bobot }}"
                                                       min="1" data-soal-id="{{ $soal->id }}"
                                                       onchange="updateBobot(this)">
                                            </td>
                                            <td>
                                                <form action="{{ route('admin.tes.hapus-soal', [$tes, $soal->id]) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0" title="Hapus">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer py-1 small text-muted">
                            <i class="bi bi-grip-vertical"></i> Drag untuk ubah urutan
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <p class="mb-0">Belum ada soal terpilih</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Soal Tersedia -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header py-2">
                    <span class="fw-semibold">Soal Tersedia</span>
                </div>
                <div class="card-body py-2 border-bottom">
                    <form method="GET" class="row g-1">
                        <div class="col-4">
                            <select name="topik_id" class="form-select form-select-sm">
                                <option value="">Semua Topik</option>
                                @foreach($topikList as $topik)
                                    <option value="{{ $topik->id }}" {{ ($filter['topik_id'] ?? '') == $topik->id ? 'selected' : '' }}>
                                        {{ $topik->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <select name="tipe" class="form-select form-select-sm">
                                <option value="">Tipe</option>
                                <option value="pilgan" {{ ($filter['tipe'] ?? '') === 'pilgan' ? 'selected' : '' }}>Pilgan</option>
                                <option value="ganda" {{ ($filter['tipe'] ?? '') === 'ganda' ? 'selected' : '' }}>Ganda</option>
                                <option value="esai" {{ ($filter['tipe'] ?? '') === 'esai' ? 'selected' : '' }}>Esai</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari..." value="{{ $filter['cari'] ?? '' }}">
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
                        </div>
                        @if($tampilkanSemua ?? false)
                            <input type="hidden" name="tampilkan_semua" value="1">
                        @endif
                    </form>
                </div>
                
                @if($soalTersedia->count() > 0)
                    <!-- Batch Actions untuk Soal Tersedia -->
                    <div class="card-body py-2 border-bottom batch-actions-tersedia" id="batchActionsTersedia">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><span id="selectedTersediaCount">0</span> dipilih</span>
                            <form action="{{ route('admin.tes.tambah-soal-batch', $tes) }}" method="POST" id="formTambahBatch">
                                @csrf
                                <input type="hidden" name="soal_ids" id="selectedTersediaIds">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-plus"></i> Tambah Terpilih
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card-body py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            @if($tampilkanSemua ?? false)
                                <small class="text-muted">{{ $soalTersedia->count() }} soal</small>
                            @else
                                <small class="text-muted">{{ $soalTersedia->count() }}/{{ $soalTersedia->total() }} soal</small>
                            @endif
                            <div class="d-flex gap-1">
                                @if(!($tampilkanSemua ?? false))
                                    <a href="{{ request()->fullUrlWithQuery(['tampilkan_semua' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-list"></i> Tampilkan Semua
                                    </a>
                                @else
                                    <a href="{{ request()->fullUrlWithQuery(['tampilkan_semua' => null]) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-grid"></i> Per Halaman
                                    </a>
                                @endif
                                <form action="{{ route('admin.tes.tambah-soal-batch', $tes) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="soal_ids" value="{{ $soalTersedia->pluck('id')->implode(',') }}">
                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-plus"></i> Tambah Semua ({{ $soalTersedia->count() }})
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
                
                <div class="card-body p-0">
                    @if($soalTersedia->count() > 0)
                        <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" class="form-check-input" id="checkAllTersedia" title="Pilih Semua">
                                        </th>
                                        <th>Pertanyaan</th>
                                        <th class="text-center" width="45">Bobot</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($soalTersedia as $soal)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input soal-tersedia-check" value="{{ $soal->id }}">
                                            </td>
                                            <td>
                                                <span title="{{ strip_tags($soal->pertanyaan) }}">{!! Str::limit(strip_tags($soal->pertanyaan), 40) !!}</span>
                                                <br><small class="text-muted">{{ $soal->topik?->nama ?? '-' }}</small>
                                            </td>
                                            <td class="text-center">{{ $soal->bobot }}</td>
                                            <td>
                                                <form action="{{ route('admin.tes.tambah-soal', $tes) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="soal_id" value="{{ $soal->id }}">
                                                    <button type="submit" class="btn btn-link text-success p-0" title="Tambah">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <p class="mb-0">Tidak ada soal tersedia</p>
                        </div>
                    @endif
                </div>
                @if(!($tampilkanSemua ?? false) && $soalTersedia->hasPages())
                    <div class="card-footer py-1">
                        {{ $soalTersedia->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="formUpdateUrutan" action="{{ route('admin.tes.update-urutan-soal', $tes) }}" method="POST" style="display:none;">@csrf</form>
<form id="formUpdateBobot" method="POST" style="display:none;">@csrf</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const soalList = document.getElementById('soalTerpilihList');
    const checkAll = document.getElementById('checkAll');
    const batchActions = document.getElementById('batchActions');
    
    // === SOAL TERPILIH ===
    // Sortable
    if (soalList) {
        new Sortable(soalList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function() {
                updateUrutanNumbers();
                saveUrutan();
            }
        });
    }
    
    // Check All - Soal Terpilih
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('.soal-check').forEach(cb => cb.checked = this.checked);
            updateBatchActions();
        });
    }
    
    // Individual checkboxes - Soal Terpilih
    document.querySelectorAll('.soal-check').forEach(cb => {
        cb.addEventListener('change', updateBatchActions);
    });
    
    function updateBatchActions() {
        const checked = document.querySelectorAll('.soal-check:checked');
        const count = checked.length;
        
        if (batchActions) {
            batchActions.classList.toggle('show', count > 0);
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('selectedSoalIds').value = Array.from(checked).map(cb => cb.value).join(',');
        }
        
        // Update checkAll state
        const allChecks = document.querySelectorAll('.soal-check');
        if (checkAll) {
            checkAll.checked = count === allChecks.length && count > 0;
            checkAll.indeterminate = count > 0 && count < allChecks.length;
        }
    }
    
    function updateUrutanNumbers() {
        soalList.querySelectorAll('.soal-row').forEach((row, i) => {
            row.querySelector('.urutan-number').textContent = i + 1;
        });
    }
    
    function saveUrutan() {
        const form = document.getElementById('formUpdateUrutan');
        form.querySelectorAll('input[name^="urutan"]').forEach(i => i.remove());
        
        soalList.querySelectorAll('.soal-row').forEach((row, i) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `urutan[${row.dataset.id}]`;
            input.value = i + 1;
            form.appendChild(input);
        });
        
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
    }
    
    // === SOAL TERSEDIA ===
    const checkAllTersedia = document.getElementById('checkAllTersedia');
    const batchActionsTersedia = document.getElementById('batchActionsTersedia');
    
    // Check All - Soal Tersedia
    if (checkAllTersedia) {
        checkAllTersedia.addEventListener('change', function() {
            document.querySelectorAll('.soal-tersedia-check').forEach(cb => cb.checked = this.checked);
            updateBatchActionsTersedia();
        });
    }
    
    // Individual checkboxes - Soal Tersedia
    document.querySelectorAll('.soal-tersedia-check').forEach(cb => {
        cb.addEventListener('change', updateBatchActionsTersedia);
    });
    
    function updateBatchActionsTersedia() {
        const checked = document.querySelectorAll('.soal-tersedia-check:checked');
        const count = checked.length;
        
        if (batchActionsTersedia) {
            batchActionsTersedia.classList.toggle('show', count > 0);
            document.getElementById('selectedTersediaCount').textContent = count;
            document.getElementById('selectedTersediaIds').value = Array.from(checked).map(cb => cb.value).join(',');
        }
        
        // Update checkAllTersedia state
        const allChecks = document.querySelectorAll('.soal-tersedia-check');
        if (checkAllTersedia) {
            checkAllTersedia.checked = count === allChecks.length && count > 0;
            checkAllTersedia.indeterminate = count > 0 && count < allChecks.length;
        }
    }
});

function updateBobot(input) {
    const soalId = input.dataset.soalId;
    const form = document.getElementById('formUpdateBobot');
    form.action = '{{ url("admin/tes/{$tes->id}/soal") }}/' + soalId + '/bobot';
    
    const bobotInput = form.querySelector('input[name="bobot_custom"]');
    if (bobotInput) bobotInput.remove();
    
    const newInput = document.createElement('input');
    newInput.type = 'hidden';
    newInput.name = 'bobot_custom';
    newInput.value = input.value;
    form.appendChild(newInput);
    
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    });
}
</script>
@endpush
