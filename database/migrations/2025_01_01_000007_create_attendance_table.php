<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('check_in_time')->useCurrent();
            $table->string('attendance_type', 30)->default('gym_class');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->timestamps();

            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
