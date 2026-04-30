<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tracking pengunjung landing page (tanpa Google Analytics) ──
        // Calon pengguna diminta izin cookies/localStorage sebelum data dikirim
        Schema::create('visitor_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64);            // ID unik per pengunjung (dari cookie)
            $table->string('session_id', 64)->nullable(); // Session identifier
            $table->string('page_url');                   // Halaman yang dikunjungi
            $table->string('referrer')->nullable();       // Dari mana pengunjung datang
            $table->string('user_agent')->nullable();     // Browser info
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6
            $table->string('device_type', 20)->nullable();// desktop, mobile, tablet
            $table->string('browser', 50)->nullable();    // Chrome, Firefox, dll
            $table->string('os', 50)->nullable();         // Windows, Android, dll
            $table->string('screen_resolution', 20)->nullable();
            $table->string('language', 10)->nullable();   // Browser language
            $table->string('country', 50)->nullable();    // Geolocation estimate
            $table->integer('time_on_page')->nullable();  // Detik di halaman
            $table->boolean('consent_given')->default(false); // Izin cookies
            $table->json('extra_data')->nullable();       // Data tambahan
            $table->timestamps();

            $table->index('visitor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_tracking');
    }
};
