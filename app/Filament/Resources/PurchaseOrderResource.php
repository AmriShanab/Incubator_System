<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\Material;
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
                                        Forms\Components\Select::make('material_id')
                                            ->relationship('material', 'name')
                                            ->label('Raw Material')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live() // Changed to live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state) {
                                                    $cost = Material::find($state)?->cost_per_unit ?? 0;
                                                    $set('unit_cost', $cost);
                                                    // FIX: Auto-calculate the row total as soon as material is picked
                                                    $set('row_total', $cost * (int)($get('quantity') ?? 1));
                                                }
                                            })
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('unit_cost')
                                            ->numeric()
                                            ->required()
                                            ->label('Unit Cost')
                                            ->prefix('LKR')
                                            ->live(onBlur: true) // Changed to live(onBlur: true)
                                            ->afterStateUpdated(
                                                fn($state, Forms\Get $get, Forms\Set $set) =>
                                                $set('row_total', (float)($state ?? 0) * (int)($get('quantity') ?? 1))
                                            )->columnSpan(1),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live(onBlur: true) // Changed to live(onBlur: true)
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
                                    ->live() // Ensures adding/removing items triggers an update
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
                                    ->relationship('account', 'name')
                                    ->label('Pay From')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    // ADD THIS NEW RULES BLOCK:
                                    ->rules([
                                        fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $account = \App\Models\Account::find($value);
                                            // Re-calculate the grand total to check against the bank balance
                                            $totalAmount = collect($get('items'))->sum(fn($item) => (float) ($item['row_total'] ?? 0));

                                            if ($account && $totalAmount > $account->balance) {
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

                                // FIX: Dynamically sum the totals directly in the placeholder
                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Grand Total')
                                    ->content(function (Forms\Get $get) {
                                        $total = collect($get('items'))->sum(fn($item) => (float) ($item['row_total'] ?? 0));
                                        return 'LKR ' . number_format($total, 2);
                                    })
                                    ->extraAttributes(['class' => 'text-xl font-black text-primary-600']),

                                // FIX: Make sure the exact same dynamic calculation saves to the database
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

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Paid Via')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('LKR')),
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
                    ->modalDescription('This will add these items to stock, update cost prices, and deduct money from your account\'s Investment Capital Pool.')
                    ->visible(fn(PurchaseOrder $record) => $record->status === 'ordered')
                    ->action(function (PurchaseOrder $record) {
                        foreach ($record->items as $item) {
                            $material = $item->material;
                            if ($material) {
                                $material->increment('current_stock', $item->quantity);
                                $material->update(['cost_per_unit' => $item->unit_cost]);
                            }
                        }
                        $record->update(['status' => 'received']);
                        Notification::make()->title('Goods Received')->success()->send();
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
