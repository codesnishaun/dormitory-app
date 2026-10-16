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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            // Applicant information
            $table->string('applicant_name');
            $table->string('email')->index();
            $table->string('phone', 30);
            $table->text('message')->nullable();

            // Room preference, not a confirmed reservation
            $table->foreignId('preferred_room_id')
                ->nullable()
                ->constrained('rooms')
                ->restrictOnDelete();

            $table->date('move_in_date');

            // Application review
            $table->string('status')->default('pending');
            $table->text('admin_remarks')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            // Linked after an account is created, if applicable
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
