<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('capital_pool', 12, 2)->default(0)->after('balance');
            $table->decimal('profit_pool', 12, 2)->default(0)->after('capital_pool');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->default(0)->after('quantity');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_cost', 12, 2)->default(0)->after('total_amount');
            $table->decimal('total_profit', 12, 2)->default(0)->after('total_cost');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['capital_pool', 'profit_pool']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'total_profit']);
        });
    }
};