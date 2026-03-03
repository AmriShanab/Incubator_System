<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionLogResource\Pages;
use App\Models\ProductionLog;
use App\Models\Incubator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ProductionLogResource extends Resource
{
    protected static ?string $model = ProductionLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Production Details')
                    ->schema([
                        Forms\Components\Select::make('incubator_id')
                            ->relationship('incubator', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live() // Essential to track stock for the selected product
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('quantity_produced', null)),

                        Forms\Components\TextInput::make('quantity_produced')
                            ->label('Quantity Built')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->live(onBlur: true)
                            // 1. Reactive Warning for the UI
                            ->afterStateUpdated(function ($state, Forms\Get $get) {
                                $incubatorId = $get('incubator_id');
                                if (!$incubatorId || !$state) return;

                                $incubator = Incubator::find($incubatorId);

                                foreach ($incubator->materials as $material) {
                                    // FIXED: pivot->quantity_required
                                    $needed = $material->pivot->quantity_required * $state;

                                    // FIXED: current_stock
                                    if ($material->current_stock < $needed) {
                                        Notification::make()
                                            ->title('Stock Shortage Detected')
                                            ->body("Insufficient {$material->name}. You need {$needed} but only have {$material->current_stock} in stock.")
                                            ->danger()
                                            ->send();
                                    }
                                }
                            })
                            // 2. Hard Stop Validation (Prevents Saving)
                            ->rules([
                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $incubatorId = $get('incubator_id');
                                    if (!$incubatorId) return;

                                    $incubator = Incubator::with('materials')->find($incubatorId);

                                    foreach ($incubator->materials as $material) {
                                        // FIXED: pivot->quantity_required
                                        $totalNeeded = $material->pivot->quantity_required * $value;

                                        // FIXED: current_stock
                                        if ($material->current_stock < $totalNeeded) {
                                            $fail("Insufficient {$material->name} in stock. Required: {$totalNeeded}, Available: {$material->current_stock}.");
                                        }
                                    }
                                },
                            ]),

                        Forms\Components\DatePicker::make('production_date')
                            ->required()
                            ->default(now()),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('quantity_produced')
                    ->label('Qty Built')
                    ->sortable(),
                Tables\Columns\TextColumn::make('production_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionLogs::route('/'),
            'create' => Pages\CreateProductionLog::route('/create'),
            'edit' => Pages\EditProductionLog::route('/{record}/edit'),
        ];
    }
}
