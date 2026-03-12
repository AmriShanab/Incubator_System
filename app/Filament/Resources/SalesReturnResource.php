<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReturnResource\Pages;
use App\Models\SalesReturn;
use App\Models\Transaction; // Needed for the ledger logging
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Sales'; 
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Return Details')
                            ->description('Link this return to the original invoice.')
                            ->icon('heroicon-o-document-magnifying-glass')
                            ->schema([
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Original Invoice')
                                    ->relationship('invoice', 'id')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "INV-{$record->id} | {$record->customer->name} (Total: LKR " . number_format($record->total_amount, 2) . ")")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    // Auto-fill the refund amount to the invoice total for convenience
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $invoice = \App\Models\Invoice::find($state);
                                            $set('refund_amount', $invoice?->total_amount ?? 0);
                                        }
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('reason')
                                    ->label('Reason for Return')
                                    ->options([
                                        'defective' => 'Defective / Broken',
                                        'wrong_item' => 'Wrong Item Sent',
                                        'customer_change' => 'Customer Changed Mind',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Financials')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\DatePicker::make('return_date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('refund_amount')
                                    ->label('Amount to Refund')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required()
                                    ->helperText('This amount will be deducted from the account that originally received the payment.'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.id')
                    ->label('Invoice')
                    ->weight('bold')
                    ->prefix('INV-')
                    ->searchable()
                    ->sortable()
                    // Shows the customer name directly under the invoice number
                    ->description(fn (SalesReturn $record): string => $record->invoice->customer->name ?? 'Unknown Customer'),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Returned On')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Refunded')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'completed' ? 'success' : 'warning')
                    ->alignEnd(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                // THE LOGIC BUTTON: PROCESS RETURN & REFUND CASH
                Tables\Actions\Action::make('complete')
                    ->label('Process Refund')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Process Return & Refund?')
                    ->modalDescription('This will restock the inventory AND deduct the refund amount from your financial ledger. This action cannot be undone.')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        
                        DB::transaction(function () use ($record) {
                            $invoice = $record->invoice;
                            $refundAmount = $record->refund_amount;

                            // 1. PUT THE ITEMS BACK IN STOCK
                            foreach ($invoice->items as $item) {
                                $product = $item->sellable;
                                if ($product) {
                                    $product->increment('current_stock', $item->quantity);
                                }
                            }

                            // 2. DEDUCT THE MONEY FROM THE LEDGER (If an account is linked)
                            if ($invoice->account_id && $refundAmount > 0) {
                                
                                // Create the ledger entry
                                Transaction::create([
                                    'account_id' => $invoice->account_id,
                                    'type' => 'out',
                                    'amount' => $refundAmount,
                                    'description' => "Refund issued for INV-{$invoice->id}. Reason: {$record->reason}",
                                    'reference_type' => SalesReturn::class,
                                    'reference_id' => $record->id,
                                    'transaction_date' => now(),
                                ]);

                                // Deduct from the actual Wallet balance
                                $invoice->account->decrement('balance', $refundAmount);
                            }

                            // 3. MARK AS COMPLETED
                            $record->update(['status' => 'completed']);
                        });

                        Notification::make()
                            ->title('Refund Processed')
                            ->body('Inventory restocked and funds deducted from ledger.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesReturns::route('/'),
            'create' => Pages\CreateSalesReturn::route('/create'),
            'edit' => Pages\EditSalesReturn::route('/{record}/edit'),
        ];
    }
}