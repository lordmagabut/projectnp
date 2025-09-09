<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage permissions'); // Pastikan Anda membuat permission 'manage permissions'
    }

    /**
     * Menampilkan daftar semua izin.
     */
    public function index()
    {
        $permissions = Permission::all();
        return view('permissions.index', compact('permissions'));
    }

    /**
     * Menampilkan formulir untuk membuat izin baru.
     */
    public function create()
    {
        return view('permissions.create');
    }

    /**
     * Menyimpan izin baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->route('permissions.index')->with('success', 'Izin berhasil ditambahkan!');
    }

    /**
     * Menampilkan formulir untuk mengedit izin yang sudah ada.
     */
    public function edit(Permission $permission) // Menggunakan Route Model Binding
    {
        return view('permissions.edit', compact('permission'));
    }

    /**
     * Memperbarui izin di database.
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->route('permissions.index')->with('success', 'Izin berhasil diperbarui!');
    }

    /**
     * Menghapus izin dari database.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Izin berhasil dihapus!');
    }
}