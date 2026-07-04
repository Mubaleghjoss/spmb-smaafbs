@extends('layouts.admin')

@section('title', 'Tahun Ajaran & Gelombang')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Tahun Ajaran & Gelombang</h1>
            <p class="text-muted mb-0">Atur pilihan periode yang tersedia pada formulir pendaftaran peserta.</p>
        </div>
        <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap1" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Data belum dapat disimpan.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Tambah Tahun Ajaran</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.pengaturan.spmb.periode.tahun.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">Tahun Ajaran</label>
                    <input type="text" name="nama" class="form-control" placeholder="2027-2028" value="{{ old('nama') }}" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="aktif" value="0">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" id="tahunAktifBaru" checked>
                        <label class="form-check-label" for="tahunAktifBaru">Aktif</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="default" value="0">
                        <input class="form-check-input" type="checkbox" name="default" value="1" id="tahunDefaultBaru">
                        <label class="form-check-label" for="tahunDefaultBaru">Jadikan default</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-plus-lg me-1"></i>Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>

    @forelse($tahunAjaran as $tahun)
        <div class="card mb-4">
            <div class="card-header bg-white d-flex flex-column flex-lg-row justify-content-between gap-3">
                <form method="POST"
                      action="{{ route('admin.pengaturan.spmb.periode.tahun.update', $tahun) }}"
                      class="row g-2 align-items-center flex-grow-1">
                    @csrf
                    @method('PUT')
                    <div class="col-md-4">
                        <input type="text" name="nama" class="form-control fw-bold" value="{{ $tahun->nama }}" required>
                    </div>
                    <div class="col-auto">
                        <input type="hidden" name="aktif" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="aktif" value="1"
                                   id="aktifTahun{{ $tahun->id }}" {{ $tahun->aktif ? 'checked' : '' }}>
                            <label class="form-check-label" for="aktifTahun{{ $tahun->id }}">Aktif</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <input type="hidden" name="default" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="default" value="1"
                                   id="defaultTahun{{ $tahun->id }}" {{ $tahun->default ? 'checked' : '' }}>
                            <label class="form-check-label" for="defaultTahun{{ $tahun->id }}">Default</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary" type="submit" title="Simpan tahun ajaran">
                            <i class="bi bi-check-lg me-1"></i>Simpan
                        </button>
                    </div>
                </form>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary">{{ $tahun->peserta_count }} peserta</span>
                    <form method="POST" action="{{ route('admin.pengaturan.spmb.periode.tahun.destroy', $tahun) }}"
                          onsubmit="return confirm('Hapus tahun ajaran ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus tahun ajaran">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama Gelombang</th>
                                <th>Tanggal Buka</th>
                                <th>Tanggal Tutup</th>
                                <th>Aktif</th>
                                <th>Peserta</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tahun->gelombangPendaftaran as $gelombang)
                                <tr>
                                    <td colspan="6">
                                        <form method="POST"
                                              action="{{ route('admin.pengaturan.spmb.periode.gelombang.update', [$tahun, $gelombang]) }}"
                                              class="row g-2 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-3">
                                                <input type="text" name="nama" class="form-control form-control-sm"
                                                       value="{{ $gelombang->nama }}" required aria-label="Nama gelombang">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="date" name="tanggal_buka" class="form-control form-control-sm"
                                                       value="{{ $gelombang->tanggal_buka?->format('Y-m-d') }}" aria-label="Tanggal buka">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="date" name="tanggal_tutup" class="form-control form-control-sm"
                                                       value="{{ $gelombang->tanggal_tutup?->format('Y-m-d') }}" aria-label="Tanggal tutup">
                                            </div>
                                            <div class="col-md-1">
                                                <input type="hidden" name="aktif" value="0">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="aktif" value="1"
                                                           title="Gelombang aktif" {{ $gelombang->aktif ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="badge bg-light text-dark">{{ $gelombang->peserta_count }}</span>
                                            </div>
                                            <div class="col-md-3 text-md-end">
                                                <button class="btn btn-sm btn-outline-primary" type="submit" title="Simpan gelombang">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" type="button"
                                                        onclick="if (confirm('Hapus gelombang ini?')) document.getElementById('hapusGelombang{{ $gelombang->id }}').submit()"
                                                        title="Hapus gelombang">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </form>
                                        <form id="hapusGelombang{{ $gelombang->id }}"
                                              method="POST"
                                              action="{{ route('admin.pengaturan.spmb.periode.gelombang.destroy', [$tahun, $gelombang]) }}"
                                              class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Belum ada gelombang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST"
                      action="{{ route('admin.pengaturan.spmb.periode.gelombang.store', $tahun) }}"
                      class="row g-2 align-items-end bg-light p-3 rounded">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small">Gelombang Baru</label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Gelombang 1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Tanggal Buka</label>
                        <input type="date" name="tanggal_buka" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Tanggal Tutup</label>
                        <input type="date" name="tanggal_tutup" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1">
                        <input type="hidden" name="aktif" value="0">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="aktif" value="1"
                                   id="gelombangAktif{{ $tahun->id }}" checked>
                            <label class="form-check-label small" for="gelombangAktif{{ $tahun->id }}">Aktif</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-success w-100" type="submit">
                            <i class="bi bi-plus-lg me-1"></i>Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Belum ada tahun ajaran.</div>
    @endforelse
</div>
@endsection
