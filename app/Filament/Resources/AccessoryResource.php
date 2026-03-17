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

    protected static ?string $modelLabel = 'Supply';

    protected static ?string $pluralModelLabel = 'Supplies';

    protected static ?string $navigationGroup = 'Inventory';

    public static function canViewAny(): bool
    {
        return in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['admin', 'inventory']);
    }

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
                                    ->placeholder('e.g., Vitamin C Drops')
                                    ->columnSpanFull(),

                                // THIS BELONGS IN THE FORM! 
                                Forms\Components\TextInput::make('category')
                                    ->required()
                                    ->label('Category')
                                    ->placeholder('Type a new category or select existing')
                                    ->datalist(fn() => Accessory::select('category')->distinct()->pluck('category')->toArray())
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
                                    ->disabledOn('edit')
                                    ->helperText('Updated via sales & purchases.'),

                                Forms\Components\TextInput::make('min_stock_alert')
                                    ->label('Low Stock Alert Level')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('Item turns red when stock is at or below this quantity.'),
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
                    ->description(fn(Accessory $record): string => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'
                        ? 'Cost: LKR ' . number_format($record->cost_price, 2)
                        : ''),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->money('LKR')
                    ->sortable()
                    ->label('Selling Price')
                    ->weight('bold')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit Margin')
                    ->state(fn(Accessory $record): float => (float) ($record->selling_price - $record->cost_price))
                    ->money('LKR')
                    ->color(fn(float $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable()
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->role === 'admin')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn(int|float $state, Accessory $record): string => match (true) {
                        $state <= (int) ($record->min_stock_alert ?? 5) => 'danger',
                        $state <= ((int) ($record->min_stock_alert ?? 5) * 2) => 'warning',
                        default => 'success',
                    })
                    ->alignEnd(),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
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