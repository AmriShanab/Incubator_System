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

                Tables\Columns\TextColumn::make('balance')
                    ->label('Total Physical Balance')
                    ->money('LKR')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('capital_pool')
                    ->label('Investment / Capital')
                    ->money('LKR')
                    ->color('warning')
                    ->description('Reserved for materials'),

                Tables\Columns\TextColumn::make('profit_pool')
                    ->label('Free Profit')
                    ->money('LKR')
                    ->weight('bold')
                    ->color('success')
                    ->description('Available earnings'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // THE NEW INVOICE-AWARE SETTLEMENT ACTION
                Tables\Actions\Action::make('settle_funds')
                    ->label('Settle COD')
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('warning')
                    ->visible(fn(Account $record) => str_contains(strtolower($record->name), 'cod') && $record->balance > 0)
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Select::make('destination_account_id')
                            ->label('Transfer To (Bank/Cash)')
                            // FIXED OPTIONS QUERY
                            ->options(fn (Account $record) => Account::where('id', '!=', $record->id)->pluck('name', 'id')->toArray())
                            ->required()
                            ->default(fn() => Account::where('name', 'Bank')->first()?->id),

                        Forms\Components\CheckboxList::make('settlement_invoices')
                            ->label('Select Invoices being Settled')
                            ->helperText('Check the invoices shown on your courier remittance slip.')
                            ->options(function (Account $record) {
                                return \App\Models\Invoice::where('account_id', $record->id)
                                    ->where('is_settled', 0)
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        $label = "INV-" . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) . " | Date: " . $invoice->invoice_date . " | LKR " . number_format($invoice->total_amount, 2);
                                        return [$invoice->id => $label];
                                    });
                            })
                            ->required()
                            ->columns(1)
                            ->live() 
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $selectedIds = $get('settlement_invoices') ?? [];

                                if (empty($selectedIds)) {
                                    $set('transfer_amount', 0);
                                    $set('capital_amount', 0);
                                    return;
                                }

                                $totals = \App\Models\Invoice::whereIn('id', $selectedIds)
                                    ->selectRaw('SUM(total_amount) as sum_amount, SUM(total_cost) as sum_cost')
                                    ->first();

                                $set('transfer_amount', $totals->sum_amount ?? 0);
                                $set('capital_amount', $totals->sum_cost ?? 0);
                            }),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('transfer_amount')
                                ->label('Total Collected Amount')
                                ->numeric()
                                ->readOnly() 
                                ->prefix('LKR')
                                ->default(0),

                            Forms\Components\TextInput::make('capital_amount')
                                ->label('Total Cost (Capital)')
                                ->numeric()
                                ->readOnly() 
                                ->prefix('LKR')
                                ->default(0),
                        ]),

                        Forms\Components\TextInput::make('courier_fee')
                            ->label('Courier Processing Fee (Deduction)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->helperText('This fee is deducted directly from your Free Profit.'),

                        Forms\Components\Placeholder::make('net_profit_preview')
                            ->label('Net Profit (Added to Free Profit Pool)')
                            ->content(function (Forms\Get $get) {
                                $transfer = (float) $get('transfer_amount');
                                $capital  = (float) $get('capital_amount');
                                $fee      = (float) $get('courier_fee');

                                $profit = $transfer - $capital - $fee;
                                return 'LKR ' . number_format(max(0, $profit), 2);
                            }),
                    ])
                    ->action(function (Account $record, array $data) {
                        $selectedInvoiceIds = $data['settlement_invoices'];
                        $transferAmount = (float) $data['transfer_amount'];
                        $capitalAmount = (float) $data['capital_amount'];
                        $courierFee = (float) $data['courier_fee'];

                        $netAmount = $transferAmount - $courierFee; 
                        $profitAmount = $transferAmount - $capitalAmount - $courierFee; 

                        $destinationAccount = Account::find($data['destination_account_id']);

                        DB::transaction(function () use ($record, $destinationAccount, $transferAmount, $capitalAmount, $courierFee, $netAmount, $profitAmount, $selectedInvoiceIds) {

                            $record->decrement('balance', $transferAmount);
                            $record->decrement('capital_pool', $capitalAmount);
                            $record->decrement('profit_pool', ($transferAmount - $capitalAmount));

                            if ($courierFee > 0) {
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $courierFee,
                                    'description' => 'Courier processing fee during settlement',
                                    'transaction_date' => now(),
                                ]);
                            }

                            if ($transferAmount > 0) {
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $netAmount,
                                    'description' => "Settlement transfer to {$destinationAccount->name}",
                                    'transaction_date' => now(),
                                ]);

                                $destinationAccount->transactions()->create([
                                    'type' => 'in',
                                    'amount' => $netAmount,
                                    'description' => "Settlement received from {$record->name}",
                                    'transaction_date' => now(),
                                ]);

                                $destinationAccount->increment('balance', $netAmount);
                                $destinationAccount->increment('capital_pool', $capitalAmount);
                                $destinationAccount->increment('profit_pool', max(0, $profitAmount));
                            }

                            \App\Models\Invoice::whereIn('id', $selectedInvoiceIds)->update([
                                'is_settled' => 1
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Settlement Complete')
                            ->body("Successfully settled " . count($selectedInvoiceIds) . " invoices. Deposited LKR " . number_format($netAmount, 2) . " into {$destinationAccount->name}.")
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