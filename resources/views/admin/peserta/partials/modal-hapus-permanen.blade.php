{{--
    Modal konfirmasi HAPUS PERMANEN peserta.

    Isi dimuat via AJAX dari route admin.peserta.pratinjau-hapus agar admin
    selalu melihat data & berkas terkini sebelum menghapus.

    Konfirmasi berlapis: centang pernyataan + ketik ulang nomor pendaftaran.
    Keduanya juga divalidasi ulang di server.
--}}
<div class="modal fade" id="modalHapusPermanen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>Hapus Permanen Peserta
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                {{-- Memuat --}}
                <div id="hpMemuat" class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="small text-muted mt-2">Memuat data peserta…</div>
                </div>

                {{-- Gagal memuat --}}
                <div id="hpGagal" class="alert alert-warning d-none mb-0"></div>

                {{-- Isi --}}
                <div id="hpIsi" class="d-none">
                    <div class="alert alert-danger">
                        <strong><i class="bi bi-shield-exclamation me-1"></i>Tindakan ini TIDAK BISA dibatalkan.</strong>
                        <div class="small mt-1">
                            Seluruh data di bawah beserta berkas di server akan hilang selamanya.
                            Kuota periode akan bertambah kembali karena peserta ini tidak lagi terhitung.
                        </div>
                    </div>

                    <div id="hpPeringatan"></div>

                    <h6 class="fw-semibold mt-3"><i class="bi bi-person-badge me-1"></i>Identitas Peserta</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-3">
                            <tbody id="hpIdentitas"></tbody>
                        </table>
                    </div>

                    <h6 class="fw-semibold"><i class="bi bi-database me-1"></i>Data yang Akan Hilang</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-3">
                            <tbody id="hpData"></tbody>
                        </table>
                    </div>

                    <h6 class="fw-semibold">
                        <i class="bi bi-paperclip me-1"></i>Berkas yang Akan Dihapus
                        <span class="badge bg-secondary" id="hpJumlahBerkas">0</span>
                        <span class="text-muted small" id="hpTotalUkuran"></span>
                    </h6>
                    <div id="hpBerkas" class="mb-3"></div>

                    <hr>

                    <form method="POST" id="hpForm">
                        @csrf
                        @method('DELETE')

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="paham" value="1" id="hpPaham">
                            <label class="form-check-label small" for="hpPaham">
                                Saya paham seluruh data dan berkas peserta ini akan hilang <strong>permanen</strong>
                                dan tidak dapat dipulihkan.
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">
                                Ketik nomor pendaftaran <code id="hpNomorContoh"></code> untuk konfirmasi:
                            </label>
                            <input type="text" name="konfirmasi_nomor" id="hpNomor" class="form-control form-control-sm"
                                   autocomplete="off" placeholder="Nomor pendaftaran">
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm" id="hpTombolHapus" disabled>
                    <i class="bi bi-trash3-fill me-1"></i>Ya, Hapus Permanen
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('modalHapusPermanen');
    if (!modalEl) return;

    const elMemuat = document.getElementById('hpMemuat');
    const elGagal = document.getElementById('hpGagal');
    const elIsi = document.getElementById('hpIsi');
    const elPaham = document.getElementById('hpPaham');
    const elNomor = document.getElementById('hpNomor');
    const elTombol = document.getElementById('hpTombolHapus');
    const elForm = document.getElementById('hpForm');

    let nomorTarget = '';

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function periksaSyarat() {
        const cocok = elNomor.value.trim() === nomorTarget && nomorTarget !== '';
        elTombol.disabled = !(elPaham.checked && cocok);
    }

    elPaham.addEventListener('change', periksaSyarat);
    elNomor.addEventListener('input', periksaSyarat);

    elTombol.addEventListener('click', function () {
        elTombol.disabled = true;
        elTombol.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menghapus…';
        elForm.submit();
    });

    // Tombol pemicu: <button data-hapus-permanen data-id="..">
    document.addEventListener('click', function (e) {
        const pemicu = e.target.closest('[data-hapus-permanen]');
        if (!pemicu) return;

        e.preventDefault();
        const id = pemicu.dataset.id;

        // Reset tampilan
        elMemuat.classList.remove('d-none');
        elGagal.classList.add('d-none');
        elGagal.textContent = '';
        elIsi.classList.add('d-none');
        elPaham.checked = false;
        elNomor.value = '';
        nomorTarget = '';
        elTombol.disabled = true;
        elTombol.innerHTML = '<i class="bi bi-trash3-fill me-1"></i>Ya, Hapus Permanen';

        elForm.action = "{{ url('admin/peserta') }}/" + id + "/hapus-permanen";

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        fetch("{{ url('admin/peserta') }}/" + id + "/pratinjau-hapus", {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async r => {
            const teks = await r.text();
            let j = null;
            try { j = JSON.parse(teks); } catch (_) { /* respons bukan JSON (mis. halaman error) */ }
            return { ok: r.ok, status: r.status, j, teks };
        })
        .then(({ ok, status, j, teks }) => {
            elMemuat.classList.add('d-none');

            if (!ok || !j) {
                // Tampilkan sebab sebenarnya, bukan pesan generik, agar mudah didiagnosis.
                let pesan = (j && j.pesan) ? j.pesan : ('Gagal memuat data peserta (HTTP ' + status + ').');
                if (!j && teks) {
                    const cocok = teks.match(/<title[^>]*>([^<]*)<\/title>/i);
                    if (cocok) pesan += ' ' + cocok[1].trim();
                }
                elGagal.textContent = pesan;
                elGagal.classList.remove('d-none');
                return;
            }

            // Identitas
            document.getElementById('hpIdentitas').innerHTML =
                Object.entries(j.identitas || {}).map(([k, v]) =>
                    `<tr><td class="text-muted" style="width:40%">${esc(k)}</td><td class="fw-semibold text-break">${esc(v)}</td></tr>`
                ).join('');

            // Data
            document.getElementById('hpData').innerHTML =
                (j.data || []).map(d =>
                    `<tr><td class="text-muted">${esc(d.label)}</td><td class="fw-semibold">${esc(d.jumlah)}</td></tr>`
                ).join('');

            // Peringatan khusus
            document.getElementById('hpPeringatan').innerHTML =
                (j.peringatan || []).map(p =>
                    `<div class="alert alert-warning py-2 small mb-2"><i class="bi bi-exclamation-triangle me-1"></i>${esc(p)}</div>`
                ).join('');

            // Berkas
            document.getElementById('hpJumlahBerkas').textContent = j.total_berkas ?? 0;
            document.getElementById('hpTotalUkuran').textContent =
                (j.total_berkas > 0) ? ' — total ' + esc(j.total_ukuran_label) : '';

            const wadahBerkas = document.getElementById('hpBerkas');
            if (!j.berkas || j.berkas.length === 0) {
                wadahBerkas.innerHTML = '<div class="text-muted small">Tidak ada berkas terunggah.</div>';
            } else {
                wadahBerkas.innerHTML = '<div class="list-group list-group-flush">' +
                    j.berkas.map(b => `
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="flex-grow-1" style="min-width:12rem">
                                    <span class="badge bg-light text-muted">${esc(b.kategori)}</span>
                                    <span class="small fw-semibold ms-1">${esc(b.label)}</span>
                                    <div class="text-muted text-break" style="font-size:.72rem">${esc(b.nama)}</div>
                                </div>
                                <div class="text-nowrap small">
                                    ${b.ada
                                        ? `<span class="text-muted me-2">${esc(b.ukuran_label)}</span>
                                           <a href="${esc(b.url)}" target="_blank" class="btn btn-sm btn-outline-secondary py-0">
                                               <i class="bi bi-eye"></i>
                                           </a>`
                                        : '<span class="badge bg-light text-danger">file tidak ditemukan</span>'}
                                </div>
                            </div>
                        </div>
                    `).join('') + '</div>';
            }

            nomorTarget = String(j.identitas?.['No. Pendaftaran'] ?? '');
            document.getElementById('hpNomorContoh').textContent = nomorTarget;
            elNomor.placeholder = nomorTarget;

            elIsi.classList.remove('d-none');
            periksaSyarat();
        })
        .catch(() => {
            elMemuat.classList.add('d-none');
            elGagal.textContent = 'Gagal menghubungi server. Coba lagi.';
            elGagal.classList.remove('d-none');
        });
    });
})();
</script>
@endpush
