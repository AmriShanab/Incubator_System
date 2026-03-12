<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Accounting';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('balance')
                    ->required()
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
                Tables\Columns\TextColumn::make('balance')
                    ->money('LKR')
                    ->sortable()
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // THE SETTLEMENT ACTION
                Tables\Actions\Action::make('settle_funds')
                    ->label('Settle COD')
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('warning')
                    // Only show this button on the COD account if it has money
                    ->visible(fn(Account $record) => $record->name === 'COD Partner' && $record->balance > 0)
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
                            ->required(),

                        Forms\Components\TextInput::make('courier_fee')
                            ->label('Courier Processing Fee (Deduction)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('This amount will be logged as an expense and subtracted from the transfer.'),
                    ])
                    ->action(function (Account $record, array $data) {
                        $transferAmount = (float) $data['transfer_amount'];
                        $courierFee = (float) $data['courier_fee'];
                        $netAmount = $transferAmount - $courierFee;

                        $destinationAccount = Account::find($data['destination_account_id']);

                        // Database Transaction ensures if math fails, nothing saves
                        DB::transaction(function () use ($record, $destinationAccount, $transferAmount, $courierFee, $netAmount) {

                            // 1. Log the Courier Fee as an expense
                            if ($courierFee > 0) {
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $courierFee,
                                    'description' => 'Courier processing fee during settlement',
                                    'transaction_date' => now(),
                                ]);
                                $record->decrement('balance', $courierFee);
                            }

                            // 2. Move the Net Amount out of COD
                            if ($netAmount > 0) {
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $netAmount,
                                    'description' => "Settlement transfer to {$destinationAccount->name}",
                                    'transaction_date' => now(),
                                ]);
                                $record->decrement('balance', $netAmount);

                                // 3. Move the Net Amount into the Bank
                                $destinationAccount->transactions()->create([
                                    'type' => 'in',
                                    'amount' => $netAmount,
                                    'description' => "Settlement received from {$record->name}",
                                    'transaction_date' => now(),
                                ]);
                                $destinationAccount->increment('balance', $netAmount);
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Settlement Complete')
                            ->body("Successfully deposited LKR " . number_format($netAmount, 2) . " into {$destinationAccount->name}.")
                            ->send();
                    })
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
      return [
            'index' => Pages\ListAccounts::route('/'),
        ];
    }
}
