<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->foreignId('to_account_id')
                ->nullable()
                ->after('account_id')
                ->constrained('accounts')
                ->onDelete('set null');
        });

        DB::statement("ALTER TABLE recurring_transactions MODIFY COLUMN type ENUM('expense', 'income', 'transfer') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE recurring_transactions MODIFY COLUMN type ENUM('expense', 'income') NOT NULL");

        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropForeign(['to_account_id']);
            $table->dropColumn('to_account_id');
        });
    }
};
