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
                Forms\Components\Group::make()
                    ->schema([
                        // Main Item Section
                        Forms\Components\Section::make('Invoice Items')
                            ->headerActions([
                                Forms\Components\Actions\Action::make('reset')
                                    ->modalHeading('Are you sure?')
                                    ->action(fn(Forms\Set $set) => $set('items', [])),
                            ])
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\MorphToSelect::make('sellable')
                                            ->label('Item')
                                            ->types([
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Incubator::class)
                                                    ->titleAttribute('name')
                                                    ->label('Product'),
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)
                                                    ->titleAttribute('name')
                                                    ->label('Inventory Item'),
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(2)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state['sellable_type'] && $state['sellable_id']) {
                                                    $modelClass = $state['sellable_type'];
                                                    $record = $modelClass::find($state['sellable_id']);
                                                    if ($record) {
                                                        $price = $record->selling_price ?? $record->price ?? 0;
                                                        $set('unit_price', $price);
                                                        // Trigger total update
                                                        $set('row_total', $price * 1);
                                                    }
                                                }
                                            }),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('LKR')
                                            ->required()
                                            ->columnSpan(1)
                                            ->reactive()
                                            ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                            $set('row_total', (float)$state * (int)$get('quantity'))),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpan(1)
                                            ->reactive()
                                            ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                            $set('row_total', (int)$state * (float)$get('unit_price'))),

                                        Forms\Components\TextInput::make('row_total')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('LKR')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(5) // Aligns everything in one neat row
                                    ->defaultItems(1)
                                    ->reorderable(true)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $items = $get('items');
                                        $total = collect($items)->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                        $set('total_amount', $total);
                                    }),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        // Customer & Meta Section
                        Forms\Components\Section::make('Summary')
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('phone'),
                                    ]),

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
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer'
                                    ])
                                    ->default('cash')
                                    ->required()
                                    ->native(false),
                            ]),

                        // Total Card
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Grand Total')
                                    ->content(fn(Forms\Get $get) => 'LKR ' . number_format($get('total_amount') ?? 0, 2)),

                                Forms\Components\Hidden::make('total_amount')
                                    ->default(0),
                            ])
                            ->extraAttributes(['class' => 'bg-primary-50 dark:bg-primary-900/10 border-primary-200']),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3); // This creates the 2:1 ratio (Main Content : Sidebar)
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

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'bank_transfer' => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),
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
