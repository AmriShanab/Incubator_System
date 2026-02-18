<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    // Icon for Customers

    protected static ?string $navigationGroup = 'Sales';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('incubator_id')
                    ->relationship('incubator', 'name') // display the incubator name
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Product Sold'),

                Forms\Components\TextInput::make('quantity_sold')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Quantity'),

                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->prefix('LKR')
                    ->label('Total Sale Amount'),

                Forms\Components\DatePicker::make('sold_at')
                    ->required()
                    ->default(now())
                    ->label('Date of Sale'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incubator.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity_sold')
                    ->sortable()
                    ->label('Qty'),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('LKR') // Formats as currency
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('LKR')), // Adds a total at bottom

                Tables\Columns\TextColumn::make('sold_at')
                    ->date()
                    ->sortable()
                    ->label('Sold Date'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sold_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ... keep getRelations and getPages as they are
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
