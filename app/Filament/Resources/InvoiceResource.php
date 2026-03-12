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
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Incubator::class)->titleAttribute('name')->label('Incubator'),
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)->titleAttribute('name')->label('Accessory'),
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
                                            ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) => $set('row_total', (float)$state * (int)$get('quantity'))),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpan(1)
                                            ->reactive()
                                            ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) => $set('row_total', (int)$state * (float)$get('unit_price'))),

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
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\Section::make('Payment Info')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                    ])
                                    ->default('cash')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('account_id')
                                    ->relationship('account', 'name')
                                    ->label('Deposit To')
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
                    ->description(fn (Invoice $record): string => $record->invoice_date),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn (Invoice $record): string => ucwords(str_replace('_', ' ', $record->payment_method))),

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