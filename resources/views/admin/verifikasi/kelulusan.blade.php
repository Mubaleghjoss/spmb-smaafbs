@extends('layouts.admin')

@section('title', 'Verifikasi Kelulusan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-mortarboard me-2"></i>Verifikasi Kelulusan</h4>
            <p class="text-muted mb-0">Kelola status kelulusan peserta SPMB</p>
        </div>
        <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap7" class="btn btn-outline-secondary">
            <i class="bi bi-gear me-1"></i>Pengaturan Kelulusan
        </a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(empty($skGelombang))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Belum ada SK gelombang. Tambahkan file SK di <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap7" class="alert-link">Pengaturan SPMB - Kelulusan</a> sebelum meluluskan peserta.
        </div>
    @endif
    
    {{-- Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                            <i class="bi bi-hourglass-split text-warning fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['menunggu'] }}</h3>
                            <small class="text-muted">Menunggu Keputusan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['lulus'] }}</h3>
                            <small class="text-muted">Lulus / Diterima</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-25 p-3 me-3">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['tidak_lulus'] }}</h3>
                            <small class="text-muted">Tidak Lulus</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Aksi Batch --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="form-check me-3">
                    <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll(this)">
                    <label class="form-check-label" for="selectAll">Pilih Semua</label>
                </div>
                <span class="text-muted me-3" id="selectedCount">0 dipilih</span>
                <select class="form-select form-select-sm" id="batchSkGelombang" style="max-width:220px" {{ empty($skGelombang) ? 'disabled' : '' }}>
                    <option value="">Pilih SK Gelombang</option>
                    @foreach($skGelombang as $sk)
                    <option value="{{ $sk['id'] }}">{{ $sk['nama'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-success btn-sm" onclick="luluskanBatch()" id="btnLuluskanBatch" disabled>
                    <i class="bi bi-check-circle me-1"></i>Luluskan Terpilih
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="tidakLulusBatch()" id="btnTidakLulusBatch" disabled>
                    <i class="bi bi-x-circle me-1"></i>Tidak Luluskan Terpilih
                </button>
                <div class="ms-auto">
                    <form action="{{ route('admin.verifikasi.kelulusan.luluskan-semua') }}" method="POST" class="d-inline" 
                          onsubmit="return confirm('Yakin ingin meluluskan SEMUA peserta yang menunggu?')">
                        @csrf
                        <div class="d-inline-flex gap-2">
                            <select name="sk_gelombang_kelulusan" class="form-select form-select-sm" style="width:190px" required {{ empty($skGelombang) ? 'disabled' : '' }}>
                                <option value="">Pilih SK</option>
                                @foreach($skGelombang as $sk)
                                <option value="{{ $sk['id'] }}">{{ $sk['nama'] }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm" {{ empty($skGelombang) ? 'disabled' : '' }}>
                            <i class="bi bi-check-all me-1"></i>Luluskan Semua ({{ $statistik['menunggu'] }})
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    {{-- Daftar Peserta --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Daftar Peserta</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" class="form-check-input" disabled></th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Asal Sekolah</th>
                            <th>Tahap</th>
                            <th>Status Kelulusan</th>
                            <th>SK Gelombang</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peserta as $p)
                        @php
                            $tahap = $p->tahapanSpmb?->tahap_saat_ini ?? 1;
                            $statusKelulusan = $p->tahapanSpmb?->status_kelulusan ?? 'menunggu';
                            $tahap7Selesai = $p->tahapanSpmb?->tahap_7_selesai ?? false;
                            $selectedSk = $p->tahapanSpmb?->sk_gelombang_kelulusan;
                            $selectedSkLabel = $selectedSk && isset($skGelombangById[$selectedSk]) ? $skGelombangById[$selectedSk]['nama'] : null;
                        @endphp
                        <tr>
                            <td>
                                @if($statusKelulusan === 'menunggu' || $statusKelulusan === null)
                                <input type="checkbox" class="form-check-input peserta-checkbox" 
                                       value="{{ $p->id }}" onchange="updateSelectedCount()">
                                @endif
                            </td>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>{{ $p->nama }}</td>
                            <td>{{ $p->formulirSpmb?->asal_sekolah ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $tahap == 7 ? 'success' : 'primary' }}">
                                    Tahap {{ $tahap }}
                                </span>
                            </td>
                            <td>
                                @if($tahap7Selesai && $statusKelulusan === 'lulus')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lulus</span>
                                @elseif($statusKelulusan === 'tidak_lulus')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Lulus</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                @endif
                            </td>
                            <td>
                                @if($selectedSkLabel)
                                    <span class="badge bg-info text-dark"><i class="bi bi-file-earmark-pdf me-1"></i>{{ $selectedSkLabel }}</span>
                                @else
                                    <span class="text-muted small">Belum dipilih</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($statusKelulusan !== 'lulus' || !$tahap7Selesai)
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    <form action="{{ route('admin.verifikasi.kelulusan.luluskan', $p) }}" method="POST" class="d-inline-flex gap-1">
                                        @csrf
                                        <select name="sk_gelombang_kelulusan" class="form-select form-select-sm" style="width:150px" required {{ empty($skGelombang) ? 'disabled' : '' }}>
                                            <option value="">Pilih SK</option>
                                            @foreach($skGelombang as $sk)
                                            <option value="{{ $sk['id'] }}" {{ $selectedSk === $sk['id'] ? 'selected' : '' }}>{{ $sk['nama'] }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm" title="Luluskan" {{ empty($skGelombang) ? 'disabled' : '' }}>
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    @if($statusKelulusan !== 'tidak_lulus')
                                    <form action="{{ route('admin.verifikasi.kelulusan.tidak-lulus', $p) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Tidak Lulus">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                @else
                                <form action="{{ route('admin.verifikasi.kelulusan.luluskan', $p) }}" method="POST" class="d-inline-flex gap-1 justify-content-center">
                                    @csrf
                                    <select name="sk_gelombang_kelulusan" class="form-select form-select-sm" style="width:150px" required {{ empty($skGelombang) ? 'disabled' : '' }}>
                                        <option value="">Pilih SK</option>
                                        @foreach($skGelombang as $sk)
                                        <option value="{{ $sk['id'] }}" {{ $selectedSk === $sk['id'] ? 'selected' : '' }}>{{ $sk['nama'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Update SK Gelombang" {{ empty($skGelombang) ? 'disabled' : '' }}>
                                        <i class="bi bi-save"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada peserta yang perlu diverifikasi kelulusannya
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($peserta->hasPages())
        <div class="card-footer bg-white">
            {{ $peserta->links() }}
        </div>
        @endif
    </div>
    
    {{-- Info Pengaturan Keterangan --}}
    @if(!empty($pengaturanKelulusan['keterangan_lulus']) || !empty($pengaturanKelulusan['keterangan_tidak_lulus']))
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Keterangan yang Ditampilkan ke Peserta</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @if(!empty($pengaturanKelulusan['keterangan_lulus']))
                <div class="col-md-6">
                    <div class="alert alert-success mb-0">
                        <h6 class="alert-heading"><i class="bi bi-check-circle me-1"></i>Jika Lulus:</h6>
                        <p class="mb-0 small">{!! nl2br(e($pengaturanKelulusan['keterangan_lulus'])) !!}</p>
                    </div>
                </div>
                @endif
                @if(!empty($pengaturanKelulusan['keterangan_tidak_lulus']))
                <div class="col-md-6">
                    <div class="alert alert-danger mb-0">
                        <h6 class="alert-heading"><i class="bi bi-x-circle me-1"></i>Jika Tidak Lulus:</h6>
                        <p class="mb-0 small">{!! nl2br(e($pengaturanKelulusan['keterangan_tidak_lulus'])) !!}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Form tersembunyi untuk batch action --}}
<form id="formLuluskanBatch" action="{{ route('admin.verifikasi.kelulusan.luluskan-batch') }}" method="POST">
    @csrf
    <input type="hidden" name="peserta_ids" id="pesertaIdsLulus">
    <input type="hidden" name="sk_gelombang_kelulusan" id="skGelombangLulus">
</form>
<form id="formTidakLulusBatch" action="{{ route('admin.verifikasi.kelulusan.tidak-lulus-batch') }}" method="POST">
    @csrf
    <input type="hidden" name="peserta_ids" id="pesertaIdsTidakLulus">
</form>
@endsection

@push('scripts')
<script>
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.peserta-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.peserta-checkbox:checked');
    const count = checked.length;
    document.getElementById('selectedCount').textContent = count + ' dipilih';
    document.getElementById('btnLuluskanBatch').disabled = count === 0;
    document.getElementById('btnTidakLulusBatch').disabled = count === 0;
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.peserta-checkbox');
    document.getElementById('selectAll').checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
}

function luluskanBatch() {
    const checked = document.querySelectorAll('.peserta-checkbox:checked');
    if (checked.length === 0) {
        alert('Pilih peserta terlebih dahulu');
        return;
    }

    const skGelombang = document.getElementById('batchSkGelombang').value;
    if (!skGelombang) {
        alert('Pilih SK gelombang terlebih dahulu');
        return;
    }
    
    if (!confirm('Yakin ingin meluluskan ' + checked.length + ' peserta terpilih?')) return;
    
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    document.getElementById('pesertaIdsLulus').value = ids;
    document.getElementById('skGelombangLulus').value = skGelombang;
    document.getElementById('formLuluskanBatch').submit();
}

function tidakLulusBatch() {
    const checked = document.querySelectorAll('.peserta-checkbox:checked');
    if (checked.length === 0) {
        alert('Pilih peserta terlebih dahulu');
        return;
    }
    
    if (!confirm('Yakin ingin menandai ' + checked.length + ' peserta sebagai TIDAK LULUS?')) return;
    
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    document.getElementById('pesertaIdsTidakLulus').value = ids;
    document.getElementById('formTidakLulusBatch').submit();
}
</script>
@endpush
