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
use Filament\Notifications\Notification; // Import for alerts

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- Section 1: PO Header ---
                Forms\Components\Section::make('Order Details')->schema([
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'company_name')
                        ->searchable()
                        ->required()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('company_name')->required(),
                            Forms\Components\TextInput::make('contact_person'),
                            Forms\Components\TextInput::make('phone'),
                        ]),

                    Forms\Components\DatePicker::make('order_date')
                        ->default(now())
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'ordered' => 'Ordered (Pending)',
                            'received' => 'Received (In Stock)',
                        ])
                        ->default('ordered')
                        ->required()
                        ->disabled(), // Disable manual change; use the Button instead!
                ])->columns(3),

                // --- Section 2: Items Repeater ---
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('material_id')
                            ->relationship('material', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive() // Listen for changes
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Auto-fill the cost price from the Material definition
                                if ($state) {
                                    $cost = Material::find($state)?->cost_per_unit ?? 0;
                                    $set('unit_cost', $cost);
                                }
                            })
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                                $set('row_total', $state * $get('unit_cost'))
                            ),

                        Forms\Components\TextInput::make('unit_cost')
                            ->numeric()
                            ->required()
                            ->label('Cost Per Unit')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                                $set('row_total', $state * $get('quantity'))
                            ),

                        Forms\Components\TextInput::make('row_total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated() // Saves to DB even if disabled
                            ->label('Total'),
                    ])
                    ->columns(5)
                    // Auto-calculate Grand Total
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        $items = $get('items');
                        $total = collect($items)->sum(fn ($item) => $item['row_total'] ?? 0);
                        $set('total_amount', $total);
                    }),

                Forms\Components\TextInput::make('total_amount')
                    ->label('Grand Total')
                    ->prefix('LKR')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->sortable()
                    ->searchable()
                    ->label('Supplier'),

                Tables\Columns\TextColumn::make('order_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ordered' => 'warning',
                        'received' => 'success',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('LKR')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                // --- THE MAGIC BUTTON: RECEIVE GOODS ---
                Tables\Actions\Action::make('receive')
                    ->label('Receive Goods')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Receive Inventory?')
                    ->modalDescription('This will add these items to your stock and update the cost price.')
                    ->visible(fn (PurchaseOrder $record) => $record->status === 'ordered') // Only show if not yet received
                    ->action(function (PurchaseOrder $record) {
                        // 1. Loop through items
                        foreach ($record->items as $item) {
                            $material = $item->material;

                            if ($material) {
                                // Increase Stock
                                $material->increment('current_stock', $item->quantity);

                                // Update Cost Price to the new price
                                $material->update(['cost_per_unit' => $item->unit_cost]);
                            }
                        }

                        // 2. Mark PO as Received
                        $record->update(['status' => 'received']);

                        // 3. Notify User
                        Notification::make()
                            ->title('Goods Received')
                            ->body('Stock levels have been updated.')
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

    public static function getRelations(): array
    {
        return [];
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