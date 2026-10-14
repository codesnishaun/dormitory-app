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
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            
            $table->foreignId('room_id')
                  ->constrained('rooms')
                  ->restrictOnDelete();

            $table->foreignId('application_id')
                  ->nullable()
                  ->unique()
                  ->constrained('applications')
                  ->restrictOnDelete();

            $table->enum('status', [
                'reserved',
                'occupied',
                'ended',
                'cancelled',
            ])->default('reserved');
            
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['room_id', 'status']);   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
    }
};
