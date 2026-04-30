<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('day', 20);
            $table->string('time', 10);
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->string('level', 30)->default('All Level');
            $table->integer('slots')->default(20);
            $table->boolean('is_personal')->default(false);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
