<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payee_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 12, 2); // positive = inflow, negative = outflow
            $table->enum('type', ['expense', 'income', 'transfer']);
            $table->date('date');
            $table->boolean('cleared')->default(false);
            $table->string('memo')->nullable();
            $table->unsignedBigInteger('transfer_pair_id')->nullable(); // links two sides of a transfer
            $table->foreignId('recurring_id')->nullable()->constrained('recurring_transactions')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->uuid('voice_batch_id')->nullable();
            $table->timestamps();

            $table->index(['budget_id', 'date']);
            $table->index(['account_id', 'date']);
            $table->index('voice_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
