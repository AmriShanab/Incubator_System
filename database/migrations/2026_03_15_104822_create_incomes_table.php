<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('title'); // e.g., "Customer Advance", "Owner Investment"
            $table->decimal('amount', 12, 2);
            $table->enum('pool_type', ['capital', 'profit'])->default('profit');
            $table->text('description')->nullable();
            $table->date('income_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};