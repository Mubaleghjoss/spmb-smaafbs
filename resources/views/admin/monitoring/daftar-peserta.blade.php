@extends('layouts.admin')

@section('title', 'Daftar Peserta SPMB')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-people me-2"></i>Daftar Peserta SPMB</h4>
        <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Filter Tahap</label>
                    <select name="tahap" class="form-select">
                        <option value="">Semua Tahap</option>
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ ($filter['tahap'] ?? '') == $i ? 'selected' : '' }}>Tahap {{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cari</label>
                    <input type="text" name="cari" class="form-control" placeholder="Nama atau No. Pendaftaran" value="{{ $filter['cari'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Cari
                    </button>
                    <a href="{{ route('admin.monitoring.peserta') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Action --}}
    <div class="card border-0 shadow-sm mb-4" x-data="bulkAction()">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span><strong x-text="selected.length"></strong> peserta dipilih</span>
            <div x-show="selected.length > 0">
                <select x-model="bulkTahap" class="form-select form-select-sm d-inline-block w-auto">
                    @for($i = 1; $i <= 7; $i++)
                        <option value="{{ $i }}">Tahap {{ $i }}</option>
                    @endfor
                </select>
                <button @click="bulkUpdate(true)" class="btn btn-sm btn-success">
                    <i class="bi bi-check-lg"></i> Selesai
                </button>
                <button @click="bulkUpdate(false)" class="btn btn-sm btn-warning">
                    <i class="bi bi-x-lg"></i> Belum
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            @if($peserta->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Tidak ada peserta ditemukan</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" @change="toggleAll($event)"></th>
                                <th>No. Pendaftaran</th>
                                <th>Nama</th>
                                <th>Tahap</th>
                                @for($i = 1; $i <= 7; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($peserta as $p)
                            <tr>
                                <td><input type="checkbox" :value="{{ $p->id }}" x-model="selected"></td>
                                <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                                <td>{{ $p->nama }}</td>
                                <td><span class="badge bg-primary">{{ $p->tahapanSpmb?->tahap_saat_ini ?? 1 }}</span></td>
                                @for($i = 1; $i <= 7; $i++)
                                    @php $selesai = $p->tahapanSpmb?->{"tahap_{$i}_selesai"} ?? false; @endphp
                                    <td class="text-center">
                                        <form action="{{ route('admin.monitoring.update-tahapan', $p) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="tahap" value="{{ $i }}">
                                            <input type="hidden" name="selesai" value="{{ $selesai ? '0' : '1' }}">
                                            <button type="submit" class="btn btn-sm {{ $selesai ? 'btn-success' : 'btn-outline-secondary' }}" title="Klik untuk toggle">
                                                <i class="bi bi-{{ $selesai ? 'check-lg' : 'circle' }}"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endfor
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="p-3">
                    {{ $peserta->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<form id="bulkForm" action="{{ route('admin.monitoring.bulk-update') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="peserta_ids" id="bulkPesertaIds">
    <input type="hidden" name="tahap" id="bulkTahapInput">
    <input type="hidden" name="selesai" id="bulkSelesaiInput">
</form>
@endsection

@push('scripts')
<script>
function bulkAction() {
    return {
        selected: [],
        bulkTahap: 1,
        toggleAll(e) {
            if (e.target.checked) {
                this.selected = @json($peserta->pluck('id'));
            } else {
                this.selected = [];
            }
        },
        bulkUpdate(selesai) {
            if (this.selected.length === 0) return;
            if (!confirm(`Update ${this.selected.length} peserta?`)) return;
            
            document.getElementById('bulkPesertaIds').value = JSON.stringify(this.selected);
            document.getElementById('bulkTahapInput').value = this.bulkTahap;
            document.getElementById('bulkSelesaiInput').value = selesai ? '1' : '0';
            document.getElementById('bulkForm').submit();
        }
    }
}
</script>
@endpush
