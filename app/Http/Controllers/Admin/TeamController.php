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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|in:active,suspended',
        ]);

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
