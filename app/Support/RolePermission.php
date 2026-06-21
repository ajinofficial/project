<?php

namespace App\Support;

use App\Models\User;

class RolePermission
{
    public const MENUS = [
        'dashboard' => ['label' => 'Dashboard', 'abbr' => 'DB', 'route' => 'dashboard', 'active' => 'dashboard'],
        'inventory' => ['label' => 'Inventory', 'abbr' => 'IN', 'route' => 'products.index', 'active' => 'products.*'],
        'billing' => ['label' => 'Billing', 'abbr' => 'BL', 'route' => 'sales.index', 'active' => 'sales.*'],
        'purchases' => ['label' => 'Purchases', 'abbr' => 'PO', 'route' => 'purchases.index', 'active' => 'purchases.*'],
        'suppliers' => ['label' => 'Suppliers', 'abbr' => 'SU', 'route' => 'suppliers.index', 'active' => 'suppliers.*'],
        'customers' => ['label' => 'Customers', 'abbr' => 'CU', 'route' => 'customers.index', 'active' => 'customers.*'],
        'returns' => ['label' => 'Returns', 'abbr' => 'RT', 'route' => 'returns.index', 'active' => 'returns.*'],
        'reports' => ['label' => 'Reports', 'abbr' => 'RP', 'route' => 'reports.index', 'active' => 'reports.*'],
        'setup' => ['label' => 'Store Setup', 'abbr' => 'ST', 'route' => 'setup.index', 'active' => 'setup.*'],
        'role_permissions' => ['label' => 'Role Permissions', 'abbr' => 'RP', 'route' => 'role-permissions.index', 'active' => 'role-permissions.*'],
    ];

    public static function roleKey(int $role): string
    {
        return match ($role) {
            User::ROLE_MANAGER => 'manager',
            User::ROLE_SALES_STAFF => 'sales_staff',
            User::ROLE_WAREHOUSE_STAFF => 'warehouse_staff',
            User::ROLE_ACCOUNTANT => 'accountant',
            default => 'owner',
        };
    }

    public static function allMenuKeys(): array
    {
        return array_keys(self::MENUS);
    }

    public static function defaults(): array
    {
        return [
            'owner' => self::allMenuKeys(),
            'manager' => ['dashboard', 'inventory', 'billing', 'purchases', 'suppliers', 'customers', 'returns', 'reports'],
            'sales_staff' => ['dashboard', 'billing', 'customers', 'returns'],
            'warehouse_staff' => ['dashboard', 'inventory', 'purchases', 'suppliers', 'returns'],
            'accountant' => ['dashboard', 'customers', 'reports'],
        ];
    }

    public static function normalize(?array $permissions): array
    {
        $defaults = self::defaults();
        $validMenus = self::allMenuKeys();

        foreach ($defaults as $role => $roleDefaults) {
            $values = $permissions[$role] ?? $roleDefaults;
            $defaults[$role] = array_values(array_intersect((array) $values, $validMenus));
        }

        $defaults['owner'] = $validMenus;

        return $defaults;
    }

    public static function canAccess(User $user, string $menu): bool
    {
        if ((int) $user->role === User::ROLE_OWNER) {
            return true;
        }

        $permissions = self::normalize($user->tenant?->role_permissions ?? []);
        $roleKey = self::roleKey((int) $user->role);

        return in_array($menu, $permissions[$roleKey] ?? [], true);
    }

    public static function firstAccessibleRoute(User $user): string
    {
        foreach (self::MENUS as $menuKey => $menu) {
            if (self::canAccess($user, $menuKey)) {
                return $menu['route'];
            }
        }

        return 'notifications.index';
    }
}
