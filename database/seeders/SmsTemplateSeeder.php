<?php

namespace Database\Seeders;

use App\Models\SmsTemplateModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsTemplateModel::truncate();
        $sms_template = [
            [
                'id' => 1,
                'template_code' => 'login',
                'description' => '$otp_code is your One-Time Password (OTP) for logging into your account.This OTP is valid for next 1 minute ',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'template_code' => 'sign-up',
                'description' => '$otp_code is your One-Time Password (OTP) for logging into your account.This OTP is valid for next 1 minute ',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => null,
                'deleted_at' => null,
            ],
        ];

        SmsTemplateModel::insert($sms_template);
    }
}
