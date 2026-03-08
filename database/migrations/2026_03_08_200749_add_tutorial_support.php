<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->boolean('is_tutorial')->default(false)->after('start_month');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('tutorial_track')->nullable()->after('ai_enabled');
            $table->string('tutorial_step')->nullable()->after('tutorial_track');
            $table->boolean('has_completed_learn_tutorial')->default(false)->after('tutorial_step');
            $table->boolean('has_completed_setup_tutorial')->default(false)->after('has_completed_learn_tutorial');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('is_tutorial');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tutorial_track',
                'tutorial_step',
                'has_completed_learn_tutorial',
                'has_completed_setup_tutorial',
            ]);
        });
    }
};
