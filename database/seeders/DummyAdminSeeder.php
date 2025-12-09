<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = SUPER_ADMIN_EMAIL;
        $mobile_number = 8275624113;
        $user_code = 'SADM000';
        $role_id = 1;

        $userInfo = User::where('mobile_number', $mobile_number)
            ->where('email', $email)
            ->where('user_code', $user_code)
            ->first();

        if (!$userInfo) {

            $first_name  = "sajid";
            $last_name  = "jalal";
            $name = $first_name . " " . $last_name;

            $userInfo = User::create([
                'role_id' => 1,
                'full_name' => $name,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'mobile_number' => $mobile_number,
                'user_code' => $user_code,
                'password' => Hash::make('your-default-password'),
                'status' => 1,
                'created_at' => now(),
            ]);
        }
    }
}
