<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payee_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['expense', 'income']);
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'yearly']);
            $table->date('next_date');
            $table->date('end_date')->nullable();
            $table->integer('end_after_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
