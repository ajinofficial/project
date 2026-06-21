<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'menus' => RolePermission::MENUS,
            'permissions' => RolePermission::normalize($request->user()->tenant->role_permissions ?? []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validMenus = RolePermission::allMenuKeys();
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

        return back()->with('status', 'Role permissions updated.');
    }
}
