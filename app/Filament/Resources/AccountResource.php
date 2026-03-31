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
use Illuminate\Support\HtmlString; 

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

                Tables\Actions\Action::make('settle_funds')
                    ->label('Settle COD')
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('warning')
                    ->visible(fn(Account $record) => str_contains(strtolower($record->name), 'cod') && $record->balance > 0)
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Select::make('destination_account_id')
                            ->label('Transfer To (Bank/Cash)')
                            ->options(fn (Account $record) => Account::where('id', '!=', $record->id)->pluck('name', 'id')->toArray())
                            ->required()
                            ->default(fn() => Account::where('name', 'Bank')->first()?->id),

                        Forms\Components\CheckboxList::make('settlement_invoices')
                            ->label('Select Invoices being Settled')
                            ->helperText('Check the invoices shown on your courier remittance slip. Verify the items below.')
                            ->options(function (Account $record) {
                                return \App\Models\Invoice::with('items.sellable')
                                    ->where('account_id', $record->id)
                                    ->where('is_settled', 0)
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        
                                        $identifier = $invoice->tracking_number 
                                            ? "{$invoice->tracking_number} <span class='text-gray-400 font-normal'>(INV-" . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) . ")</span>"
                                            : "INV-" . str_pad($invoice->id, 5, '0', STR_PAD_LEFT);
                                            
                                        $amount = number_format($invoice->total_amount, 2);
                                        
                                        $itemsList = $invoice->items->map(function ($item) {
                                            $itemName = $item->sellable ? $item->sellable->name : 'Unknown Item';
                                            return "<b>{$item->quantity}x</b> {$itemName}";
                                        })->implode(', ');

                                        if (empty($itemsList)) {
                                            $itemsList = 'No items found';
                                        }

                                        $html = "
                                            <div class='flex flex-col py-1 ml-1'>
                                                <span class='text-sm font-bold text-gray-900 dark:text-white'>
                                                    {$identifier} &bull; LKR {$amount}
                                                </span>
                                                <span class='text-xs text-gray-500 dark:text-gray-400 mb-1'>
                                                    Dispatched: {$invoice->invoice_date}
                                                </span>
                                                <span class='text-xs text-primary-600 dark:text-primary-400 leading-snug'>
                                                    &#8627; 📦 {$itemsList}
                                                </span>
                                            </div>
                                        ";

                                        return [(string) $invoice->id => new HtmlString($html)];
                                    });
                            })
                            ->required()
                            ->columns(1)
                            ->bulkToggleable() 
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
                        $courierFee = (float) $data['courier_fee'];
                        $destinationAccount = Account::find($data['destination_account_id']);

                        DB::transaction(function () use ($record, $destinationAccount, $courierFee, $selectedInvoiceIds) {
                            
                            // Fetch the actual invoices to build itemized transactions
                            $invoices = \App\Models\Invoice::whereIn('id', $selectedInvoiceIds)->get();

                            $totalTransferAmount = 0;
                            $totalCapitalAmount = 0;

                            // LOOP THROUGH EACH INVOICE
                            foreach ($invoices as $invoice) {
                                $invAmount = (float) $invoice->total_amount;
                                $invCost = (float) $invoice->total_cost;

                                $totalTransferAmount += $invAmount;
                                $totalCapitalAmount += $invCost;

                                $invString = 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT);
                                $trackingInfo = $invoice->tracking_number ? " ({$invoice->tracking_number})" : " ({$invString})";

                                // Create the itemized 'OUT' transaction from the COD Account
                                $record->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $invAmount,
                                    'description' => "Settlement transferred to {$destinationAccount->name}{$trackingInfo}",
                                    'transaction_date' => now(),
                                    'reference_type' => \App\Models\Invoice::class,
                                    'reference_id' => $invoice->id,
                                ]);

                                // Create the itemized 'IN' transaction to the Destination Account
                                $destinationAccount->transactions()->create([
                                    'type' => 'in',
                                    'amount' => $invAmount,
                                    'description' => "Settlement received from {$record->name}{$trackingInfo}",
                                    'transaction_date' => now(),
                                    'reference_type' => \App\Models\Invoice::class,
                                    'reference_id' => $invoice->id,
                                ]);

                                // Mark invoice as settled
                                $invoice->updateQuietly(['is_settled' => 1]);
                            }

                            // Handle the Courier Fee as a separate single transaction out of the Destination Account
                            if ($courierFee > 0) {
                                $destinationAccount->transactions()->create([
                                    'type' => 'out',
                                    'amount' => $courierFee,
                                    'description' => "Courier processing fee for bulk settlement",
                                    'transaction_date' => now(),
                                ]);
                            }

                            $netDestinationAmount = $totalTransferAmount - $courierFee;
                            $netDestinationProfit = ($totalTransferAmount - $totalCapitalAmount) - $courierFee;

                            // Update Physical Account Balances
                            $record->decrement('balance', $totalTransferAmount);
                            $record->decrement('capital_pool', $totalCapitalAmount);
                            $record->decrement('profit_pool', ($totalTransferAmount - $totalCapitalAmount));

                            $destinationAccount->increment('balance', $netDestinationAmount);
                            $destinationAccount->increment('capital_pool', $totalCapitalAmount);
                            $destinationAccount->increment('profit_pool', max(0, $netDestinationProfit));

                        });

                        Notification::make()
                            ->success()
                            ->title('Settlement Complete')
                            ->body("Successfully settled " . count($selectedInvoiceIds) . " invoices. See the itemized ledger for details.")
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