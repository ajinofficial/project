<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityNotifier;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function index(Request $request): View
    {
        return view('role-permissions.index', [
            'roles' => User::ROLES,
            'menus' => RolePermission::configurableMenus(),
            'permissions' => RolePermission::normalize($request->user()->tenant->role_permissions ?? []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validMenus = RolePermission::configurableMenuKeys();
        $currentPermissions = RolePermission::normalize($request->user()->tenant->role_permissions ?? []);
        $roleKeys = collect(array_keys(User::ROLES))
            ->map(fn ($role) => RolePermission::roleKey((int) $role))
            ->all();
        $roleLabels = collect(User::ROLES)
            ->mapWithKeys(fn ($label, $role) => [RolePermission::roleKey((int) $role) => $label])
            ->all();

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', Rule::in($validMenus)],
        ]);

        $permissions = RolePermission::defaults();

        foreach ($roleKeys as $roleKey) {
            $permissions[$roleKey] = array_values(array_intersect(
                $data['permissions'][$roleKey] ?? [],
                $validMenus
            ));
        }

        $permissions['owner'] = $validMenus;
        $changedRoles = collect($roleKeys)
            ->filter(function ($roleKey) use ($currentPermissions, $permissions) {
                $current = $currentPermissions[$roleKey] ?? [];
                $next = $permissions[$roleKey] ?? [];

                sort($current);
                sort($next);

                return $current !== $next;
            })
            ->map(fn ($roleKey) => $roleLabels[$roleKey] ?? ucfirst(str_replace('_', ' ', $roleKey)))
            ->values()
            ->all();

        $request->user()->tenant->update(['role_permissions' => $permissions]);

        $roleMessage = count($changedRoles) > 0
            ? 'updated permissions for '.$this->formatRoleList($changedRoles).'.'
            : 'reviewed role permissions with no changes.';

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'role_permissions_updated',
            'Role permissions updated',
            $request->user()->name.' '.$roleMessage
        );

        return back()->with('status', 'Role permissions updated.');
    }

    private function formatRoleList(array $roles): string
    {
        return match (count($roles)) {
            0 => 'no roles',
            1 => $roles[0],
            2 => $roles[0].' and '.$roles[1],
            default => implode(', ', array_slice($roles, 0, -1)).', and '.end($roles),
        };
    }
}
