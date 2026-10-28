<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('electricity_bills', function (Blueprint $table): void {
            $table->decimal('rate_per_kwh', 10, 4)->nullable()->change();
            $table->string('cost_source')->default('meter_rate');
            $table->string('allocation_method')->default('legacy_person_days');
        });

        Schema::table('electricity_bill_allocations', function (Blueprint $table): void {
            $table->date('assignment_start_date')->nullable();
            $table->date('assignment_end_date')->nullable();
            $table->unsignedInteger('eligible_days')->nullable();
            $table->unsignedInteger('approved_absence_days')->nullable();
            $table->json('absence_snapshots')->nullable();
            $table->unsignedSmallInteger('rounding_adjustment_cents')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('electricity_bills')->whereNull('rate_per_kwh')->exists()) {
            throw new RuntimeException(
                'Electricity bills using actual provider costs cannot be rolled back until they have an explicit tariff.',
            );
        }

        Schema::table('electricity_bill_allocations', function (Blueprint $table): void {
            $table->dropColumn([
                'assignment_start_date',
                'assignment_end_date',
                'eligible_days',
                'approved_absence_days',
                'absence_snapshots',
                'rounding_adjustment_cents',
            ]);
        });

        Schema::table('electricity_bills', function (Blueprint $table): void {
            $table->decimal('rate_per_kwh', 10, 4)->nullable(false)->change();
            $table->dropColumn(['cost_source', 'allocation_method']);
        });
    }
};
