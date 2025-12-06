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
        if (!Schema::hasTable('email_log')) {
            Schema::create('email_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('email_id', 100)->nullable();
                $table->string('template_name', 50)->nullable();
                $table->string('subject', 80)->nullable();
                $table->json('data')->nullable();
                $table->tinyInteger('status')->default(0)->comment('0:pending,1:sent,2:hold,3:failed');

                $table->unsignedBigInteger('created_by')->default(0);
                $table->timestamps();

                $table->index(['id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_log');
    }
};
