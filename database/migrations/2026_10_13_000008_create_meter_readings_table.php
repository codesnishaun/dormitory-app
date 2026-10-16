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
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meter_assignment_id')
                  ->constrained('meter_assignments')
                  ->restrictOnDelete();

            $table->decimal('reading_value', 12, 3);
            $table->date('reading_date');
            
            $table->foreignId('recorder_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
            $table->index(['meter_assignment_id', 'reading_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
