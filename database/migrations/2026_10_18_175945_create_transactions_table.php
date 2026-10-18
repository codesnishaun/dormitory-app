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
        Schema::create('transactions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')->constrained()->restrictOnDelete();

        $table->string('type');                          
        $table->string('description')->nullable();      
        $table->decimal('amount', 10, 2);
        $table->date('transaction_date');
        $table->date('due_date')->nullable();            
        $table->string('payment_method')->nullable();   
        $table->text('notes')->nullable();

        $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

        $table->timestamps();

        $table->index(['user_id', 'transaction_date']);
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
