<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total_amount');
            $table->enum('payment_status', ['paid', 'partial', 'credit'])->default('paid')->after('status');
        });

        DB::statement('UPDATE invoices SET amount_paid = total_amount WHERE total_amount IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_status']);
        });
    }
};
