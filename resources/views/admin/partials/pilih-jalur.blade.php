{{--
    Kartu pengarah: muncul di halaman kerja admin ketika jalur pendaftaran
    BELUM dipilih. Menggantikan daftar peserta (bukan menumpuknya) agar data
    siswa baru & pindahan tidak pernah tampil bercampur.

    Cara pakai di dalam @section('content'):
        @if($jalurBelumMemilih ?? false)
            @include('admin.partials.pilih-jalur', ['konteks' => 'daftar peserta'])
        @else
            ... isi halaman ...
        @endif
--}}
@php $konteks = $konteks ?? 'halaman ini'; @endphp

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body text-center p-4 p-md-5">
                    <div class="mb-3">
                        <i class="bi bi-signpost-2 text-warning" style="font-size:3rem"></i>
                    </div>

                    <h4 class="mb-2">Pilih Jalur Pendaftaran</h4>
                    <p class="text-muted mb-4">
                        Data <strong>Siswa Baru</strong> dan <strong>Siswa Pindahan</strong> dikelola terpisah,
                        jadi {{ $konteks }} perlu satu jalur yang dipilih lebih dulu.
                        Pilihan ini diingat sampai Anda mengubahnya lewat tombol jalur di atas.
                    </p>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <form action="{{ route('admin.jalur-aktif.ganti') }}" method="POST">
                                @csrf
                                <input type="hidden" name="jenis_pendaftaran" value="siswa_baru">
                                <button type="submit" class="btn btn-outline-success w-100 py-4 h-100">
                                    <i class="bi bi-person-plus d-block mb-2" style="font-size:2rem"></i>
                                    <span class="fw-semibold d-block">1. Siswa Baru</span>
                                    <small class="text-muted">Kelas 10</small>
                                </button>
                            </form>
                        </div>
                        <div class="col-12 col-sm-6">
                            <form action="{{ route('admin.jalur-aktif.ganti') }}" method="POST">
                                @csrf
                                <input type="hidden" name="jenis_pendaftaran" value="pindahan">
                                <button type="submit" class="btn btn-outline-primary w-100 py-4 h-100">
                                    <i class="bi bi-arrow-left-right d-block mb-2" style="font-size:2rem"></i>
                                    <span class="fw-semibold d-block">2. Siswa Pindahan</span>
                                    <small class="text-muted">Kelas 10 &amp; 11</small>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-0 small text-start">
                        <i class="bi bi-info-circle me-1"></i>
                        Ringkasan kedua jalur sekaligus tetap dapat dilihat di
                        <a href="{{ route('admin.dashboard') }}" class="fw-semibold">Dashboard</a>.
                        Kelas 12 belum dibuka untuk jalur pindahan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
