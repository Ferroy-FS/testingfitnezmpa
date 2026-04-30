<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tabel authentications: menyimpan salt, hash, provider ──
        Schema::create('authentications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('email');
            $table->string('password_hash');
            $table->string('salt', 64);
            $table->string('provider', 20)->default('local'); // local, google, dll
            $table->boolean('is_active')->default(true);
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentications');
    }
};
