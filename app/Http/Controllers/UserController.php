<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        // Keamanan: hanya yang punya izin "manage users"
        $this->middleware('permission:manage users');

        // Catatan:
        // Kita TIDAK pakai middleware 'activity' di sini agar tidak dobel log.
        // Logging dilakukan manual di method terkait dengan deskripsi yang kaya.
    }

    /** LIST */
    public function index()
    {
        // Cukup ambil user + roles (log akan diload via modal AJAX)
        $users = User::with('roles')->get();
        return view('user.index', compact('users'));
    }

    /** FORM CREATE */
    public function create()
    {
        $roles = Role::all();
        return view('user.create', compact('roles'));
    }

    /** SIMPAN USER BARU */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email:rfc,dns|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles'    => 'nullable|array',
            'roles.*'  => 'exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        // LOG: create_user (detail)
        ActivityLog::create([
            'user_id'     => auth()->id(), // aktor
            'event'       => 'create_user',
            'description' => sprintf(
                'Membuat user: %s (ID %d) dengan roles: [%s]',
                $user->name ?? $user->username,
                $user->id,
                implode(', ', $validated['roles'] ?? [])
            ),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil ditambahkan!');
    }

    /** FORM EDIT */
    public function edit(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('user.edit', compact('user', 'roles', 'userRoles'));
    }

    /** UPDATE DATA USER (termasuk opsi update roles dari form umum) */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'roles'    => 'nullable|array',
            'roles.*'  => 'exists:roles,name',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Jika form ini juga mengubah roles, catat perubahan roles dengan detail
        if (array_key_exists('roles', $validated)) {
            $old = $user->roles->pluck('name')->sort()->values()->toArray();
            $user->save();
            $user->syncRoles($validated['roles'] ?? []);
            $new = $user->roles->pluck('name')->sort()->values()->toArray();

            if ($old !== $new) {
                ActivityLog::create([
                    'user_id'     => auth()->id(),
                    'event'       => 'update_user_roles',
                    'description' => sprintf(
                        'Ubah roles user %s (ID %d): [%s] -> [%s]',
                        $user->name ?? $user->username,
                        $user->id,
                        implode(', ', $old),
                        implode(', ', $new)
                    ),
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                ]);
            }
        } else {
            // Hanya update profil (tanpa roles)
            $user->save();
        }

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil diperbarui!');
    }

    /** FORM EDIT ROLES KHUSUS */
    public function editRoles(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('user.edit-roles', compact('user', 'roles', 'userRoles'));
    }

    /** UPDATE ROLES KHUSUS */
    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles'    => 'nullable|array',
            'roles.*'  => 'exists:roles,name',
        ]);

        $old = $user->roles->pluck('name')->sort()->values()->toArray();
        $user->syncRoles($validated['roles'] ?? []);
        $new = $user->roles->pluck('name')->sort()->values()->toArray();

        // LOG: update_user_roles (hanya kalau berubah)
        if ($old !== $new) {
            ActivityLog::create([
                'user_id'     => auth()->id(), // aktor yang mengubah
                'event'       => 'update_user_roles',
                'description' => sprintf(
                    'Ubah roles user %s (ID %d): [%s] -> [%s]',
                    $user->name ?? $user->username,
                    $user->id,
                    implode(', ', $old),
                    implode(', ', $new)
                ),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);
        }

        return redirect()->route('user.index')->with('success', 'Peran pengguna berhasil diperbarui!');
    }

    /** HAPUS */
    public function destroy(User $user)
    {
        if (auth()->check() && auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();
        return redirect()->route('user.index')->with('success', 'Pengguna berhasil dihapus!');
    }

    /** AJAX: PARTIAL TABEL LOG UNTUK MODAL DI INDEX */
    public function logs(User $user, Request $request)
    {
        $logs = ActivityLog::where('user_id', $user->id)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($w) use ($term) {
                    $w->where('event', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%")
                      ->orWhere('ip_address', 'like', "%{$term}%")
                      ->orWhere('device_name', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->limit(50)
            ->get();

        $lastLogin = ActivityLog::where('user_id', $user->id)
            ->where('event', 'login')
            ->latest()
            ->first();

        return view('user.partials.logs', compact('user', 'logs', 'lastLogin'));
    }
}
