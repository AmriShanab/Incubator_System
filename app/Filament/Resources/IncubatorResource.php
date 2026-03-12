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
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('General Information')
                            ->description('Basic details and identification for this incubator.')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 120 Egg Incubator')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., INC-120'),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pricing & Availability')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Selling Price')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required()
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Live Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled() // Locked because Production Logs manage this
                                    ->dehydrated(false)
                                    ->helperText('Updated automatically via Production Logs.'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Details')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    // Combines SKU under the Name in gray text for a cleaner table
                    ->description(fn (Incubator $record): string => 'SKU: ' . ($record->sku ?? 'N/A')),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available Stock')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Selling Price')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd(), // Aligns numbers to the right (Accounting standard)

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
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