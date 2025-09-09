<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

        public function __construct()
        {
            $this->middleware('permission:manage users'); // Lindungi akses
        }

        public function index()
        {
            $users = User::all(); // Ambil semua pengguna
            return view('user.index', compact('users')); // <<< Teruskan variabel $roles ke view
        }

        public function create()
        {
            $roles = Role::all(); // Ambil semua peran untuk ditampilkan di formulir
            return view('user.create', compact('roles'));
        }
    
        public function store(Request $request)
        {
            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed', // Password minimal 8 karakter dan harus dikonfirmasi
                'roles' => 'nullable|array', // Bisa jadi array kosong jika tidak ada peran yang dipilih
                'roles.*' => 'exists:roles,name', // Pastikan setiap peran yang dipilih ada di tabel roles
            ]);
    
            $user = User::create([
                'username' => $request->name,
                'password' => Hash::make($request->password), // Hash password sebelum disimpan!
            ]);
    
            // Berikan peran yang dipilih ke user baru
            if ($request->has('roles')) {
                $user->assignRole($request->roles);
            }
    
            return redirect()->route('user.index')->with('success', 'Pengguna berhasil ditambahkan!');
        }

        public function edit(User $user)
        {
            $roles = Role::all(); // Ambil semua peran
            $userRoles = $user->roles->pluck('name')->toArray(); // Peran yang sudah dimiliki user
            return view('user.edit', compact('user', 'roles', 'userRoles'));
        }
    
        /**
         * Memperbarui informasi pengguna di database.
         * Menggunakan Route Model Binding: Laravel akan otomatis menemukan User berdasarkan {user} di URL.
         */
        public function update(Request $request, User $user)
        {
            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'nullable|string|min:8|confirmed', // Password opsional, hanya jika ingin mengubahnya
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name',
            ]);
    
            $user->username = $request->username;
    
            if ($request->filled('password')) { // Hanya update password jika diisi
                $user->password = Hash::make($request->password);
            }
    
            $user->save();
    
            // Sinkronkan peran pengguna
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            } else {
                $user->syncRoles([]); // Hapus semua peran jika tidak ada yang dipilih
            }
    
            return redirect()->route('user.index')->with('success', 'Pengguna berhasil diperbarui!');
        }

        public function editRoles(User $user)

        {
            $roles = Role::all(); // Semua peran yang tersedia
            $userRoles = $user->roles->pluck('name')->toArray(); // Peran yang sudah dimiliki pengguna
            return view('user.edit-roles', compact('user', 'roles', 'userRoles'));
        }

        public function updateRoles(Request $request, User $user)
        {
            $request->validate([
                'roles' => 'nullable|array',
            ]);

            $user->syncRoles($request->roles); // Sinkronkan peran pengguna

            return redirect()->route('user.index')->with('success', 'Peran pengguna berhasil diperbarui!');
        }

        public function destroy(User $user) // Menggunakan Route Model Binding
        {
            // Pencegahan: Jangan biarkan user menghapus dirinya sendiri jika sedang login
            if (auth()->check() && auth()->user()->id === $user->id) {
                return redirect()->back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
            }
    
            $user->delete();
            return redirect()->route('user.index')->with('success', 'Pengguna berhasil dihapus!');
        }

}
