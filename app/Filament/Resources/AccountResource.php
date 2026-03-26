<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers; // <-- ADDED THIS LINE
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Accounting';

    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('balance')
                    ->label('Physical Balance')
                    ->required()
                    ->default(0.00)
                    ->prefix('LKR')
                    ->disabled('edit'),

                Forms\Components\TextInput::make('capital_pool')
                    ->label('Investment Capital')
                    ->default(0.00)
                    ->prefix('LKR')
                    ->disabled('edit'),

                Forms\Components\TextInput::make('profit_pool')
                    ->label('Free Profit')
                    ->default(0.00)
                    ->prefix('LKR')
                    ->disabled('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),

                // The physical money in the drawer/bank
                Tables\Columns\TextColumn::make('balance')
                    ->label('Total Physical Balance')
                    ->money('LKR')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),

                // The money strictly for buying stock
                Tables\Columns\TextColumn::make('capital_pool')
                    ->label('Investment / Capital')
                    ->money('LKR')
                    ->color('warning')
                    ->description('Reserved for materials'),

                // The money they can safely take home!
                Tables\Columns\TextColumn::make('profit_pool')
                    ->label('Free Profit')
                    ->money('LKR')
                    ->weight('bold')
                    ->color('success')
                    ->description('Available earnings'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // THE SETTLEMENT ACTION
                Tables\Actions\Action::make('settle_funds')
                    ->label('Settle COD')
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('warning')
                    ->visible(fn(Account $record) => str_contains($record->name, 'COD Partner') && $record->balance > 0)
                    ->form([
                        Forms\Components\Select::make('destination_account_id')
                            ->label('Transfer To')
                            ->options(Account::where('name', '!=', 'COD Partner')->pluck('name', 'id'))
                            ->required()
                            ->default(fn() => Account::where('name', 'Bank')->first()?->id),

                        Forms\Components\TextInput::make('transfer_amount')
                            ->label('Total COD Amount Collected')
                            ->numeric()
                            ->default(fn(Account $record) => $record->balance)
                            ->maxValue(fn(Account $record) => $record->balance)
                            ->required()
                            ->live(onBlur: true),

                        // NEW: Explicitly define the product costs!
                        Forms\Components\TextInput::make('capital_amount')
                            ->label('Cost of Products (Goes to Capital)')
                            ->helperText('How much did it cost you to build/buy these items? This money is protected for restocking.')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('courier_fee')
                            ->label('Courier Processing Fee (Deduction)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->helperText('This fee is deducted directly from your Free Profit.'),

                        // NEW: Show the Admin exactly how much profit they are making in real-time
                        Forms\Components\Placeholder::make('net_profit_preview')
                            ->label('Net Profit (Added to Free Profit Pool)')
                            ->content(function (Forms\Get $get) {
                                $transfer = (float) $get('transfer_amount');
                                $capital = (float) $get('capital_amount');
                                $fee = (float) $get('courier_fee');

                                // Profit = Total Money - Cost - Courier Fee
                                $profit = $transfer - $capital - $fee;

                                return 'LKR ' . number_format(max(0, $profit), 2);
                            }),
                    ])
                    ->action(function (Account $record, array $data) {
                        $transferAmount = (float) $data['transfer_amount'];
                        $capitalAmount = (float) $data['capital_amount']; // The explicitly defined cost
                        $courierFee = (float) $data['courier_fee'];

                        $netAmount = $transferAmount - $courierFee; // What hits the bank
                        $profitAmount = $transferAmount - $capitalAmount - $courierFee; // What you take home

                        $destinationAccount = Account::find($data['destination_account_id']);

                        DB::transaction(function () use ($record, $destinationAccount, $transferAmount, $capitalAmount, $courierFee, $netAmount, $profitAmount) {

                            // 1. Calculate how to safely zero out the COD holding account
                            $transferRatio = $record->balance > 0 ? ($transferAmount / $record->balance) : 0;
                            $codCapitalToDeduct = $record->capital_pool * $transferRatio;
                            $codProfitToDeduct = $record->profit_pool * $transferRatio;

                            // 2. Record the Courier Fee Expense
                            if ($courierFee > 0) {
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $courierFee,
                                    'description' => 'Courier processing fee during settlement',
                                    'transaction_date' => now(),
                                ]);
                            }

                            if ($transferAmount > 0) {
                                // 3. Deduct from COD Account
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $netAmount,
                                    'description' => "Settlement transfer to {$destinationAccount->name}",
                                    'transaction_date' => now(),
                                ]);

                                $record->decrement('balance', $transferAmount);
                                $record->decrement('capital_pool', $codCapitalToDeduct);
                                $record->decrement('profit_pool', $codProfitToDeduct);

                                // 4. Add to Destination Bank (Using the exact Capital/Profit split you defined!)
                                $destinationAccount->transactions()->create([
                                    'type' => 'in',
                                    'amount' => $netAmount,
                                    'description' => "Settlement received from {$record->name}",
                                    'transaction_date' => now(),
                                ]);

                                $destinationAccount->increment('balance', $netAmount);
                                $destinationAccount->increment('capital_pool', $capitalAmount);
                                $destinationAccount->increment('profit_pool', $profitAmount);
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Settlement Complete')
                            ->body("Successfully deposited LKR " . number_format($netAmount, 2) . " into {$destinationAccount->name}.")
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'view' => Pages\ViewAccount::route('/{record}'),
        ];
    }
}
