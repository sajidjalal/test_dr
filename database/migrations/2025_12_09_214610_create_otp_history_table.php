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
        Schema::create('otp_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type', 20);
            $table->string('template_code', 50);
            $table->string('email', 80)->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->string('otp_code', 20);

            $table->dateTime('otp_time_from');
            $table->dateTime('otp_time_to');
            $table->boolean('sent_on_mobile')->default('0')->comment('0:not send, 1:send');
            $table->boolean('sent_on_email')->default('0')->comment('0:not send, 1:send');
            $table->string('generate_request_ip', 50)->nullable();
            $table->string('verify_request_ip', 50)->nullable();

            $table->boolean('is_verify')->default('0')->comment('0:not verified');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_history');
    }
};
