<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // Kita akan butuh ini untuk mengelola izin yang terkait dengan peran

class RoleController extends Controller
{
    public function __construct()
    {
        // Anda bisa menambahkan middleware di sini untuk melindungi akses ke halaman ini
        // Misalnya, hanya admin yang bisa mengakses
        $this->middleware('permission:manage roles'); // Pastikan Anda membuat permission 'manage roles'
    }

    /**
     * Menampilkan daftar semua peran.
     */
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    /**
     * Menampilkan formulir untuk membuat peran baru.
     */
    public function create()
    {
        $permissions = Permission::all(); // Ambil semua izin untuk ditampilkan di formulir
        return view('roles.create', compact('permissions'));
    }

    /**
     * Menyimpan peran baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions); // Berikan izin yang dipilih ke peran

        return redirect()->route('roles.index')->with('success', 'Peran berhasil ditambahkan!');
    }

    /**
     * Menampilkan formulir untuk mengedit peran yang sudah ada.
     */
    public function edit(Role $role) // Menggunakan Route Model Binding
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray(); // Izin yang sudah dimiliki peran
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Memperbarui peran di database.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions); // Sinkronkan izin

        return redirect()->route('roles.index')->with('success', 'Peran berhasil diperbarui!');
    }

    /**
     * Menghapus peran dari database.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Peran berhasil dihapus!');
    }
}