<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type', 20);
            $table->string('icon', 10)->nullable();
            $table->boolean('is_on_budget')->default(true);
            $table->decimal('starting_balance', 12, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
