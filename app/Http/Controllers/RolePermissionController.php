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
        $roleKeys = collect(array_keys(User::ROLES))
            ->map(fn ($role) => RolePermission::roleKey((int) $role))
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

        $request->user()->tenant->update(['role_permissions' => $permissions]);

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'role_permissions_updated',
            'Role permissions updated',
            $request->user()->name.' updated staff menu access.'
        );

        return back()->with('status', 'Role permissions updated.');
    }
}
