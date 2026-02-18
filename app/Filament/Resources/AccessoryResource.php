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

    protected static ?string $navigationIcon = 'heroicon-o-tag'; // Tag icon for products
    
    protected static ?string $navigationLabel = 'Accessories / Parts';
    
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Item Name')
                        ->columnSpan(2),

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
                        ->label('Current Stock')
                        ->helperText('Stock will automatically decrease when you make a Sale.'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('selling_price')
                    ->money('LKR')
                    ->sortable()
                    ->label('Price'),

                // "Out of Box": Show Profit Margin directly in the table!
                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit')
                    ->state(function (Accessory $record): string {
                        $profit = $record->selling_price - $record->cost_price;
                        return number_format($profit, 2) . ' LKR';
                    })
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state <= 5 => 'danger',   // Red if low stock
                        $state <= 20 => 'warning', // Orange if getting low
                        default => 'success',      // Green if good
                    }),
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
        return [
            //
        ];
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