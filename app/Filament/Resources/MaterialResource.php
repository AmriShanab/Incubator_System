<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Raw Materials';

    public static function canViewAny(): bool
    {
        return in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['admin', 'inventory']);
    }

    public static function getNavigationBadge(): ?string
    {
        $lowStockCount = static::getModel()::where('current_stock', '<=', 10)->count();
        return $lowStockCount > 0 ? (string) $lowStockCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Material Profile')
                            ->description('Basic details and measurement unit for this material.')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('e.g., MDF Board (8x4)')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('unit')
                                    ->label('Unit of Measurement')
                                    ->options([
                                        'pcs' => 'Pieces (pcs)',
                                        'm' => 'Meters (m)',
                                        'kg' => 'Kilograms (kg)',
                                        'l' => 'Liters (l)',
                                    ])
                                    ->required()
                                    ->default('pcs')
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Valuation & Stock')
                            ->schema([
                                Forms\Components\TextInput::make('cost_per_unit')
                                    ->label('Cost Per Unit (Updates automatically via PO)')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required()
                                    ->default(0),

                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Live Stock Level')
                                    ->numeric()
                                    ->default(0)
                                    ->disabledOn('edit')
                                    ->helperText('Updated automatically via Purchase Orders and Production Logs.'),
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
                    ->description(fn (Material $record): string => 'Sold/Used in: ' . strtoupper($record->unit)),

                Tables\Columns\TextColumn::make('cost_per_unit')
                    ->label('Unit Cost')
                    ->money('LKR')
                    ->sortable()
                    ->color('primary')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Live Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 10 => 'danger',
                        $state <= 25 => 'warning',
                        default => 'success',
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('asset_value')
                    ->label('Total Asset Value')
                    ->state(fn (Material $record): float => (float) ($record->cost_per_unit * $record->current_stock))
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->color('gray')
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
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}