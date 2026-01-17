<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('month', 7); // YYYY-MM format
            $table->decimal('budgeted_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};
