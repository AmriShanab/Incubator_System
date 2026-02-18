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
        Schema::table('incubators', function (Blueprint $table) {
            $table->integer('current_stock')->default(0)->after('sku');
            $table->decimal('price', 10, 2)->default(0)->after('current_stock'); // Optional: Selling Price
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incubators', function (Blueprint $table) {
            //
        });
    }
};
