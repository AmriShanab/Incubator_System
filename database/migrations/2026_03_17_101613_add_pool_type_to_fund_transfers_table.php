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
        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->string('pool_type')->default('capital')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->dropColumn('pool_type');
        });
    }
};
