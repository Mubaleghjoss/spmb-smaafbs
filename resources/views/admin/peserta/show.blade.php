@extends('layouts.admin')

@section('title', 'Detail Peserta')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Peserta</h1>
        <div class="d-flex gap-2">
            @if($peserta->formulirSpmb)
            <button class="btn btn-success" onclick="copyToWA()">
                <i class="bi bi-whatsapp me-1"></i>Copy ke WA
            </button>
            @endif
            <a href="{{ route('admin.peserta.edit', $peserta) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    @if(session('sukses'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- Data Peserta (Akun) -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-person-badge me-2"></i>Data Akun</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">No. Pendaftaran</td>
                                    <td><code>{{ $peserta->nomor_pendaftaran }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama</td>
                                    <td>{{ $peserta->nama }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email</td>
                                    <td>{{ $peserta->email }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Telepon</td>
                                    <td>{{ $peserta->telepon ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Password</td>
                                    <td>
                                        @if($peserta->password_temp)
                                            <div class="input-group input-group-sm" style="max-width: 200px;">
                                                <input type="password" class="form-control form-control-sm" id="passwordField" value="{{ $peserta->password_temp }}" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Password sementara</small>
                                        @else
                                            <span class="text-muted">-</span>
                                            <small class="d-block text-muted">Password sudah diubah peserta</small>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Asal Sekolah</td>
                                    <td>{{ $peserta->asal_sekolah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Alamat</td>
                                    <td>{{ $peserta->alamat ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Grup</td>
                                    <td>
                                        @forelse($peserta->grup as $g)
                                            <span class="badge bg-secondary">{{ $g->nama }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Formulir SPMB Lengkap -->
            @if($peserta->formulirSpmb)
            @php $f = $peserta->formulirSpmb; @endphp
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Data Formulir SPMB Lengkap</h5>
                    <span class="badge bg-{{ $f->status_verifikasi === 'terverifikasi' ? 'success' : ($f->status_verifikasi === 'menunggu' ? 'warning' : ($f->status_verifikasi === 'ditolak' ? 'danger' : 'secondary')) }}">
                        {{ ucfirst($f->status_verifikasi ?? 'draft') }}
                    </span>
                </div>
                <div class="card-body" id="biodataContent">
                    {{-- Data Diri --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person me-2"></i>Data Diri</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">1. Nama Lengkap</small>
                            <strong>{{ $f->nama_lengkap ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">2. Tanggal Lahir</small>
                            <strong>{{ $f->tanggal_lahir?->format('d F Y') ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">5. Jenis Kelamin</small>
                            <strong>{{ $f->jenis_kelamin === 'L' ? 'Laki-laki' : ($f->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">3. Kota Kelahiran</small>
                            <strong>{{ $f->tempat_lahir ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">4. Provinsi Kelahiran</small>
                            <strong>{{ $f->provinsi_lahir ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Sekolah --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-building me-2"></i>Data Sekolah</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">6. Asal Sekolah SMP</small>
                            <strong>{{ $f->asal_sekolah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">7. Prestasi</small>
                            <strong>{{ $f->prestasi ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Fisik --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-heart-pulse me-2"></i>Data Fisik & Ukuran Baju</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">8. Lingkar Dada</small>
                            <strong>{{ $f->lingkar_dada ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">9. Lingkar Pinggang</small>
                            <strong>{{ $f->lingkar_pinggang ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">10. Lingkar Kepala</small>
                            <strong>{{ $f->lingkar_kepala ?? '-' }} cm</strong>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <small class="text-muted d-block">11. Panjang Celana/Rok</small>
                            <strong>{{ $f->panjang_celana ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">12. Tinggi Badan</small>
                            <strong>{{ $f->tinggi_badan ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">13. Berat Badan</small>
                            <strong>{{ $f->berat_badan ?? '-' }} kg</strong>
                        </div>
                    </div>

                    {{-- Data Tambahan --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-star me-2"></i>Data Tambahan</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">14. Hobi</small>
                            <strong>{{ $f->hobi ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">15. Cita-cita</small>
                            <strong>{{ $f->cita_cita ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Orang Tua --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-people me-2"></i>Data Orang Tua</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">16. Nama Ayah/Wali</small>
                            <strong>{{ $f->nama_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">17. Nama Ibu/Wali</small>
                            <strong>{{ $f->nama_ibu ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">18. Pekerjaan Ayah</small>
                            <strong>{{ $f->pekerjaan_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">19. Pekerjaan Ibu</small>
                            <strong>{{ $f->pekerjaan_ibu ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">20. Pendidikan Terakhir Ayah</small>
                            <strong>{{ $f->pendidikan_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">21. Pendidikan Terakhir Ibu</small>
                            <strong>{{ $f->pendidikan_ibu ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Alamat --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-geo-alt me-2"></i>Data Alamat</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">22. Kelurahan</small>
                            <strong>{{ $f->alamat_kelurahan ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">23. Kecamatan</small>
                            <strong>{{ $f->alamat_kecamatan ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">24. Kota/Kab</small>
                            <strong>{{ $f->alamat_kota ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">25. Provinsi</small>
                            <strong>{{ $f->alamat_provinsi ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Kontak --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-telephone me-2"></i>Data Kontak</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">26. No Telepon Rumah</small>
                            <strong>{{ $f->telp_rumah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">27. No HP/WA Siswa</small>
                            <strong>{{ $f->telepon ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">28. No HP/WA Ayah</small>
                            <strong>{{ $f->telepon_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">29. No HP/WA Ibu</small>
                            <strong>{{ $f->telepon_ibu ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Lainnya --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-card-list me-2"></i>Data Lainnya</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <small class="text-muted d-block">30. Jumlah Saudara</small>
                            <strong>{{ $f->jumlah_saudara ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">31. Tanggal Daftar</small>
                            <strong>{{ $f->tanggal_daftar?->format('d F Y') ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">32. NISN</small>
                            <strong>{{ $f->nisn ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">33. Nama Kelompok</small>
                            <strong>{{ $f->kelompok ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">34. Nama Desa</small>
                            <strong>{{ $f->desa ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">35. Nama Daerah</small>
                            <strong>{{ $f->daerah ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Dokumen --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-file-earmark me-2"></i>Dokumen Unggahan</h6>
                    <div class="row g-3">
                        @php
                        $dokumen = [
                            '36. KK' => $f->file_kk,
                            '37. Akta Lahir' => $f->file_akta,
                            '38. Ijazah SMP' => $f->file_ijazah,
                            '39. Kartu BPJS' => $f->file_bpjs,
                            '40. KTP Ibu' => $f->file_ktp_ibu,
                            '41. KTP Ayah' => $f->file_ktp_ayah,
                        ];
                        @endphp
                        @foreach($dokumen as $label => $file)
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ $label }}</small>
                            @if($file)
                                <a href="{{ Storage::url($file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <i class="bi bi-file-earmark-x display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Peserta belum mengisi formulir SPMB.</p>
                </div>
            </div>
            @endif

            <!-- Progres Tahapan -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-signpost-split me-2"></i>Progres Tahapan SPMB</h5>
                    <span class="badge bg-primary fs-6">Tahap {{ $peserta->tahap_saat_ini }} / 7</span>
                </div>
                <div class="card-body">
                    @php
                        $tahapan = [
                            1 => ['label' => 'Pendaftaran', 'icon' => 'bi-person-plus-fill', 'color' => '#6366f1'],
                            2 => ['label' => 'Bayar Formulir', 'icon' => 'bi-credit-card-fill', 'color' => '#f59e0b'],
                            3 => ['label' => 'Isi Formulir', 'icon' => 'bi-file-earmark-text-fill', 'color' => '#3b82f6'],
                            4 => ['label' => 'Tes Online', 'icon' => 'bi-pencil-square', 'color' => '#8b5cf6'],
                            5 => ['label' => 'Wawancara', 'icon' => 'bi-chat-dots-fill', 'color' => '#ec4899'],
                            6 => ['label' => 'Pelunasan', 'icon' => 'bi-cash-coin', 'color' => '#f97316'],
                            7 => ['label' => 'Kelulusan', 'icon' => 'bi-mortarboard-fill', 'color' => '#10b981'],
                        ];
                        $tahapSaatIni = $peserta->tahap_saat_ini;
                    @endphp

                    {{-- Step indicators --}}
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-1">
                        @foreach($tahapan as $nomor => $info)
                        @php
                            $selesai = $peserta->tahapanSpmb?->{"tahap_{$nomor}_selesai"} ?? false;
                            $aktif = $tahapSaatIni == $nomor;
                        @endphp
                        <div class="text-center flex-fill" style="min-width: 60px;">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-1
                                 {{ $selesai ? 'text-white' : ($aktif ? 'text-white' : 'text-muted bg-light border') }}"
                                 style="width: 36px; height: 36px; font-size: 0.85rem;
                                    {{ $selesai ? 'background:'.$info['color'] : ($aktif ? 'background:'.$info['color'].';box-shadow: 0 0 0 3px '.str_replace(')', ',0.3)', str_replace('rgb', 'rgba', $info['color'])).'40' : '') }}">
                                @if($selesai)
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    {{ $nomor }}
                                @endif
                            </div>
                            <div class="small {{ $aktif ? 'fw-bold' : 'text-muted' }}" style="font-size: 0.65rem; line-height: 1.1;">{{ $info['label'] }}</div>
                        </div>
                        @if($nomor < 7)
                        <div class="flex-shrink-0" style="width: 12px; height: 2px; background: {{ $selesai ? $info['color'] : '#e5e7eb' }};"></div>
                        @endif
                        @endforeach
                    </div>

                    {{-- Progress bar --}}
                    @php $progres = $peserta->tahapanSpmb?->persentase_progres ?? 0; @endphp
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: {{ $progres }}%"></div>
                    </div>
                    <small class="text-muted">{{ $progres }}% selesai</small>
                </div>
            </div>

            <!-- Riwayat Pembayaran -->
            @if($peserta->pembayaran->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Pembayaran</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($peserta->pembayaran as $bayar)
                                <tr>
                                    <td>{{ ucfirst($bayar->jenis) }}</td>
                                    <td>{{ $bayar->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($bayar->status === 'terverifikasi')
                                            <span class="badge bg-success">Terverifikasi</span>
                                        @elseif($bayar->status === 'ditolak')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning">Menunggu</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- Kontrol Tahap SPMB --}}
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-sliders me-2"></i>Kontrol Tahap</h5>
                </div>
                <div class="card-body">
                    @php
                        $tahapSaatIni = $peserta->tahap_saat_ini;
                        $tahapLabels = [
                            1 => 'Pendaftaran', 2 => 'Bayar Formulir', 3 => 'Isi Formulir',
                            4 => 'Tes Online', 5 => 'Wawancara', 6 => 'Pelunasan', 7 => 'Kelulusan'
                        ];
                        $tahapColors = [
                            1 => '#6366f1', 2 => '#f59e0b', 3 => '#3b82f6',
                            4 => '#8b5cf6', 5 => '#ec4899', 6 => '#f97316', 7 => '#10b981'
                        ];
                    @endphp

                    {{-- Current stage display --}}
                    <div class="text-center mb-3">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white mx-auto mb-2"
                             style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: 700; background: {{ $tahapColors[$tahapSaatIni] }};">
                            {{ $tahapSaatIni }}
                        </div>
                        <h6 class="mb-0">{{ $tahapLabels[$tahapSaatIni] }}</h6>
                        <small class="text-muted">Tahap saat ini</small>
                    </div>

                    {{-- Quick navigation buttons --}}
                    <div class="d-flex gap-2 mb-3">
                        @if($tahapSaatIni > 1)
                        <form action="{{ route('admin.peserta.update-tahap', $peserta) }}" method="POST" class="flex-fill"
                              onsubmit="return confirm('Mundurkan peserta ke Tahap {{ $tahapSaatIni - 1 }}: {{ $tahapLabels[$tahapSaatIni - 1] }}?')">
                            @csrf
                            <input type="hidden" name="tahap_baru" value="{{ $tahapSaatIni - 1 }}">
                            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>Mundur
                                <div class="small" style="font-size:0.65rem">Tahap {{ $tahapSaatIni - 1 }}</div>
                            </button>
                        </form>
                        @else
                        <button class="btn btn-outline-secondary w-100 btn-sm flex-fill" disabled>
                            <i class="bi bi-arrow-left me-1"></i>Mundur
                            <div class="small" style="font-size:0.65rem">—</div>
                        </button>
                        @endif

                        @if($tahapSaatIni < 7)
                        <form action="{{ route('admin.peserta.update-tahap', $peserta) }}" method="POST" class="flex-fill"
                              onsubmit="return confirm('Lanjutkan peserta ke Tahap {{ $tahapSaatIni + 1 }}: {{ $tahapLabels[$tahapSaatIni + 1] }}?')">
                            @csrf
                            <input type="hidden" name="tahap_baru" value="{{ $tahapSaatIni + 1 }}">
                            <button type="submit" class="btn btn-success w-100 btn-sm">
                                Lanjut<i class="bi bi-arrow-right ms-1"></i>
                                <div class="small" style="font-size:0.65rem">Tahap {{ $tahapSaatIni + 1 }}</div>
                            </button>
                        </form>
                        @else
                        <button class="btn btn-outline-secondary w-100 btn-sm flex-fill" disabled>
                            Lanjut<i class="bi bi-arrow-right ms-1"></i>
                            <div class="small" style="font-size:0.65rem">—</div>
                        </button>
                        @endif
                    </div>

                    <hr>

                    {{-- Direct jump --}}
                    <form action="{{ route('admin.peserta.update-tahap', $peserta) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin mengubah tahap peserta?')">
                        @csrf
                        <label class="form-label small fw-medium">Pindah langsung ke tahap:</label>
                        <div class="input-group input-group-sm">
                            <select name="tahap_baru" class="form-select form-select-sm">
                                @for($i = 1; $i <= 7; $i++)
                                <option value="{{ $i }}" {{ $tahapSaatIni == $i ? 'selected' : '' }}>
                                    Tahap {{ $i }} — {{ $tahapLabels[$i] }}
                                </option>
                                @endfor
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-arrow-right-circle"></i>
                            </button>
                        </div>
                    </form>

                    {{-- Status kelulusan --}}
                    @if($peserta->tahapanSpmb)
                    <hr>
                    <div class="text-center mb-3">
                        @if($peserta->tahapanSpmb->status_kelulusan === 'lulus')
                            <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle me-1"></i>LULUS</span>
                        @elseif($peserta->tahapanSpmb->status_kelulusan === 'tidak_lulus')
                            <span class="badge bg-danger fs-6 px-3 py-2"><i class="bi bi-x-circle me-1"></i>TIDAK LULUS</span>
                        @else
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Menunggu Keputusan</span>
                        @endif
                    </div>
                    @endif

                    <hr>

                    {{-- Link Proses per Tahap --}}
                    @php
                        $tahapRoutes = [
                            1 => ['route' => 'admin.peserta.index', 'label' => 'Daftar Peserta', 'icon' => 'bi-people-fill'],
                            2 => ['route' => 'admin.verifikasi.pembayaran-formulir', 'label' => 'Verifikasi Pembayaran', 'icon' => 'bi-credit-card-2-front'],
                            3 => ['route' => 'admin.verifikasi.formulir', 'label' => 'Verifikasi Formulir', 'icon' => 'bi-file-earmark-check'],
                            4 => ['route' => 'admin.verifikasi.hasil-tes', 'label' => 'Verifikasi Hasil Tes', 'icon' => 'bi-pencil-square'],
                            5 => ['route' => 'admin.verifikasi.wawancara', 'label' => 'Verifikasi Wawancara', 'icon' => 'bi-chat-dots'],
                            6 => ['route' => 'admin.verifikasi.pelunasan', 'label' => 'Verifikasi Pelunasan', 'icon' => 'bi-cash-coin'],
                            7 => ['route' => 'admin.verifikasi.kelulusan', 'label' => 'Verifikasi Kelulusan', 'icon' => 'bi-mortarboard'],
                        ];
                    @endphp
                    <label class="form-label small fw-medium mb-2">
                        <i class="bi bi-link-45deg me-1"></i>Link Proses Tahap:
                    </label>

                    {{-- Current stage link (highlighted) --}}
                    <a href="{{ route($tahapRoutes[$tahapSaatIni]['route']) }}" 
                       class="btn btn-primary btn-sm w-100 mb-2 d-flex align-items-center gap-2">
                        <i class="bi {{ $tahapRoutes[$tahapSaatIni]['icon'] }}"></i>
                        <span class="flex-grow-1 text-start">{{ $tahapRoutes[$tahapSaatIni]['label'] }}</span>
                        <span class="badge bg-white text-primary">Tahap {{ $tahapSaatIni }}</span>
                    </a>

                    {{-- Other stages (collapsed) --}}
                    <div class="collapse" id="allProcessLinks">
                        @foreach($tahapRoutes as $num => $info)
                            @if($num !== $tahapSaatIni)
                            <a href="{{ route($info['route']) }}" 
                               class="btn btn-outline-secondary btn-sm w-100 mb-1 d-flex align-items-center gap-2 text-start">
                                <i class="bi {{ $info['icon'] }}"></i>
                                <span class="flex-grow-1">{{ $info['label'] }}</span>
                                <small class="text-muted">T{{ $num }}</small>
                            </a>
                            @endif
                        @endforeach
                    </div>
                    <button class="btn btn-link btn-sm w-100 text-muted p-0 mt-1" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#allProcessLinks"
                            onclick="this.textContent = this.textContent.includes('Lihat') ? '▲ Sembunyikan' : '▼ Lihat semua proses'">
                        ▼ Lihat semua proses
                    </button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Terdaftar</td>
                            <td class="text-end">{{ $peserta->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Diperbarui</td>
                            <td class="text-end">{{ $peserta->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Password</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.peserta.update-password', $peserta) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="newPasswordField" 
                                       placeholder="Masukkan password baru" required minlength="6">
                                <button class="btn btn-outline-primary" type="button" onclick="toggleNewPassword()" title="Tampilkan password">
                                    <i class="bi bi-eye" id="toggleNewIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i>Simpan Password
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aksi</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('admin.verifikasi.history', $peserta) }}" class="btn btn-info text-white">
                        <i class="bi bi-clock-history me-1"></i> Histori Tahapan SPMB
                    </a>
                    <form action="{{ route('admin.peserta.reset-password', $peserta) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin reset password acak?')">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-key"></i> Reset Password Acak
                        </button>
                    </form>
                    <a href="{{ route('admin.peserta.kartu', $peserta) }}" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Cetak Kartu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden textarea for WA copy --}}
@if($peserta->formulirSpmb)
<textarea id="waText" style="position:absolute;left:-9999px;">
*📋 DATA FORMULIR SPMB*
*{{ $peserta->formulirSpmb->nama_lengkap ?? $peserta->nama }}*
━━━━━━━━━━━━━━━━━━

📌 *DATA DIRI*
1. Nama Lengkap: {{ $peserta->formulirSpmb->nama_lengkap ?? '-' }}
2. Tanggal Lahir: {{ $peserta->formulirSpmb->tanggal_lahir?->format('d F Y') ?? '-' }}
3. Kota Kelahiran: {{ $peserta->formulirSpmb->tempat_lahir ?? '-' }}
4. Provinsi Kelahiran: {{ $peserta->formulirSpmb->provinsi_lahir ?? '-' }}
5. Jenis Kelamin: {{ $peserta->formulirSpmb->jenis_kelamin === 'L' ? 'Laki-laki' : ($peserta->formulirSpmb->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}

🏫 *DATA SEKOLAH*
6. Asal Sekolah: {{ $peserta->formulirSpmb->asal_sekolah ?? '-' }}
7. Prestasi: {{ $peserta->formulirSpmb->prestasi ?? '-' }}

💪 *DATA FISIK & UKURAN BAJU*
8. Lingkar Dada: {{ $peserta->formulirSpmb->lingkar_dada ?? '-' }} cm
9. Lingkar Pinggang: {{ $peserta->formulirSpmb->lingkar_pinggang ?? '-' }} cm
10. Lingkar Kepala: {{ $peserta->formulirSpmb->lingkar_kepala ?? '-' }} cm
11. Panjang Celana/Rok: {{ $peserta->formulirSpmb->panjang_celana ?? '-' }} cm
12. Tinggi Badan: {{ $peserta->formulirSpmb->tinggi_badan ?? '-' }} cm
13. Berat Badan: {{ $peserta->formulirSpmb->berat_badan ?? '-' }} kg

⭐ *DATA TAMBAHAN*
14. Hobi: {{ $peserta->formulirSpmb->hobi ?? '-' }}
15. Cita-cita: {{ $peserta->formulirSpmb->cita_cita ?? '-' }}

👪 *DATA ORANG TUA*
16. Nama Ayah: {{ $peserta->formulirSpmb->nama_ayah ?? '-' }}
17. Nama Ibu: {{ $peserta->formulirSpmb->nama_ibu ?? '-' }}
18. Pekerjaan Ayah: {{ $peserta->formulirSpmb->pekerjaan_ayah ?? '-' }}
19. Pekerjaan Ibu: {{ $peserta->formulirSpmb->pekerjaan_ibu ?? '-' }}
20. Pendidikan Ayah: {{ $peserta->formulirSpmb->pendidikan_ayah ?? '-' }}
21. Pendidikan Ibu: {{ $peserta->formulirSpmb->pendidikan_ibu ?? '-' }}

📍 *DATA ALAMAT*
22. Kelurahan: {{ $peserta->formulirSpmb->alamat_kelurahan ?? '-' }}
23. Kecamatan: {{ $peserta->formulirSpmb->alamat_kecamatan ?? '-' }}
24. Kota/Kab: {{ $peserta->formulirSpmb->alamat_kota ?? '-' }}
25. Provinsi: {{ $peserta->formulirSpmb->alamat_provinsi ?? '-' }}

📞 *DATA KONTAK*
26. Telp Rumah: {{ $peserta->formulirSpmb->telp_rumah ?? '-' }}
27. HP/WA Siswa: {{ $peserta->formulirSpmb->telepon ?? '-' }}
28. HP/WA Ayah: {{ $peserta->formulirSpmb->telepon_ayah ?? '-' }}
29. HP/WA Ibu: {{ $peserta->formulirSpmb->telepon_ibu ?? '-' }}

📋 *DATA LAINNYA*
30. Jumlah Saudara: {{ $peserta->formulirSpmb->jumlah_saudara ?? '-' }}
31. NISN: {{ $peserta->formulirSpmb->nisn ?? '-' }}
32. Kelompok: {{ $peserta->formulirSpmb->kelompok ?? '-' }}
33. Desa: {{ $peserta->formulirSpmb->desa ?? '-' }}
34. Daerah: {{ $peserta->formulirSpmb->daerah ?? '-' }}
━━━━━━━━━━━━━━━━━━
No. Pendaftaran: {{ $peserta->nomor_pendaftaran }}
</textarea>
@endif
@endsection

@push('scripts')
<script>
function togglePassword() {
    const passwordField = document.getElementById('passwordField');
    const toggleIcon = document.getElementById('toggleIcon');
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function toggleNewPassword() {
    const passwordField = document.getElementById('newPasswordField');
    const toggleIcon = document.getElementById('toggleNewIcon');
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function copyToWA() {
    const waText = document.getElementById('waText');
    if (!waText) return;
    
    // Clean up whitespace - remove leading spaces from each line
    const text = waText.value.split('\n').map(line => line.trimStart()).join('\n').trim();
    
    navigator.clipboard.writeText(text).then(() => {
        // Show success feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Tersalin!';
        btn.classList.replace('btn-success', 'btn-outline-success');
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.replace('btn-outline-success', 'btn-success');
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        waText.style.position = 'static';
        waText.select();
        document.execCommand('copy');
        waText.style.position = 'absolute';
        alert('Data berhasil dicopy! Paste ke WhatsApp.');
    });
}
</script>
@endpush
