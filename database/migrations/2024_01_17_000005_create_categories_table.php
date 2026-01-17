<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('category_groups')->onDelete('cascade');
            $table->string('name');
            $table->string('icon')->nullable(); // emoji
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->json('projections')->nullable(); // { "1": 400, "2": 425, "3": 450 }
            $table->integer('sort_order')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
