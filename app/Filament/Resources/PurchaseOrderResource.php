<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?string $navigationGroup = 'Supply Chain';

    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::check() && in_array(\Illuminate\Support\Facades\Auth::user()->role, ['admin', 'inventory']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Supplier Details')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->relationship('supplier', 'company_name')
                                    ->label('Vendor')
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('company_name')->required(),
                                        Forms\Components\TextInput::make('contact_person'),
                                        Forms\Components\TextInput::make('phone')->tel(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Line Items')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        // THE FIX: Hidden tracker to check if dropdown ACTUALLY changed
                                        Forms\Components\Hidden::make('item_tracker'),

                                        Forms\Components\MorphToSelect::make('purchasable')
                                            ->label('Item to Purchase')
                                            ->types([
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Material::class)
                                                    ->titleAttribute('name')
                                                    ->label('Raw Material'),
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)
                                                    ->titleAttribute('name')
                                                    ->label('Supply / Accessory'),
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                
                                                $currentTracker = ($state['purchasable_type'] ?? '') . '_' . ($state['purchasable_id'] ?? '');

                                                // If the tracker hasn't changed, this is just a background re-render. Do nothing!
                                                if ($get('item_tracker') === $currentTracker) {
                                                    return;
                                                }

                                                // If it HAS changed, update the tracker and fetch the DB price
                                                $set('item_tracker', $currentTracker);

                                                if ($state['purchasable_type'] && $state['purchasable_id']) {
                                                    $modelClass = $state['purchasable_type'];
                                                    $record = $modelClass::find($state['purchasable_id']);

                                                    if ($record) {
                                                        $cost = $record->cost_per_unit ?? $record->cost_price ?? $record->cost ?? 0;
                                                        
                                                        $set('unit_cost', $cost);
                                                        $set('row_total', $cost * (int)($get('quantity') ?? 1));
                                                    }
                                                }
                                            })
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('unit_cost')
                                            ->numeric()
                                            ->required()
                                            ->label('Unit Cost')
                                            ->prefix('LKR')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(
                                                fn($state, Forms\Get $get, Forms\Set $set) =>
                                                $set('row_total', (float)($state ?? 0) * (int)($get('quantity') ?? 1))
                                            )->columnSpan(1),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(
                                                fn($state, Forms\Get $get, Forms\Set $set) =>
                                                $set('row_total', (int)($state ?? 1) * (float)($get('unit_cost') ?? 0))
                                            )->columnSpan(1),

                                        Forms\Components\TextInput::make('row_total')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->label('Subtotal')
                                            ->prefix('LKR')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(5)
                                    ->live()
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Order Summary')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\DatePicker::make('order_date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('account_id')
                                    ->options(function () {
                                        return \App\Models\Account::whereIn('type', ['cash', 'bank', 'credit_payable'])
                                            ->orWhereNull('type')
                                            ->pluck('name', 'id');
                                    })
                                    ->label('Pay From / Credit Account')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->rules([
                                        fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $account = \App\Models\Account::find($value);
                                            $totalAmount = collect($get('items'))->sum(fn($item) => (float) ($item['row_total'] ?? 0));

                                            if ($account && $account->type !== 'credit_payable' && $totalAmount > $account->balance) {
                                                $fail("Insufficient funds. Order costs LKR " . number_format($totalAmount, 2) . " but account only has LKR " . number_format($account->balance, 2));
                                            }
                                        },
                                    ]),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ordered' => 'Ordered (Pending)',
                                        'received' => 'Received (In Stock)',
                                    ])
                                    ->default('ordered')
                                    ->required()
                                    ->disabled()
                                    ->native(false),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Grand Total')
                                    ->content(function (Forms\Get $get) {
                                        $total = collect($get('items'))->sum(fn($item) => (float) ($item['row_total'] ?? 0));
                                        return 'LKR ' . number_format($total, 2);
                                    })
                                    ->extraAttributes(['class' => 'text-xl font-black text-primary-600']),

                                Forms\Components\Hidden::make('total_amount')
                                    ->default(0)
                                    ->dehydrateStateUsing(function (Forms\Get $get) {
                                        return collect($get('items'))->sum(fn($item) => (float) ($item['row_total'] ?? 0));
                                    }),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-storefront')
                    ->description(fn(PurchaseOrder $record): string => 'Ordered: ' . $record->order_date),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ordered' => 'warning',
                        'received' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'credit' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd(),

                // Displaying the balance remaining
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->state(fn(PurchaseOrder $record) => $record->total_amount - $record->amount_paid)
                    ->money('LKR')
                    ->color('danger')
                    ->alignEnd(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(PurchaseOrder $record) => $record->status === 'ordered'),

                Tables\Actions\Action::make('receive')
                    ->label('Receive Goods')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Receive Inventory?')
                    ->modalDescription('This will add these items to your physical stock and update cost prices.')
                    ->visible(fn(PurchaseOrder $record) => $record->status === 'ordered')
                    ->action(function (PurchaseOrder $record, \App\Services\PurchaseService $service) {
                        try {
                            $service->receiveOrder($record);
                            Notification::make()->title('Goods Received and Stocked')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // PARTIAL PAYMENTS ACTION
                Tables\Actions\Action::make('settle_debt')
                    ->label('Pay Debt')
                    ->icon('heroicon-m-banknotes')
                    ->color('danger')
                    ->visible(fn(PurchaseOrder $record) => 
                        $record->account && 
                        $record->account->type === 'credit_payable' && 
                        $record->payment_status !== 'paid' && 
                        $record->status === 'received' // Can only pay for received goods
                    )
                    ->form([
                        Forms\Components\Select::make('payment_account_id')
                            ->label('Pay From Drawer/Bank')
                            ->options(function () {
                                return \App\Models\Account::whereIn('type', ['cash', 'bank'])
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('payment_amount')
                            ->label('Amount to Pay')
                            ->numeric()
                            ->required()
                            ->prefix('LKR')
                            ->default(fn(PurchaseOrder $record) => $record->total_amount - $record->amount_paid)
                            ->maxValue(fn(PurchaseOrder $record) => $record->total_amount - $record->amount_paid)
                            ->helperText(fn(PurchaseOrder $record) => 'Balance Due: LKR ' . number_format($record->total_amount - $record->amount_paid, 2)),
                    ])
                    ->action(function (array $data, PurchaseOrder $record, \App\Services\PurchaseService $service) {
                        try {
                            $service->settleCreditOrder($record, (int) $data['payment_account_id'], (float) $data['payment_amount']);
                            Notification::make()->title('Payment Processed Successfully')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Payment Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}