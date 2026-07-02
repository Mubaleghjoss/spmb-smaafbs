<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengguna::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('peran')) {
            $query->where('peran', $request->peran);
        }

        $pengguna = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.pengguna.index', compact('pengguna'));
    }

    public function create()
    {
        return view('admin.pengguna.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pengguna,email',
            'password' => 'required|string|min:6|confirmed',
            'peran' => 'required|in:admin,operator,tim_spmb',
            'menu_akses' => 'nullable|array',
            'menu_akses.*' => 'string|in:' . implode(',', array_keys(Pengguna::daftarMenu())),
        ], [
            'nama.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.unique' => 'Email sudah digunakan',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'peran.required' => 'Peran wajib dipilih',
        ]);

        $data = [
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'peran' => $request->peran,
            'aktif' => true,
        ];

        // Set menu_akses untuk non-admin
        if ($request->peran !== 'admin') {
            $data['menu_akses'] = $request->menu_akses ?? Pengguna::menuDefault();
        }

        Pengguna::create($data);

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan');
    }

    public function edit(Pengguna $pengguna)
    {
        return view('admin.pengguna.edit', compact('pengguna'));
    }

    public function update(Request $request, Pengguna $pengguna)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('pengguna')->ignore($pengguna->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'peran' => 'required|in:admin,operator,tim_spmb',
            'aktif' => 'boolean',
            'menu_akses' => 'nullable|array',
            'menu_akses.*' => 'string|in:' . implode(',', array_keys(Pengguna::daftarMenu())),
        ]);

        $data = [
            'nama' => $request->nama,
            'email' => $request->email,
            'peran' => $request->peran,
            'aktif' => $request->boolean('aktif'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Set menu_akses untuk non-admin
        if ($request->peran !== 'admin') {
            $data['menu_akses'] = $request->menu_akses ?? Pengguna::menuDefault();
        } else {
            $data['menu_akses'] = null; // Admin punya akses semua
        }

        $pengguna->update($data);

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Pengguna berhasil diupdate');
    }

    public function destroy(Pengguna $pengguna)
    {
        // Jangan hapus diri sendiri
        if ($pengguna->id === auth('pengguna')->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri');
        }

        $pengguna->delete();

        return back()->with('success', 'Pengguna berhasil dihapus');
    }

    public function toggleAktif(Pengguna $pengguna)
    {
        // Jangan nonaktifkan diri sendiri
        if ($pengguna->id === auth('pengguna')->id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri');
        }

        $pengguna->update(['aktif' => !$pengguna->aktif]);

        $status = $pengguna->aktif ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Pengguna berhasil {$status}");
    }
}
