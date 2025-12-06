<?php

namespace Database\Seeders;

use App\Models\RolesModel;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'reporting_role_id' => 0,
                'role_name' => 'super_admin',
                'display_name' => 'Super Admin',
                'role_prefix' => 'SADM',
                'description' => null,
                'is_admin' => 0,
                'status' => 1,
                'sequence' => 0,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => '2025-10-14 16:17:15',
                'updated_at' => '2025-10-14 16:17:15',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'reporting_role_id' => 0,
                'role_name' => 'admin',
                'display_name' => 'Admin',
                'role_prefix' => 'ADM',
                'description' => null,
                'is_admin' => 0,
                'status' => 1,
                'sequence' => 0,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => '2025-10-14 16:17:15',
                'updated_at' => '2025-10-14 16:17:15',
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'reporting_role_id' => 0,
                'role_name' => 'doctor',
                'display_name' => 'doctor',
                'role_prefix' => 'DR',
                'description' => null,
                'is_admin' => 0,
                'status' => 1,
                'sequence' => 0,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => '2025-10-14 16:17:15',
                'updated_at' => '2025-10-14 16:17:15',
                'deleted_at' => null,
            ],
        ];


        // Using upsert for updating or inserting records
        RolesModel::upsert(
            $roles,
            ['id'],
            ['role_name', 'role_prefix', 'display_name', 'description', 'status', 'is_admin', 'sequence', 'updated_at', 'deleted_at']
        );
    }
}
