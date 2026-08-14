<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\LoginActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlController extends Controller
{
    const DEFAULT_ROLES = [
        'super-admin' => ['color' => 'rose',    'description' => 'Full access to everything'],
        'admin'       => ['color' => 'violet',  'description' => 'Full admin panel access'],
        'support'     => ['color' => 'sky',     'description' => 'Ticket and user support'],
        'finance'     => ['color' => 'emerald', 'description' => 'Payments and financial data'],
        'moderator'   => ['color' => 'amber',   'description' => 'Content and order moderation'],
        'developer'   => ['color' => 'indigo',  'description' => 'API access and technical tools'],
    ];

    public function index(): Response
    {
        $roles = Role::withCount('users')->get()->map(fn ($r) => [
            'id'          => $r->id,
            'name'        => $r->name,
            'users_count' => $r->users_count,
            'color'       => self::DEFAULT_ROLES[$r->name]['color'] ?? 'slate',
            'description' => self::DEFAULT_ROLES[$r->name]['description'] ?? '',
        ]);

        $permissions = Permission::all()->groupBy(function ($p) {
            return explode('.', $p->name)[0] ?? 'other';
        })->map(fn ($group, $key) => [
            'group' => $key,
            'items' => $group->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
        ])->values();

        $users = User::with('roles')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'roles'  => $u->roles->pluck('name')->toArray(),
                'avatar' => $u->avatar,
            ]);

        $loginHistory = LoginActivity::with('user:id,name,email')
            ->latest()
            ->take(30)
            ->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'user'       => $a->user ? ['name' => $a->user->name, 'email' => $a->user->email] : null,
                'ip_address' => $a->ip_address,
                'user_agent' => $a->user_agent,
                'created_at' => $a->created_at->toISOString(),
                'status'     => $a->status ?? 'success',
            ]);

        $activityLogs = AdminActivityLog::latest()
            ->take(100)
            ->get()
            ->map(fn ($l) => [
                'id'           => $l->id,
                'admin'        => $l->admin_username,
                'action'       => $l->action,
                'description'  => $l->description,
                'subject_type' => $l->subject_type,
                'subject_id'   => $l->subject_id,
                'ip_address'   => $l->ip_address,
                'created_at'   => $l->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/AccessControl', [
            'roles'          => $roles,
            'permissions'    => $permissions,
            'users'          => $users,
            'login_history'  => $loginHistory,
            'activity_logs'  => $activityLogs,
            'default_roles'  => array_keys(self::DEFAULT_ROLES),
        ]);
    }

    public function assignRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        if ($user->hasRole($request->role)) {
            return back()->with('error', "{$user->name} already has the {$request->role} role.");
        }

        $user->assignRole($request->role);

        return back()->with('success', "Role '{$request->role}' assigned to {$user->name}.");
    }

    public function removeRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user->removeRole($request->role);

        return back()->with('success', "Role '{$request->role}' removed from {$user->name}.");
    }

    public function createRole(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ]);

        Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return back()->with('success', "Role '{$request->name}' created.");
    }

    public function deleteRole(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['super-admin', 'admin'])) {
            return back()->with('error', 'Cannot delete core admin roles.');
        }

        $role->delete();

        return back()->with('success', "Role deleted.");
    }

    public function seedDefaultRoles(): RedirectResponse
    {
        foreach (array_keys(self::DEFAULT_ROLES) as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        return back()->with('success', 'Default roles created.');
    }
}
