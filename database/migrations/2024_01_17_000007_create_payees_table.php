<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('default_category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payees');
    }
};
