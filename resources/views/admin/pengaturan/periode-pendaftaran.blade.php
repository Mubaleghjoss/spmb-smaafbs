@extends('layouts.admin')

@section('title', 'Tahun Ajaran & Gelombang')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Tahun Ajaran & Gelombang</h1>
            <p class="text-muted mb-0">Atur pilihan periode yang tampil dan berlaku pada halaman pendaftaran publik.</p>
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

    <div class="alert alert-info border-0 shadow-sm">
        <div class="d-flex gap-3">
            <i class="bi bi-info-circle fs-4"></i>
            <div>
                <div class="fw-semibold">Halaman /daftar mengikuti jadwal di halaman ini.</div>
                <div class="small mb-0">Tahun harus aktif, gelombang harus aktif, dan tanggal/jam saat ini harus berada di rentang buka sampai tutup. Toggle utama pendaftaran tetap diatur dari Pengaturan SPMB.</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Tambah Tahun Ajaran</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.pengaturan.spmb.periode.tahun.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <input type="text" name="nama" class="form-control" placeholder="2027-2028" value="{{ old('nama') }}" required>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Kuota Total</label>
                    <input type="number" name="kuota_peserta" class="form-control" min="0" value="{{ old('kuota_peserta') }}" placeholder="0 = tanpa batas">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Kuota Laki-laki</label>
                    <input type="number" name="kuota_laki_laki" class="form-control" min="0" value="{{ old('kuota_laki_laki') }}" placeholder="0 = tanpa batas">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Kuota Perempuan</label>
                    <input type="number" name="kuota_perempuan" class="form-control" min="0" value="{{ old('kuota_perempuan') }}" placeholder="0 = tanpa batas">
                </div>
                <div class="col-lg-1 col-md-2">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="aktif" value="0">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" id="tahunAktifBaru" checked>
                        <label class="form-check-label" for="tahunAktifBaru">Aktif</label>
                    </div>
                </div>
                <div class="col-lg-1 col-md-2">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="default" value="0">
                        <input class="form-check-input" type="checkbox" name="default" value="1" id="tahunDefaultBaru">
                        <label class="form-check-label" for="tahunDefaultBaru">Jadikan default</label>
                    </div>
                </div>
                <div class="col-lg-1 col-md-2">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @forelse($tahunAjaran as $tahun)
        @php
            $kuotaTahun = $ringkasanKuota[$tahun->id] ?? [
                'kuota_label' => 'Tidak dibatasi',
                'dalam_kuota' => 0,
                'waiting_list' => 0,
                'total' => $tahun->peserta_count,
                'sisa_label' => 'Tidak dibatasi',
                'laki_laki' => ['kuota_label' => 'Tidak dibatasi', 'total' => 0, 'dalam_kuota' => 0, 'waiting_list' => 0, 'sisa_label' => 'Tidak dibatasi'],
                'perempuan' => ['kuota_label' => 'Tidak dibatasi', 'total' => 0, 'dalam_kuota' => 0, 'waiting_list' => 0, 'sisa_label' => 'Tidak dibatasi'],
                'belum_isi_gender' => ['total' => 0, 'dalam_kuota' => 0, 'waiting_list' => 0],
            ];
        @endphp
        <div class="card mb-4">
            <div class="card-header bg-white d-flex flex-column flex-lg-row justify-content-between gap-3">
                <form method="POST"
                      action="{{ route('admin.pengaturan.spmb.periode.tahun.update', $tahun) }}"
                      class="row g-2 align-items-center flex-grow-1">
                    @csrf
                    @method('PUT')
                    <div class="col-md-3">
                        <input type="text" name="nama" class="form-control fw-bold" value="{{ $tahun->nama }}" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="kuota_peserta" class="form-control"
                               min="0" value="{{ $tahun->kuota_peserta ?? '' }}" placeholder="Total">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="kuota_laki_laki" class="form-control"
                               min="0" value="{{ $tahun->kuota_laki_laki ?? '' }}" placeholder="Laki-laki">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="kuota_perempuan" class="form-control"
                               min="0" value="{{ $tahun->kuota_perempuan ?? '' }}" placeholder="Perempuan">
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
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-primary">Kuota: {{ $kuotaTahun['kuota_label'] }}</span>
                    <span class="badge bg-success">Dalam: {{ $kuotaTahun['dalam_kuota'] }}</span>
                    <span class="badge bg-warning text-dark">Waiting: {{ $kuotaTahun['waiting_list'] }}</span>
                    <span class="badge bg-light text-dark border">Total: {{ $kuotaTahun['total'] }}</span>
                    <span class="badge bg-info text-dark">Sisa: {{ $kuotaTahun['sisa_label'] }}</span>
                    <span class="badge bg-dark">L: {{ $kuotaTahun['laki_laki']['dalam_kuota'] }}/{{ $kuotaTahun['laki_laki']['kuota_label'] }}</span>
                    <span class="badge bg-secondary">P: {{ $kuotaTahun['perempuan']['dalam_kuota'] }}/{{ $kuotaTahun['perempuan']['kuota_label'] }}</span>
                    @if(($kuotaTahun['belum_isi_gender']['total'] ?? 0) > 0)
                        <span class="badge bg-light text-dark border">JK belum isi: {{ $kuotaTahun['belum_isi_gender']['total'] }}</span>
                    @endif
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
                                <th>Jam Buka</th>
                                <th>Tanggal Tutup</th>
                                <th>Jam Tutup</th>
                                <th>Status</th>
                                <th>Aktif</th>
                                <th>Peserta</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tahun->gelombangPendaftaran as $gelombang)
                                @php
                                    $statusGelombang = $gelombang->statusPendaftaran();
                                @endphp
                                <tr>
                                    <td colspan="9">
                                        <form method="POST"
                                              action="{{ route('admin.pengaturan.spmb.periode.gelombang.update', [$tahun, $gelombang]) }}"
                                              class="row g-2 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-lg-2 col-md-4">
                                                <input type="text" name="nama" class="form-control form-control-sm"
                                                       value="{{ $gelombang->nama }}" required aria-label="Nama gelombang">
                                            </div>
                                            <div class="col-lg-2 col-md-4">
                                                <input type="date" name="tanggal_buka" class="form-control form-control-sm"
                                                       value="{{ $gelombang->tanggal_buka?->format('Y-m-d') }}" aria-label="Tanggal buka">
                                            </div>
                                            <div class="col-lg-1 col-md-4">
                                                <input type="time" name="waktu_buka" class="form-control form-control-sm"
                                                       value="{{ $gelombang->waktu_buka ? substr((string) $gelombang->waktu_buka, 0, 5) : '' }}" aria-label="Jam buka">
                                            </div>
                                            <div class="col-lg-2 col-md-4">
                                                <input type="date" name="tanggal_tutup" class="form-control form-control-sm"
                                                       value="{{ $gelombang->tanggal_tutup?->format('Y-m-d') }}" aria-label="Tanggal tutup">
                                            </div>
                                            <div class="col-lg-1 col-md-4">
                                                <input type="time" name="waktu_tutup" class="form-control form-control-sm"
                                                       value="{{ $gelombang->waktu_tutup ? substr((string) $gelombang->waktu_tutup, 0, 5) : '' }}" aria-label="Jam tutup">
                                            </div>
                                            <div class="col-lg-1 col-md-4">
                                                <span class="badge bg-{{ $statusGelombang['class'] }}">{{ $statusGelombang['label'] }}</span>
                                            </div>
                                            <div class="col-lg-1 col-md-4">
                                                <input type="hidden" name="aktif" value="0">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="aktif" value="1"
                                                           title="Gelombang aktif" {{ $gelombang->aktif ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                            <div class="col-lg-1 col-md-4">
                                                <span class="badge bg-light text-dark">{{ $gelombang->peserta_count }}</span>
                                            </div>
                                            <div class="col-lg-1 col-md-4 text-md-end">
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
                                    <td colspan="9" class="text-center text-muted py-3">Belum ada gelombang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST"
                      action="{{ route('admin.pengaturan.spmb.periode.gelombang.store', $tahun) }}"
                      class="row g-2 align-items-end bg-light p-3 rounded">
                    @csrf
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small">Gelombang Baru</label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Gelombang 1" required>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small">Tanggal Buka</label>
                        <input type="date" name="tanggal_buka" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-1 col-md-4">
                        <label class="form-label small">Jam Buka</label>
                        <input type="time" name="waktu_buka" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small">Tanggal Tutup</label>
                        <input type="date" name="tanggal_tutup" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-1 col-md-4">
                        <label class="form-label small">Jam Tutup</label>
                        <input type="time" name="waktu_tutup" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <input type="hidden" name="aktif" value="0">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="aktif" value="1"
                                   id="gelombangAktif{{ $tahun->id }}" checked>
                            <label class="form-check-label small" for="gelombangAktif{{ $tahun->id }}">Aktif</label>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
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
