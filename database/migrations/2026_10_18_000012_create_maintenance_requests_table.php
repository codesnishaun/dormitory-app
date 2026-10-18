<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->foreignId('room_assignment_id')
                  ->nullable()
                  ->constrained('room_assignments')
                  ->nullOnDelete();

            $table->string('title');
            $table->string('category')->default('Other');
            $table->string('priority')->default('Low');
            $table->text('description');
            $table->string('status')->default('Pending');
            $table->text('admin_remarks')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};