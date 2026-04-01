<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::updateOrCreate(
            ['name' => 'Cash'],
            [
                'balance' => 0,
                'capital_pool' => 0,
                'profit_pool' => 0,
            ]
        );

        Account::updateOrCreate(
            ['name' => 'Bank Account'],
            [
                'balance' => 0,
                'capital_pool' => 0,
                'profit_pool' => 0,
            ]
        );

        Account::updateOrCreate(
            ['name' => 'COD Partner (Courier)'],
            [
                'balance' => 0,
                'capital_pool' => 0,
                'profit_pool' => 0,
            ]
        );

        Account::updateOrCreate(
            ['name' => 'Accounts Receivable'],
            [
                'balance' => 0,
                'capital_pool' => 0,
                'profit_pool' => 0,
            ]
        );
    }
}
