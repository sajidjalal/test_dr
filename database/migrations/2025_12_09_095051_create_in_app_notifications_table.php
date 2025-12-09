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
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('table_name', 50)->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->string('code', 100)->nullable();
            $table->string('title', 250);
            $table->longText('body')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->string('type', 100)->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=unread, 1=read');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
    }
};
