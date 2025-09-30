<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage users');
    }

    public function index()
    {
        $users = User::query()
            ->with('roles')
            ->withCount('activityLogs as logs_count')
            ->addSelect([
                'last_login_at' => ActivityLog::select('created_at')
                    ->whereColumn('activity_logs.user_id', 'users.id')
                    ->where('event', 'login')
                    ->orderByDesc('created_at')->limit(1),
                'last_login_ip' => ActivityLog::select('ip_address')
                    ->whereColumn('activity_logs.user_id', 'users.id')
                    ->where('event', 'login')
                    ->orderByDesc('created_at')->limit(1),
                'last_device' => ActivityLog::select('device_name')
                    ->whereColumn('activity_logs.user_id', 'users.id')
                    ->where('event', 'login')
                    ->orderByDesc('created_at')->limit(1),
            ])
            ->get();

        return view('user.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('user.create', compact('roles'));
    }

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

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil ditambahkan!');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('user.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Memperbarui informasi pengguna.
     */
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

        $user->save();

        // Sinkron peran
        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil diperbarui!');
    }

    public function editRoles(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('user.edit-roles', compact('user', 'roles', 'userRoles'));
    }

    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles'   => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('user.index')->with('success', 'Peran pengguna berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        if (auth()->check() && auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();
        return redirect()->route('user.index')->with('success', 'Pengguna berhasil dihapus!');
    }

    public function logs(User $user, Request $request)
    {
        // Ambil 50 log terakhir; bisa ditambah pencarian sederhana dari query 'q'
        $logs = ActivityLog::where('user_id', $user->id)
            ->when($request->filled('q'), function($q) use ($request) {
                $term = $request->q;
                $q->where(function($w) use ($term){
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

        // Kembalikan partial HTML (bukan full layout) untuk dimasukkan ke modal
        return view('user.partials.logs', compact('user','logs','lastLogin'));
    }
}
