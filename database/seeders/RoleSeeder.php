<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $owner = Role::create(['name' => 'owner']);
        $adminPusat = Role::create(['name' => 'admin_pusat']);
        $manajerCabang = Role::create(['name' => 'manajer_cabang']);
        $kasir = Role::create(['name' => 'kasir']);
        $karyawanDapur = Role::create(['name' => 'karyawan_dapur']);

        // Create permissions
        $permissions = [
            // Branch management
            'branch.view',
            'branch.create',
            'branch.edit',
            'branch.delete',

            // Employee management
            'employee.view',
            'employee.create',
            'employee.edit',
            'employee.delete',

            // Inventory management
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',

            // Menu management
            'menu.view',
            'menu.create',
            'menu.edit',
            'menu.delete',

            // Order/Transaction
            'order.view',
            'order.create',
            'order.edit',
            'order.delete',

            // Reports
            'report.sales',
            'report.profit',
            'report.inventory',
            'report.financial',

            // Financial
            'financial.view',
            'financial.create',
            'financial.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to owner
        $owner->givePermissionTo(Permission::all());

        // Assign permissions to admin pusat
        $adminPusat->givePermissionTo([
            'branch.view', 'branch.create', 'branch.edit',
            'employee.view', 'employee.create', 'employee.edit',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'menu.view', 'menu.create', 'menu.edit',
            'order.view',
            'report.sales', 'report.profit', 'report.inventory', 'report.financial',
            'financial.view', 'financial.create', 'financial.edit',
        ]);

        // Assign permissions to manajer cabang
        $manajerCabang->givePermissionTo([
            'employee.view', 'employee.create', 'employee.edit',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'menu.view',
            'order.view', 'order.create', 'order.edit',
            'report.sales', 'report.inventory',
            'financial.view',
        ]);

        // Assign permissions to kasir
        $kasir->givePermissionTo([
            'menu.view',
            'order.view', 'order.create', 'order.edit',
        ]);

        // Assign permissions to karyawan dapur
        $karyawanDapur->givePermissionTo([
            'order.view',
        ]);

        // Create test user (owner)
        $testUser = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password'),
        ]);
        $testUser->assignRole('owner');
    }
}
