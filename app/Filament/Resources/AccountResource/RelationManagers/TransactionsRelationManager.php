<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    protected static ?string $recordTitleAttribute = 'description';
    protected static ?string $title = 'Account Ledger (Incomes & Expenses)';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Flow')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state) => $state === 'in' ? 'Income (+)' : 'Expense (-)'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('LKR')
                    ->weight('bold')
                    ->color(fn ($record) => $record->type === 'in' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // You can add filters here later if they want to sort by just income or just expenses
            ]);
    }
}