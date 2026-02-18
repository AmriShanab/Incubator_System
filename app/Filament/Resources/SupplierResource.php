<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    // Icon representing a "Company" or "Store"
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Supply Chain'; // Optional: Groups this in the sidebar

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier Information')->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->required()
                        ->maxLength(255)
                        ->label('Company Name')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('contact_person')
                        ->maxLength(255)
                        ->label('Contact Person'),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-m-envelope'), // CHANGED: icon -> prefixIcon

                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20)
                        ->prefixIcon('heroicon-m-phone'),    // CHANGED: icon -> prefixIcon
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable() // Allows searching by name
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->label('Contact'),

                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->copyable(), // Click to copy number

                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->copyable(),
            ])
            ->defaultSort('company_name', 'asc')
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
            // Later we can add "PurchaseOrdersRelationManager" here 
            // to see all POs for this supplier!
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
