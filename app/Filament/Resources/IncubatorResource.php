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
use Illuminate\Support\Str;

class IncubatorResource extends Resource
{
    protected static ?string $model = Incubator::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Products';

    public static function canViewAny(): bool
    {
        return in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['admin', 'inventory']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('General Information')
                            ->description('Basic details and identification for this product.')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 120 Egg Product')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Forms\Set $set, string $operation) {
                                        if ($operation !== 'create') {
                                            return;
                                        }

                                        $set('sku', static::generateSku($state));
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->helperText('Auto-generated from product name.'),
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

                                // NEW: Unit of Measure Dropdown
                                Forms\Components\Select::make('uom')
                                    ->label('Unit of Measure')
                                    ->options([
                                        'pcs' => 'Pieces (pcs)',
                                        'kg'  => 'Kilograms (kg)',
                                        'm'   => 'Meters (m)',
                                        'l'   => 'Liters (l)',
                                    ])
                                    ->default('pcs')
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Live Stock')
                                    ->numeric()
                                    ->step('any') // ALLOWS DECIMALS!
                                    ->default(0)
                                    ->disabled() // Locked because Production Logs manage this
                                    ->dehydrated(false)
                                    ->helperText('Updated automatically via Production Logs.'),

                                Forms\Components\TextInput::make('low_stock_cycles')
                                    ->label('Low Stock Cycles')
                                    ->numeric()
                                    ->step('any')
                                    ->default(2)
                                    ->minValue(0.01)
                                    ->required()
                                    ->helperText('Item turns red when stock is below this production-cycle count.'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('materials'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Details')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Incubator $record): string => 'SKU: ' . ($record->sku ?? 'N/A')),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available Stock')
                    ->badge()
                    ->formatStateUsing(fn($state, Incubator $record): string => $state . ' ' . ($record->uom ?? 'pcs'))
                    ->color(fn(int|float $state, Incubator $record): string => match (true) {
                        $state < (float) ($record->low_stock_cycles ?? 2) => 'danger',
                        $state < ((float) ($record->low_stock_cycles ?? 2) * 2) => 'warning',
                        $state <= 0 => 'danger',
                        default => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost')
                    ->label('Cost')
                    ->state(fn(Incubator $record): float => (float) $record->materials->sum(
                        fn($material) => ((float) ($material->cost_per_unit ?? 0)) * ((float) ($material->pivot->quantity_required ?? 0))
                    ))
                    ->money('LKR')
                    ->sortable(false)
                    ->color('warning')
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'admin')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Selling Price')
                    ->money('LKR')
                    ->sortable()
                    ->color('primary')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Profit Margin')
                    ->state(function (Incubator $record): float {
                        $cost = (float) $record->materials->sum(
                            fn($material) => ((float) ($material->cost_per_unit ?? 0)) * ((float) ($material->pivot->quantity_required ?? 0))
                        );
                        return (float) $record->price - $cost;
                    })
                    ->money('LKR')
                    ->weight('bold')
                    ->color(fn(float $state): string => $state >= 0 ? 'success' : 'danger')
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'admin')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
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

    public static function generateSku(?string $name, ?int $ignoreRecordId = null): string
    {
        $slug = Str::upper(Str::slug((string) $name, ''));
        $base = 'PRD-' . ($slug !== '' ? Str::substr($slug, 0, 8) : 'ITEM');

        $sku = $base;
        $counter = 1;

        while (Incubator::query()
            ->when($ignoreRecordId, fn($query) => $query->whereKeyNot($ignoreRecordId))
            ->where('sku', $sku)
            ->exists()
        ) {
            $sku = $base . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }
}
