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
        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_assignment_id')
                  ->constrained('room_assignments')
                  ->restrictOnDelete();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->enum('status', [
                'pending', 
                'approved', 
                'rejected'
            ])
                ->default('pending');

            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->timestamps();
            $table->index(['room_assignment_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_requests');
    }
};
