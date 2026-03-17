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

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Supply Chain'; 
    
    protected static ?int $navigationSort = 1;

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
                        Forms\Components\Section::make('Company Profile')
                            ->description('Primary details for this vendor.')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Forms\Components\TextInput::make('company_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Company Name')
                                    ->placeholder('e.g., City Hardware & Timber')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('contact_person')
                                    ->maxLength(255)
                                    ->label('Primary Contact Person')
                                    ->placeholder('e.g., John Doe')
                                    ->prefixIcon('heroicon-m-user')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Contact Information')
                            ->description('How to reach this supplier.')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->placeholder('sales@company.com'),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-m-phone')
                                    ->placeholder('+94 7X XXX XXXX'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-storefront')
                    // Show the contact person right underneath the company name
                    ->description(fn (Supplier $record): string => 'Contact: ' . ($record->contact_person ?? 'N/A')),

                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email address copied')
                    ->searchable(),

                // Premium Feature: Total Purchase Orders Counter
                Tables\Columns\TextColumn::make('purchase_orders_count')
                    ->counts('purchaseOrders')
                    ->label('Total Orders')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('company_name', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Quick Action: Send an Email directly from the table
                Tables\Actions\Action::make('email_supplier')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->url(fn (Supplier $record) => "mailto:{$record->email}")
                    ->visible(fn (Supplier $record) => filled($record->email)),
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
        return [
            //
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