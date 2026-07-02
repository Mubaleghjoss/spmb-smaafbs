@extends('layouts.admin')

@section('title', 'Verifikasi Hasil Tes')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-journal-check me-2"></i>Verifikasi Hasil Tes</h4>
        <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        Daftar peserta yang <strong>nilai tesnya masih di bawah nilai lulus</strong>. Peserta yang nilainya sudah memenuhi nilai lulus akan otomatis lolos dan tidak masuk daftar ini.
        <ul class="mb-0 mt-2">
            <li><strong>Loloskan</strong> - Peserta dianggap lulus tes ini (harus menyelesaikan semua tes untuk lanjut tahap)</li>
            <li><strong>Ulangi Tes</strong> - Hapus sesi tes sehingga peserta dapat mengerjakan ulang</li>
        </ul>
    </div>

    @if(($jumlahLulusOtomatis ?? 0) > 0)
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle me-2"></i>
            {{ $jumlahLulusOtomatis }} hasil tes sudah memenuhi nilai lulus dan otomatis ditandai lolos.
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Menunggu Keputusan Admin</h6>
            @if(!$sesiMenunggu->isEmpty())
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success" id="btnLoloskanTerpilih" disabled data-bs-toggle="modal" data-bs-target="#modalLoloskanBatch">
                    <i class="bi bi-check-lg me-1"></i>Loloskan Terpilih (<span id="countTerpilih">0</span>)
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalLoloskanSemua">
                    <i class="bi bi-check-all me-1"></i>Loloskan Semua
                </button>
                <button type="button" class="btn btn-sm btn-warning text-dark" id="btnUlangiTerpilih" disabled data-bs-toggle="modal" data-bs-target="#modalUlangiBatch">
                    <i class="bi bi-arrow-repeat me-1"></i>Ulangi Terpilih
                </button>
            </div>
            @endif
        </div>
        <div class="card-body">
            @if($sesiMenunggu->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Tidak ada hasil tes yang menunggu verifikasi</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" class="form-check-input" id="checkAll" title="Pilih Semua">
                                </th>
                                <th>No. Pendaftaran</th>
                                <th>Nama</th>
                                <th>Tes</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Nilai Lulus</th>
                                <th class="text-center">Selisih</th>
                                <th>Waktu Selesai</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sesiMenunggu as $sesi)
                            @if(!$sesi->peserta)
                                @continue
                            @endif
                            @php
                                $selisih = $sesi->nilai - $sesi->tes->nilai_lulus;
                                $lulusNilai = $selisih >= 0;
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input sesi-checkbox" value="{{ $sesi->id }}" data-nama="{{ $sesi->peserta->nama }}">
                                </td>
                                <td><code>{{ $sesi->peserta->nomor_pendaftaran }}</code></td>
                                <td>
                                    <strong>{{ $sesi->peserta->nama }}</strong>
                                    <br><small class="text-muted">{{ $sesi->peserta->email }}</small>
                                </td>
                                <td>{{ $sesi->tes->nama }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $lulusNilai ? 'success' : 'danger' }} fs-6">{{ number_format($sesi->nilai, 1) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $sesi->tes->nilai_lulus }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="{{ $lulusNilai ? 'text-success fw-bold' : 'text-danger' }}">
                                        {{ $lulusNilai ? '+' : '' }}{{ number_format($selisih, 1) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $sesi->waktu_selesai->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('admin.hasil.detail-peserta', [$sesi->tes_id, $sesi]) }}"
                                           class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success"
                                                data-bs-toggle="modal" data-bs-target="#modalLoloskan{{ $sesi->id }}">
                                            <i class="bi bi-check-lg me-1"></i>Loloskan
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning text-dark"
                                                data-bs-toggle="modal" data-bs-target="#modalTolak{{ $sesi->id }}">
                                            <i class="bi bi-arrow-repeat me-1"></i>Ulangi
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Modal Loloskan --}}
                            <div class="modal fade" id="modalLoloskan{{ $sesi->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.verifikasi.hasil-tes.loloskan', $sesi) }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Loloskan Peserta</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    Peserta <strong>{{ $sesi->peserta->nama }}</strong> mendapat nilai <strong>{{ number_format($sesi->nilai, 1) }}</strong>
                                                    (di bawah nilai lulus {{ $sesi->tes->nilai_lulus }}).
                                                </div>
                                                <p>Apakah Anda yakin ingin meloloskan peserta ini ke tahap berikutnya?</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Catatan (opsional)</label>
                                                    <textarea class="form-control" name="catatan" rows="2" placeholder="Alasan meloloskan..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="bi bi-check-lg me-1"></i>Ya, Loloskan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Modal Ulangi Tes --}}
                            <div class="modal fade" id="modalTolak{{ $sesi->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.verifikasi.hasil-tes.tolak', $sesi) }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Ulangi Tes</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    Sesi tes akan <strong>dihapus</strong> sehingga peserta dapat mengerjakan ulang tes ini.
                                                </div>
                                                <p>Peserta: <strong>{{ $sesi->peserta->nama }}</strong></p>
                                                <p>Tes: <strong>{{ $sesi->tes->nama }}</strong></p>
                                                <p>Nilai: <span class="badge bg-danger">{{ number_format($sesi->nilai, 1) }}</span></p>
                                                <div class="mb-3">
                                                    <label class="form-label">Alasan <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="alasan" rows="2" required placeholder="Alasan mengulang tes..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="bi bi-arrow-repeat me-1"></i>Ya, Ulangi Tes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $sesiMenunggu->links() }}
            @endif
        </div>
    </div>

    {{-- Legend --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan:</strong>
                <span class="ms-3"><span class="badge bg-danger">Nilai</span> Nilai peserta (di bawah nilai lulus)</span>
                <span class="ms-3"><i class="bi bi-check-lg text-success"></i> Loloskan (dianggap lulus tes ini)</span>
                <span class="ms-3"><i class="bi bi-arrow-repeat text-warning"></i> Ulangi (hapus sesi, peserta bisa mengerjakan ulang)</span>
            </small>
        </div>
    </div>

    {{-- Modal Loloskan Batch (Terpilih) --}}
    <div class="modal fade" id="modalLoloskanBatch" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.verifikasi.hasil-tes.loloskan-batch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="sesi_ids" id="loloskanBatchIds">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Loloskan Terpilih</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Anda akan meloloskan <strong id="loloskanBatchCount">0</strong> peserta yang dipilih.
                        </div>
                        <p>Daftar peserta yang akan diloloskan:</p>
                        <ul id="loloskanBatchList" class="mb-3"></ul>
                        <div class="mb-3">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea class="form-control" name="catatan" rows="2" placeholder="Alasan meloloskan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>Ya, Loloskan Semua Terpilih
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Loloskan Semua --}}
    <div class="modal fade" id="modalLoloskanSemua" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.verifikasi.hasil-tes.loloskan-semua') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-check-all me-2"></i>Loloskan Semua</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Anda akan meloloskan <strong>SEMUA</strong> peserta yang menunggu verifikasi ({{ $sesiMenunggu->total() }} peserta).
                        </div>
                        <p>Apakah Anda yakin ingin meloloskan semua peserta?</p>
                        <div class="mb-3">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea class="form-control" name="catatan" rows="2" placeholder="Alasan meloloskan semua..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-all me-1"></i>Ya, Loloskan Semua
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Ulangi Batch (Terpilih) --}}
    <div class="modal fade" id="modalUlangiBatch" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.verifikasi.hasil-tes.ulangi-batch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="sesi_ids" id="ulangiBatchIds">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Ulangi Tes Terpilih</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Sesi tes dari <strong id="ulangiBatchCount">0</strong> peserta akan <strong>dihapus</strong> sehingga mereka dapat mengerjakan ulang.
                        </div>
                        <p>Daftar peserta yang akan diulangi tesnya:</p>
                        <ul id="ulangiBatchList" class="mb-3"></ul>
                        <div class="mb-3">
                            <label class="form-label">Alasan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="alasan" rows="2" required placeholder="Alasan mengulang tes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-repeat me-1"></i>Ya, Ulangi Semua Terpilih
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.sesi-checkbox');
    const btnLoloskanTerpilih = document.getElementById('btnLoloskanTerpilih');
    const btnUlangiTerpilih = document.getElementById('btnUlangiTerpilih');
    const countTerpilih = document.getElementById('countTerpilih');

    function updateButtons() {
        const checked = document.querySelectorAll('.sesi-checkbox:checked');
        const count = checked.length;

        countTerpilih.textContent = count;
        btnLoloskanTerpilih.disabled = count === 0;
        btnUlangiTerpilih.disabled = count === 0;

        // Update modal data
        const ids = Array.from(checked).map(cb => cb.value);
        const names = Array.from(checked).map(cb => cb.dataset.nama);

        document.getElementById('loloskanBatchIds').value = ids.join(',');
        document.getElementById('loloskanBatchCount').textContent = count;
        document.getElementById('loloskanBatchList').innerHTML = names.map(n => `<li>${n}</li>`).join('');

        document.getElementById('ulangiBatchIds').value = ids.join(',');
        document.getElementById('ulangiBatchCount').textContent = count;
        document.getElementById('ulangiBatchList').innerHTML = names.map(n => `<li>${n}</li>`).join('');

        // Update checkAll state
        checkAll.checked = count === checkboxes.length && count > 0;
        checkAll.indeterminate = count > 0 && count < checkboxes.length;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateButtons();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateButtons);
    });
});
</script>
@endpush
@endsection
