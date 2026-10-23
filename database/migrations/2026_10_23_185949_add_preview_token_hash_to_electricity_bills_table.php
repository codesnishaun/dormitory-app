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
        Schema::table('electricity_bills', function (Blueprint $table): void {
            $table->char('preview_token_hash', 64)
                ->nullable()
                ->unique('electricity_bills_preview_token_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('electricity_bills', function (Blueprint $table): void {
            $table->dropUnique('electricity_bills_preview_token_unique');
            $table->dropColumn('preview_token_hash');
        });
    }
};
