<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncubatorResource\Pages;
use App\Filament\Resources\IncubatorResource\RelationManagers;
use App\Models\Incubator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IncubatorResource extends Resource
{
    protected static ?string $model = Incubator::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Products';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Details')
                    ->description('Manage the basic information and pricing for this product.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU (Stock Keeping Unit)')
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('price')
                            ->label('Selling Price')
                            ->numeric()
                            ->prefix('LKR')
                            ->required()
                            ->placeholder('0.00'),
                    ])->columns(2),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('LKR') 
                    ->sortable(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncubators::route('/'),
            'create' => Pages\CreateIncubator::route('/create'),
            'edit' => Pages\EditIncubator::route('/{record}/edit'),
        ];
    }
}
