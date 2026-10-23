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
        Schema::create('electricity_bills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('rate_per_kwh', 10, 4);
            $table->decimal('total_kwh', 12, 3);
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('finalized_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at');
            $table->timestamps();

            $table->unique(
                ['room_id', 'period_start', 'period_end'],
                'electricity_bills_room_period_unique',
            );
            $table->index(['room_id', 'period_end']);
        });

        Schema::create('electricity_bill_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('electricity_bill_id')->constrained()->restrictOnDelete();
            $table->foreignId('meter_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('opening_reading_id')->constrained('meter_readings')->restrictOnDelete();
            $table->foreignId('closing_reading_id')->constrained('meter_readings')->restrictOnDelete();
            $table->date('segment_start');
            $table->date('segment_end');
            $table->decimal('opening_reading', 12, 3);
            $table->decimal('closing_reading', 12, 3);
            $table->decimal('consumption_kwh', 12, 3);
            $table->timestamps();

            $table->unique(
                ['electricity_bill_id', 'meter_assignment_id'],
                'electricity_bill_segments_bill_assignment_unique',
            );
        });

        Schema::create('electricity_bill_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('electricity_bill_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('tenant_name');
            $table->unsignedInteger('chargeable_days');
            $table->decimal('allocated_amount', 10, 2);
            $table->foreignId('transaction_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['electricity_bill_id', 'room_assignment_id'],
                'electricity_bill_allocations_bill_assignment_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_bill_allocations');
        Schema::dropIfExists('electricity_bill_segments');
        Schema::dropIfExists('electricity_bills');
    }
};
