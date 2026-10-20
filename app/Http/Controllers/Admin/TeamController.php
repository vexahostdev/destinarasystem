<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    public function index()
    {
        $internalUsers = User::where('account_type', AccountType::INTERNAL)
            ->with('roles')
            ->latest()
            ->paginate(15);

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($perm) {
            $parts = explode('.', $perm->name);

            return $parts[0] ?? 'general';
        });

        return view('admin.team.index', compact('internalUsers', 'roles', 'permissions'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validated['role'] === 'super_admin' && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat menetapkan role super_admin.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        AuditLogger::log(
            action: 'create_internal_user',
            target: $user,
            description: "Staf internal {$user->name} ({$user->email}) dibuat dengan peran {$validated['role']}."
        );

        return back()->with('success', "Staf internal '{$user->name}' berhasil ditambahkan.");
    }

    public function updateUser(Request $request, User $user)
    {
        if (! $user->isInternal()) {
            return back()->with('error', 'Hanya staf internal yang dapat dikelola di modul ini.');
        }

        $currentUser = Auth::user();
        $isCurrentSuperAdmin = $currentUser->isSuperAdmin();

        // 1. Only super admin may update an account with super_admin role
        if ($user->hasRole('super_admin') && ! $isCurrentSuperAdmin) {
            abort(403, 'Hanya Super Admin yang dapat mengubah akun Super Admin.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|in:active,suspended',
        ]);

        // 2. Only super admin may assign or revoke super_admin role
        if (($validated['role'] === 'super_admin' || $user->hasRole('super_admin')) && ! $isCurrentSuperAdmin) {
            abort(403, 'Hanya Super Admin yang dapat menetapkan atau mencabut role super_admin.');
        }

        // 3. Non-super-admin staff cannot change their own role or status
        if (! $isCurrentSuperAdmin && $user->id === $currentUser->id) {
            $currentRoleName = $user->roles->first()?->name;
            if ($validated['role'] !== $currentRoleName || $validated['status'] !== $user->status) {
                abort(403, 'Staf non-super-admin tidak boleh mengubah role atau status dirinya sendiri.');
            }
        }

        // 4. Protect the last super admin from being demoted or suspended
        if ($user->hasRole('super_admin')) {
            $activeSuperAdmins = User::where('account_type', AccountType::INTERNAL)
                ->where('status', 'active')
                ->role('super_admin')
                ->count();

            if ($activeSuperAdmins <= 1) {
                if ($user->status === 'active' && $validated['status'] === 'suspended') {
                    return back()->with('error', 'Tidak dapat menonaktifkan Super Admin terakhir.');
                }
                if ($validated['role'] !== 'super_admin') {
                    return back()->with('error', 'Tidak dapat menurunkan role Super Admin terakhir.');
                }
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->status = $validated['status'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        AuditLogger::log(
            action: 'update_internal_user',
            target: $user,
            description: "Data staf internal {$user->name} diperbarui."
        );

        return back()->with('success', "Data staf '{$user->name}' berhasil diperbarui.");
    }

    public function deleteUser(User $user)
    {
        if (! $user->isInternal()) {
            return back()->with('error', 'Hanya staf internal yang dapat dikelola di modul ini.');
        }

        $currentUser = Auth::user();
        if ($user->hasRole('super_admin') && ! $currentUser->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat menghapus akun Super Admin.');
        }

        if ($user->hasRole('super_admin')) {
            $superAdminCount = User::where('account_type', AccountType::INTERNAL)
                ->role('super_admin')
                ->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus Super Admin terakhir.');
            }
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        AuditLogger::log(
            action: 'delete_internal_user',
            target: $user,
            description: "Staf internal {$name} dinonaktifkan/dihapus."
        );

        return back()->with('success', "Staf '{$name}' berhasil dinonaktifkan.");
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        AuditLogger::log(
            action: 'create_role',
            target: $role,
            description: "Peran baru {$role->name} dibuat."
        );

        return back()->with('success', "Peran '{$role->name}' berhasil dibuat.");
    }

    public function updateRole(Request $request, Role $role)
    {
        if ($role->name === 'super_admin') {
            abort(403, 'Hak akses peran super_admin tidak dapat diubah.');
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        AuditLogger::log(
            action: 'update_role_permissions',
            target: $role,
            description: "Izin akses peran {$role->name} diperbarui."
        );

        return back()->with('success', "Izin peran '{$role->name}' berhasil diperbarui.");
    }
}
