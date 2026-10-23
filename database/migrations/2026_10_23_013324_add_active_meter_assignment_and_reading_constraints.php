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
        Schema::table('meter_assignments', function (Blueprint $table): void {
            $table->unsignedBigInteger('active_room_id')
                ->nullable()
                ->virtualAs('CASE WHEN uninstalled_at IS NULL THEN room_id ELSE NULL END');
            $table->unsignedBigInteger('active_submeter_id')
                ->nullable()
                ->virtualAs('CASE WHEN uninstalled_at IS NULL THEN submeter_id ELSE NULL END');

            $table->unique('active_room_id', 'meter_assignments_active_room_unique');
            $table->unique('active_submeter_id', 'meter_assignments_active_submeter_unique');
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->unique(
                ['meter_assignment_id', 'reading_date'],
                'meter_readings_assignment_date_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->dropUnique('meter_readings_assignment_date_unique');
        });

        Schema::table('meter_assignments', function (Blueprint $table): void {
            $table->dropUnique('meter_assignments_active_room_unique');
            $table->dropUnique('meter_assignments_active_submeter_unique');
            $table->dropColumn(['active_room_id', 'active_submeter_id']);
        });
    }
};
