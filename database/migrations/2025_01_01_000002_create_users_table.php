<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('full_name');
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict');
            $table->string('phone', 30)->nullable();
            $table->text('bio')->nullable()->default('');
            $table->boolean('has_trainer_cert')->default(false);
            $table->string('trainer_cert_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
