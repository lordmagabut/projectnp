<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        // Sesuaikan dengan izin yang kamu pakai
        $this->middleware('permission:manage roles');
    }

    public function index()
    {
        // roles + relasi permissions untuk tampil di tabel
        $roles = Role::with('permissions')->get();

        // daftar semua permissions untuk checkbox di modal create/edit
        $permissions = Permission::all();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        // hidden input "form=create" dari modal akan otomatis ikut ke old() saat gagal
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:roles,name',
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Peran berhasil dibuat.');
    }

    public function update(Request $request, Role $role)
    {
        // hidden input "form=edit" + "role_id" juga akan ikut saat gagal
        $validated = $request->validate([
            'name'         => ['required','string','max:255', Rule::unique('roles','name')->ignore($role->id)],
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'exists:permissions,name',
        ]);

        $role->name = $validated['name'];
        $role->save();

        // sinkron permissions
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Peran berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        // Hapus peran (Spatie akan menghapus pivot role_has_permissions & model_has_roles)
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Peran berhasil dihapus.');
    }
}
