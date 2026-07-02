<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\TokenGlobal;
use App\Services\TokenGlobalService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TokenGlobalController extends Controller
{
    public function __construct(
        private TokenGlobalService $tokenGlobalService
    ) {}

    /**
     * Tampilkan daftar token global
     */
    public function index(Request $request): View
    {
        $filter = $request->only(['cari', 'aktif']);
        $daftarToken = $this->tokenGlobalService->ambilSemua($filter);
        $statistik = $this->tokenGlobalService->ambilStatistik();
        $daftarTes = Tes::where('status', 'aktif')->orderBy('nama')->get();

        return view('admin.tes.token-global', compact('daftarToken', 'statistik', 'daftarTes', 'filter'));
    }

    /**
     * Simpan token global baru
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'tes_ids' => 'nullable|array',
            'tes_ids.*' => 'exists:tes,id',
            'untuk_semua_tes' => 'nullable|boolean',
        ], [
            'selesai.after_or_equal' => 'Waktu selesai harus setelah atau sama dengan waktu mulai',
        ]);

        $data = $request->only(['nama', 'keterangan', 'mulai', 'selesai']);
        $data['aktif'] = true;

        // Jika untuk semua tes, gunakan method khusus
        if ($request->boolean('untuk_semua_tes')) {
            $token = $this->tokenGlobalService->buatUntukSemuaTes($data);
        } else {
            $data['tes_ids'] = $request->input('tes_ids', []);
            $token = $this->tokenGlobalService->buat($data);
        }

        return redirect()->route('admin.token-global.index')
            ->with('success', "Token global berhasil dibuat: {$token->kode}");
    }

    /**
     * Update token global
     */
    public function update(Request $request, TokenGlobal $tokenGlobal): RedirectResponse
    {
        $request->validate([
            'nama' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'tes_ids' => 'nullable|array',
            'tes_ids.*' => 'exists:tes,id',
            'aktif' => 'nullable|boolean',
        ]);

        $data = $request->only(['nama', 'keterangan', 'mulai', 'selesai']);
        $data['aktif'] = $request->boolean('aktif', $tokenGlobal->aktif);
        $data['tes_ids'] = $request->input('tes_ids', []);

        $this->tokenGlobalService->update($tokenGlobal, $data);

        return redirect()->route('admin.token-global.index')
            ->with('success', 'Token global berhasil diperbarui');
    }

    /**
     * Toggle status aktif token
     */
    public function toggleAktif(TokenGlobal $tokenGlobal): RedirectResponse
    {
        $this->tokenGlobalService->toggleAktif($tokenGlobal);
        $status = $tokenGlobal->fresh()->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.token-global.index')
            ->with('success', "Token {$tokenGlobal->kode} berhasil {$status}");
    }

    /**
     * Hapus token global
     */
    public function destroy(TokenGlobal $tokenGlobal): RedirectResponse
    {
        $kode = $tokenGlobal->kode;
        $this->tokenGlobalService->hapus($tokenGlobal);

        return redirect()->route('admin.token-global.index')
            ->with('success', "Token {$kode} berhasil dihapus");
    }

    /**
     * Lihat log penggunaan token
     */
    public function logs(TokenGlobal $tokenGlobal): View
    {
        $logs = $tokenGlobal->logs()
            ->with('peserta')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.tes.token-global-logs', compact('tokenGlobal', 'logs'));
    }
}
