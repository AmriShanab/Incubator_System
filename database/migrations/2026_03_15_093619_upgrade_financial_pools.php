<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('captain_pool', 12, 2)->default(0)->after('balance');
            $table->decimal('profit_poo', 12, 2)->default(0)->after('captain_pool');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('unit_costs', 12, 2)->default(0)->after('quantity');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_cost', 12, 2)->default(0)->after('total_amount');
            $table->decimal('total_profit', 12, 2)->default(0)->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
