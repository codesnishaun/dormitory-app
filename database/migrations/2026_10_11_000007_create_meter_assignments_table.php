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
        Schema::create('meter_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submeter_id')
                  ->constrained('submeters')
                  ->restrictOnDelete();

            $table->foreignId('room_id')
                  ->constrained('rooms')
                  ->restrictOnDelete();
            
            $table->foreignId('installed_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->date('installed_at');
            $table->date('uninstalled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_assignments');
    }
};
