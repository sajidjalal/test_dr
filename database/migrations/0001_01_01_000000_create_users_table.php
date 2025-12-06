<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->integer('reporting_role_id')->default(0);
            $table->string('role_name', 80);
            $table->string('display_name', 100)->nullable();
            $table->string('role_prefix', 10)->nullable();
            $table->string('description', 250)->nullable();
            $table->tinyInteger('is_admin')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('sequence')->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id')->index();
            $table->unsignedInteger('reporting_id')->index()->nullable();
            $table->string('name', 180);
            $table->string('email_id', 200);
            $table->string('mobile_number', 200);
            $table->string('api_token', 250)->nullable();
            $table->string('user_code', 50)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->unsignedTinyInteger('gender_master_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('pincode_master_id')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('address', 250)->nullable();
            $table->string('profile_pic', 150)->nullable();
            $table->rememberToken();
            $table->integer('notification_count')->nullable()->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->string('server_token')->nullable();
            $table->timestamp('server_token_expires_at')->nullable();
            $table->string('device_token')->nullable();
            $table->string('device_id')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os', 80)->nullable();
            $table->string('os_version', 80)->nullable();
            $table->string('app_version', 80)->nullable();
            $table->tinyInteger('status')->default(1)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('device_tokens');
    }
};
