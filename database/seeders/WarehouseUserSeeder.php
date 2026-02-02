<?php

namespace Database\Seeders;

use App\Models\WarehouseUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WarehouseUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a warehouse manager
        WarehouseUser::create([
            'name' => 'Warehouse Manager',
            'email' => 'manager@warehouse.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 9876543210',
            'employee_id' => 'WH-MGR-001',
            'department' => 'Warehouse Operations',
            'role' => 'manager',
            'assigned_areas' => ['A', 'B', 'C', 'D', 'E'],
            'can_add_stock' => true,
            'can_adjust_stock' => true,
            'can_manage_locations' => true,
            'can_view_reports' => true,
            'can_manage_quick_delivery' => true,
            'is_active' => true,
            'created_by' => 'System',
        ]);

        // Create a warehouse supervisor
        WarehouseUser::create([
            'name' => 'John Supervisor',
            'email' => 'supervisor@warehouse.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 9876543211',
            'employee_id' => 'WH-SUP-001',
            'department' => 'Warehouse Operations',
            'role' => 'supervisor',
            'assigned_areas' => ['A', 'B', 'C'],
            'can_add_stock' => true,
            'can_adjust_stock' => true,
            'can_manage_locations' => true,
            'can_view_reports' => true,
            'can_manage_quick_delivery' => false,
            'is_active' => true,
            'created_by' => 'System',
        ]);

        // Create warehouse staff members
        WarehouseUser::create([
            'name' => 'Alice Staff',
            'email' => 'alice@warehouse.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 9876543212',
            'employee_id' => 'WH-STF-001',
            'department' => 'Warehouse Operations',
            'role' => 'staff',
            'assigned_areas' => ['A', 'B'],
            'can_add_stock' => true,
            'can_adjust_stock' => false,
            'can_manage_locations' => false,
            'can_view_reports' => false,
            'can_manage_quick_delivery' => false,
            'is_active' => true,
            'created_by' => 'System',
        ]);

        WarehouseUser::create([
            'name' => 'Bob Staff',
            'email' => 'bob@warehouse.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 9876543213',
            'employee_id' => 'WH-STF-002',
            'department' => 'Warehouse Operations',
            'role' => 'staff',
            'assigned_areas' => ['C', 'D'],
            'can_add_stock' => true,
            'can_adjust_stock' => false,
            'can_manage_locations' => false,
            'can_view_reports' => false,
            'can_manage_quick_delivery' => false,
            'is_active' => true,
            'created_by' => 'System',
        ]);

        WarehouseUser::create([
            'name' => 'Carol Staff',
            'email' => 'carol@warehouse.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 9876543214',
            'employee_id' => 'WH-STF-003',
            'department' => 'Quick Delivery',
            'role' => 'staff',
            'assigned_areas' => ['E'],
            'can_add_stock' => true,
            'can_adjust_stock' => false,
            'can_manage_locations' => false,
            'can_view_reports' => false,
            'can_manage_quick_delivery' => false,
            'is_active' => true,
            'created_by' => 'System',
        ]);

        $this->command->info('Created 5 warehouse users:');
        $this->command->info('- Manager: manager@warehouse.com (password: password123)');
        $this->command->info('- Supervisor: supervisor@warehouse.com (password: password123)');
        $this->command->info('- Staff: alice@warehouse.com (password: password123)');
        $this->command->info('- Staff: bob@warehouse.com (password: password123)');
        $this->command->info('- Staff: carol@warehouse.com (password: password123)');
    }
}
