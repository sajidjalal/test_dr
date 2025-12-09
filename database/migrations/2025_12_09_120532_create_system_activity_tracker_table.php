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
        Schema::create('system_activity_tracker', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->comment('type of operation i.e CRUD');
            $table->string('table_name', 80);
            $table->unsignedInteger('table_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('quote_id', 100)->nullable();
            $table->string('type_of_summary')->nullable();
            $table->string('url')->nullable();
            $table->string('description')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address', 20)->nullable();
            $table->unsignedInteger('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id', 'table_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_activity_tracker');
    }
};
