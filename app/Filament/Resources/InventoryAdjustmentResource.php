<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryAdjustmentResource\Pages;
use App\Models\InventoryAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryAdjustmentResource extends Resource
{
    protected static ?string $model = InventoryAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Stock Adjustments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Adjustment Details')->schema([
                    // 1. Select Item Type (Polymorphic)
                    Forms\Components\MorphToSelect::make('adjustable')
                        ->label('Item to Adjust')
                        ->types([
                            Forms\Components\MorphToSelect\Type::make(\App\Models\Material::class)
                                ->titleAttribute('name')
                                ->label('Raw Material'),
                            Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)
                                ->titleAttribute('name')
                                ->label('Accessory / Part'),
                            Forms\Components\MorphToSelect\Type::make(\App\Models\Incubator::class)
                                ->titleAttribute('name')
                                ->label('Finished Incubator'),
                        ])
                        ->searchable()
                        ->preload()
                        ->required(),

                    // 2. Quantity (Positive or Negative)
                    Forms\Components\TextInput::make('quantity')
                        ->label('Adjustment Quantity')
                        ->numeric()
                        ->required()
                        ->helperText('Enter NEGATIVE for loss (e.g., -5) or POSITIVE for gain (e.g., 5).'),

                    // 3. Reason / Type
                    Forms\Components\Select::make('type')
                        ->options([
                            'wastage' => 'Wastage (Production Loss)',
                            'damage' => 'Damaged Stock',
                            'audit' => 'Audit Correction (Found/Lost)',
                            'theft' => 'Theft / Missing',
                            'return' => 'Customer Return (Restock)',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('adjustment_date')
                        ->default(now())
                        ->required(),

                    Forms\Components\Textarea::make('reason')
                        ->label('Notes / Reason')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('adjustable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge(),

                Tables\Columns\TextColumn::make('adjustable.name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wastage', 'damage', 'theft' => 'danger',
                        'audit' => 'warning',
                        'return' => 'success',
                    }),

                Tables\Columns\TextColumn::make('adjustment_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('adjustment_date', 'desc')
            ->actions([
                // We typically DO NOT allow editing adjustments to preserve audit history
                // But deleting is okay if it reverses the stock (advanced logic)
                // For now, let's just View
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryAdjustments::route('/'),
            'create' => Pages\CreateInventoryAdjustment::route('/create'),
        ];
    }
}