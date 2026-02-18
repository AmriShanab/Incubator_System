<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Illuminate\Support\now;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Header
                Forms\Components\Section::make('Invoice Details')->schema([
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\TextInput::make('phone'),
                        ]), // "Out of Box": Create customer directly from Invoice!

                    Forms\Components\DatePicker::make('invoice_date')
                        ->default(now())
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'processing' => 'Processing',
                            'out_for_delivery' => 'Out for Delivery',
                            'delivered' => 'Delivered',
                        ])
                        ->default('draft')
                        ->required(),
                ])->columns(3),


                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\MorphToSelect::make('sellable')
                            ->label('Item Type')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(\App\Models\Incubator::class)
                                    ->titleAttribute('name')
                                    ->label('Incubator'),
                                Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)
                                    ->titleAttribute('name')
                                    ->label('Accessory / Part'),
                            ])
                            ->searchable()
                            ->required()
                            ->reactive() // Make it listen to changes
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // "Out of Box": Auto-fill price when product is selected
                                if ($state['sellable_type'] && $state['sellable_id']) {
                                    $modelClass = $state['sellable_type'];
                                    $record = $modelClass::find($state['sellable_id']);
                                    if ($record) {
                                        // Assuming both models have 'selling_price' or 'price'
                                        // You might need to adjust Incubator model to have 'price'
                                        $price = $record->selling_price ?? $record->price ?? 0;
                                        $set('unit_price', $price);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, Forms\Get $get, Forms\Set $set) =>
                                $set('row_total', $state * $get('unit_price'))
                            ),

                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, Forms\Get $get, Forms\Set $set) =>
                                $set('row_total', $state * $get('quantity'))
                            ),

                        Forms\Components\TextInput::make('row_total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated() // Ensure it saves to DB
                    ])
                    ->columns(4)
                    // "Out of Box": Auto calculate Grand Total
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        $items = $get('items');
                        $total = collect($items)->sum(fn($item) => $item['row_total'] ?? 0);
                        $set('total_amount', $total);
                    }),

                Forms\Components\TextInput::make('total_amount')
                    ->prefix('LKR')
                    ->disabled()
                    ->dehydrated()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Invoice ID (Searchable)
                Tables\Columns\TextColumn::make('id')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                // 2. Customer Name (Searchable)
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // 3. Status with Colors (Badge)
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'out_for_delivery' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                    }),

                // 4. Total Amount (Formatted as Money)
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('LKR')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('LKR')),

                // 5. Date
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),

                // 6. Created At (Hidden by default, toggleable)
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc') // Show newest first
            ->filters([
                // Optional: Add a filter for Status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'processing' => 'Processing',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Dispatch Button
                Tables\Actions\Action::make('dispatch')
                    ->label('Dispatch')
                    ->icon('heroicon-m-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === 'processing')
                    ->action(fn(Invoice $record) => $record->update(['status' => 'out_for_delivery'])),

                // Deliver Button
                Tables\Actions\Action::make('deliver')
                    ->label('Mark Delivered')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === 'out_for_delivery')
                    ->action(fn(Invoice $record) => $record->update(['status' => 'delivered'])),

                // Print Button
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->url(fn(Invoice $record) => route('invoice.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
