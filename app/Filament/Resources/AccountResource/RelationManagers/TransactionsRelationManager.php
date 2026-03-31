<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists; // <-- Added for the beautiful read-only popup

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
            ])
            ->actions([
                // PHASE 3: The "View Order" Action Modal
                Tables\Actions\Action::make('view_order')
                    ->label('View Items')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    // Only show this button if the transaction is linked to an Invoice
                    ->visible(fn ($record) => $record->reference_type === \App\Models\Invoice::class && $record->reference_id !== null)
                    
                    // Dynamically set the title of the popup based on the tracking number or INV number
                    ->modalHeading(function ($record) {
                        $invoice = $record->reference;
                        if (!$invoice) return 'Order Details';
                        
                        $invStr = 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT);
                        return $invoice->tracking_number ? "Order Items: {$invoice->tracking_number} ({$invStr})" : "Order Items: {$invStr}";
                    })
                    ->modalSubmitAction(false) // Hide the "Submit" button since it's read-only
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl')
                    
                    // Build the actual visual layout using Infolists
                    ->infolist(function ($record, Infolists\Infolist $infolist) {
                        return $infolist
                            ->record($record->reference) // Switch context from Transaction to the actual Invoice
                            ->schema([
                                Infolists\Components\Section::make('Items Sold')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('items')
                                            ->label('') // Hide the default label
                                            ->schema([
                                                Infolists\Components\TextEntry::make('sellable.name')
                                                    ->label('Product / Supply')
                                                    ->weight('bold'),
                                                    
                                                Infolists\Components\TextEntry::make('quantity')
                                                    ->label('Qty')
                                                    ->badge()
                                                    ->color('gray'),
                                                    
                                                Infolists\Components\TextEntry::make('unit_price')
                                                    ->label('Unit Price')
                                                    ->money('LKR'),
                                                    
                                                Infolists\Components\TextEntry::make('row_total')
                                                    ->label('Subtotal')
                                                    ->money('LKR')
                                                    ->weight('bold')
                                                    ->color('success'),
                                            ])
                                            ->columns(4) // Lay out the items in a clean row
                                    ])
                            ]);
                    }),
            ]);
    }
}