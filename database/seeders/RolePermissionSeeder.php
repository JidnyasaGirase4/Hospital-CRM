<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * module => extra actions beyond the standard view/create/update/delete set.
     * Modules not yet built (later phases) still get their permission rows now
     * so role assignments don't need to change as each phase lands.
     */
    private const MODULES = [
        'users' => ['manage-roles'],
        'roles' => [],
        'patients' => ['view-360'],
        'appointments' => ['check-in', 'cancel', 'reschedule'],
        'opd' => [],
        'consultations' => [],
        'diagnoses' => [],
        'prescriptions' => [],
        'pharmacy' => ['dispense', 'purchase', 'return'],
        'laboratory' => ['collect-sample', 'process', 'approve-result'],
        'radiology' => [],
        'ipd' => ['admit', 'discharge'],
        'beds' => ['allocate'],
        'nursing' => [],
        'emergency' => [],
        'ot' => [],
        'billing' => [],
        'payments' => ['refund'],
        'insurance' => ['approve-claim'],
        'inventory' => [],
        'suppliers' => [],
        'documents' => ['download'],
        'notifications' => [],
        'reports' => [],
        'audit-logs' => [],
    ];

    private const STANDARD_ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * role slug => module permission grants. 'all' grants every action for
     * that module; an array grants only the listed actions.
     */
    private const ROLE_GRANTS = [
        Role::SUPER_ADMIN => '*',
        Role::HOSPITAL_ADMIN => '*',
        Role::DOCTOR => [
            'patients' => ['view', 'view-360'],
            'appointments' => ['view', 'update', 'check-in', 'reschedule'],
            'opd' => 'all',
            'consultations' => 'all',
            'diagnoses' => 'all',
            'prescriptions' => 'all',
            'laboratory' => ['view', 'create'],
            'radiology' => ['view', 'create'],
            'ipd' => ['view', 'admit', 'discharge'],
            'nursing' => ['view'],
            'emergency' => ['view', 'create', 'update'],
            'documents' => ['view', 'create', 'download'],
        ],
        Role::NURSE => [
            'patients' => ['view'],
            'nursing' => 'all',
            'ipd' => ['view'],
            'beds' => ['view'],
            'emergency' => ['view', 'create', 'update'],
            'documents' => ['view'],
        ],
        Role::RECEPTIONIST => [
            'patients' => ['view', 'create', 'update'],
            'appointments' => 'all',
            'opd' => ['view', 'create'],
            'billing' => ['view', 'create'],
            'payments' => ['view', 'create'],
            'documents' => ['view', 'create'],
        ],
        Role::BILLING_STAFF => [
            'billing' => 'all',
            'payments' => 'all',
            'insurance' => ['view', 'create', 'update', 'approve-claim'],
            'patients' => ['view'],
        ],
        Role::PHARMACIST => [
            'pharmacy' => 'all',
            'prescriptions' => ['view'],
            'inventory' => ['view'],
            'suppliers' => ['view'],
            'patients' => ['view'],
        ],
        Role::LAB_TECHNICIAN => [
            'laboratory' => 'all',
            'patients' => ['view'],
        ],
        Role::RADIOLOGY_STAFF => [
            'radiology' => 'all',
            'patients' => ['view'],
        ],
        Role::OT_STAFF => [
            'ot' => 'all',
            'ipd' => ['view'],
            'patients' => ['view'],
        ],
        Role::ACCOUNTANT => [
            'billing' => ['view'],
            'payments' => ['view'],
            'insurance' => ['view'],
            'reports' => ['view'],
        ],
        Role::INVENTORY_MANAGER => [
            'inventory' => 'all',
            'suppliers' => 'all',
        ],
    ];

    public function run(): void
    {
        $permissionIdsByModule = $this->seedPermissions();
        $roles = $this->seedRoles();

        foreach (self::ROLE_GRANTS as $roleSlug => $grants) {
            $role = $roles[$roleSlug];

            if ($grants === '*') {
                $role->permissions()->sync(collect($permissionIdsByModule)->flatten()->all());

                continue;
            }

            $permissionIds = [];

            foreach ($grants as $module => $actions) {
                $modulePermissions = $permissionIdsByModule[$module] ?? [];

                if ($actions === 'all') {
                    $permissionIds = [...$permissionIds, ...array_values($modulePermissions)];

                    continue;
                }

                foreach ($actions as $action) {
                    if (isset($modulePermissions[$action])) {
                        $permissionIds[] = $modulePermissions[$action];
                    }
                }
            }

            $role->permissions()->sync($permissionIds);
        }
    }

    /**
     * @return array<string, array<string, int>> module => [action => permission_id]
     */
    private function seedPermissions(): array
    {
        $map = [];

        foreach (self::MODULES as $module => $extraActions) {
            $actions = [...self::STANDARD_ACTIONS, ...$extraActions];

            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";

                $permission = Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => Str::headline("{$action} {$module}"),
                        'module' => $module,
                        'description' => "Allows the {$action} action on the {$module} module.",
                    ]
                );

                $map[$module][$action] = $permission->id;
            }
        }

        return $map;
    }

    /**
     * @return array<string, Role>
     */
    private function seedRoles(): array
    {
        $definitions = [
            Role::SUPER_ADMIN => ['name' => 'Super Admin', 'is_system' => true, 'description' => 'Full, unrestricted access to every module.'],
            Role::HOSPITAL_ADMIN => ['name' => 'Hospital Admin', 'is_system' => true, 'description' => 'Administers hospital operations and staff.'],
            Role::DOCTOR => ['name' => 'Doctor', 'is_system' => true, 'description' => 'Consults patients, orders investigations, prescribes.'],
            Role::NURSE => ['name' => 'Nurse', 'is_system' => true, 'description' => 'Manages nursing care, vitals, and ward duties.'],
            Role::RECEPTIONIST => ['name' => 'Receptionist', 'is_system' => true, 'description' => 'Registers patients and manages appointments.'],
            Role::BILLING_STAFF => ['name' => 'Billing Staff', 'is_system' => true, 'description' => 'Manages billing, payments and refunds.'],
            Role::PHARMACIST => ['name' => 'Pharmacist', 'is_system' => true, 'description' => 'Manages pharmacy stock and dispensing.'],
            Role::LAB_TECHNICIAN => ['name' => 'Lab Technician', 'is_system' => true, 'description' => 'Processes lab orders and results.'],
            Role::RADIOLOGY_STAFF => ['name' => 'Radiology Staff', 'is_system' => true, 'description' => 'Processes radiology orders and reports.'],
            Role::OT_STAFF => ['name' => 'OT Staff', 'is_system' => true, 'description' => 'Manages OT/surgery scheduling and records.'],
            Role::ACCOUNTANT => ['name' => 'Accountant', 'is_system' => true, 'description' => 'Views financial records and reports.'],
            Role::INVENTORY_MANAGER => ['name' => 'Inventory Manager', 'is_system' => true, 'description' => 'Manages inventory and suppliers.'],
        ];

        $roles = [];

        foreach ($definitions as $slug => $attributes) {
            $roles[$slug] = Role::query()->updateOrCreate(['slug' => $slug], $attributes);
        }

        return $roles;
    }
}
