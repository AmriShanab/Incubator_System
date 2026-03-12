<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessoryResource\Pages;
use App\Models\Accessory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccessoryResource extends Resource
{
    protected static ?string $model = Accessory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationLabel = 'Supplies';
    
    protected static ?string $navigationGroup = 'Inventory';

    /**
     * Premium Feature: Sidebar Badge for Low Stock
     */
    public static function getNavigationBadge(): ?string
    {
        $lowStockCount = static::getModel()::where('current_stock', '<=', 5)->count();
        return $lowStockCount > 0 ? (string) $lowStockCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Supply Details')
                            ->description('Identify and categorize this supply item.')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Item Name')
                                    ->placeholder('e.g., Temperature Sensor')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pricing & Stock')
                            ->schema([
                                Forms\Components\TextInput::make('cost_price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->label('Buying Price (Cost)'),

                                Forms\Components\TextInput::make('selling_price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->label('Selling Price'),

                                Forms\Components\TextInput::make('current_stock')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->label('Live Stock')
                                    ->disabledOn('edit') // Prevent manual cheating of stock on edit
                                    ->helperText('Updated via sales & purchases.'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    // Show the base cost under the item name
                    ->description(fn (Accessory $record): string => 'Cost: LKR ' . number_format($record->cost_price, 2)),

                Tables\Columns\TextColumn::make('selling_price')
                    ->money('LKR')
                    ->sortable()
                    ->label('Selling Price')
                    ->weight('bold')
                    ->alignEnd(), // Accounting standard alignment

                // Premium computed column using native money formatting
                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit Margin')
                    ->state(fn (Accessory $record): float => (float) ($record->selling_price - $record->cost_price))
                    ->money('LKR')
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 5 => 'danger',   // Red if low stock
                        $state <= 20 => 'warning', // Orange if getting low
                        default => 'success',      // Green if good
                    })
                    ->alignEnd(),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListAccessories::route('/'),
            'create' => Pages\CreateAccessory::route('/create'),
            'edit' => Pages\EditAccessory::route('/{record}/edit'),
        ];
    }
}