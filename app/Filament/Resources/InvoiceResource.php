<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    public static function canViewAny(): bool
    {
        return in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['admin', 'cashier']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Invoice Items')
                            ->icon('heroicon-o-shopping-cart')
                            ->headerActions([
                                Forms\Components\Actions\Action::make('reset')
                                    ->modalHeading('Clear all items?')
                                    ->color('danger')
                                    ->action(fn(Forms\Set $set) => $set('items', [])),
                            ])
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\MorphToSelect::make('sellable')
                                            ->label('Product / Supply')
                                            ->types([
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Incubator::class)->titleAttribute('name')->label('Product'),
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)->titleAttribute('name')->label('Supply'),
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(2)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state['sellable_type'] && $state['sellable_id']) {
                                                    $modelClass = $state['sellable_type'];
                                                    $record = $modelClass::find($state['sellable_id']);

                                                    if ($record) {
                                                        $price = $record->selling_price ?? $record->price ?? 0;

                                                        // --- SMART COST FETCHER ---
                                                        $cost = 0;
                                                        if (class_basename($modelClass) === 'Accessory') { // Note: use $type instead of $modelClass in PosTerminal.php
                                                            $cost = $record->cost_price ?? 0;
                                                        } else {
                                                            // Bulletproof BOM Calculation
                                                            $cost = $record->cost ?? \Illuminate\Support\Facades\DB::table('incubator_material')
                                                                ->join('materials', 'incubator_material.material_id', '=', 'materials.id')
                                                                ->where('incubator_id', $record->id)
                                                                ->selectRaw('SUM(incubator_material.quantity_required * materials.cost_per_unit) as calculated_cost')
                                                                ->value('calculated_cost') ?? 0;
                                                        }
                                                        // --------------------------

                                                        $set('unit_price', $price);
                                                        $set('unit_cost', $cost); // Saves the dynamically calculated BOM cost!
                                                        $set('row_total', $price * (int)$get('quantity'));
                                                    }
                                                }
                                                // Force Grand Total Update
                                                $total = collect($get('../../items'))->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                                $set('../../total_amount', $total);
                                            }),

                                        Forms\Components\Hidden::make('unit_cost')->default(0), // The silent cost tracker

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('LKR')
                                            ->required()
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $set('row_total', (float)$state * (int)$get('quantity'));
                                                // Force Grand Total Update
                                                $total = collect($get('../../items'))->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                                $set('../../total_amount', $total);
                                            }),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->rules([
                                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $type = $get('sellable_type');
                                                    $id = $get('sellable_id');
                                                    if ($type && $id) {
                                                        $product = $type::find($id);
                                                        if ($product && $value > $product->current_stock) {
                                                            $fail("Out of stock! Only {$product->current_stock} available.");
                                                        }
                                                    }
                                                },
                                            ]),

                                        Forms\Components\TextInput::make('row_total')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('LKR')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(5)
                                    ->defaultItems(1)
                                    ->reorderable(true)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $items = $get('items');
                                        $total = collect($items)->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                        $set('total_amount', $total);
                                    }),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Client & Logistics')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->label('Customer')
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('phone')->tel(),
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
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\Section::make('Payment Info')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->relationship('account', 'name')
                                    ->label('Payment Method')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Grand Total')
                                    ->content(fn(Forms\Get $get) => 'LKR ' . number_format($get('total_amount') ?? 0, 2))
                                    ->extraAttributes(['class' => 'text-2xl font-bold text-success-600']),

                                Forms\Components\Hidden::make('total_amount')->default(0),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('INV #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->prefix('INV-')
                    ->description(fn(Invoice $record): string => $record->invoice_date),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn(Invoice $record): string => ucwords(str_replace('_', ' ', $record->payment_method))),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'out_for_delivery' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('LKR')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('process')
                        ->label('Start Processing')
                        ->icon('heroicon-m-play')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => $record->status === 'draft')
                        ->action(fn(Invoice $record) => $record->update(['status' => 'processing'])),

                    Tables\Actions\Action::make('dispatch')
                        ->label('Dispatch')
                        ->icon('heroicon-m-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => $record->status === 'processing')
                        ->action(fn(Invoice $record) => $record->update(['status' => 'out_for_delivery'])),

                    Tables\Actions\Action::make('deliver')
                        ->label('Mark Delivered')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => $record->status === 'out_for_delivery')
                        ->action(fn(Invoice $record) => $record->update(['status' => 'delivered'])),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Invoice')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => in_array($record->status, ['draft', 'processing', 'out_for_delivery']))
                        ->action(fn(Invoice $record) => $record->update(['status' => 'cancelled'])),

                    Tables\Actions\Action::make('print')
                        ->label('Print Invoice')
                        ->icon('heroicon-m-printer')
                        ->color('gray')
                        ->url(fn(Invoice $record) => route('invoice.print', $record))
                        ->openUrlInNewTab(),
                ]),
            ]);
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
