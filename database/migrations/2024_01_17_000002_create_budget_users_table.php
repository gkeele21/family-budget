<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'member'])->default('member');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_users');
    }
};
