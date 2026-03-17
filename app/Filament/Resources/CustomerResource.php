<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; 

    protected static ?string $navigationGroup = 'Sales'; 
    
    // Puts it near the top of the Sales group
    protected static ?int $navigationSort = 1; 

    public static function canViewAny(): bool
    {
        return in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['admin', 'cashier']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Personal Information')
                            ->description('Basic contact details for this client.')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Full Name')
                                    ->prefixIcon('heroicon-m-user'),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->label('Phone Number')
                                    ->prefixIcon('heroicon-m-phone')
                                    ->placeholder('+94 7X XXX XXXX'),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Location Details')
                            ->description('Shipping and billing address.')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->rows(4)
                                    ->label('Complete Address')
                                    ->placeholder('Enter street address, city, and postal code...'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer Profile')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    // Places the phone number neatly under the name
                    ->description(fn (Customer $record): string => $record->phone ?? 'No phone provided'),

                Tables\Columns\TextColumn::make('address')
                    ->label('Location')
                    ->icon('heroicon-m-map-pin')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),

                // Premium Feature: Show total orders to spot VIP customers
                Tables\Columns\TextColumn::make('invoices_count')
                    ->counts('invoices')
                    ->label('Total Orders')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                // Useful quick-action to call them directly from mobile
                Tables\Actions\Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-m-phone')
                    ->color('success')
                    ->url(fn (Customer $record) => "tel:{$record->phone}")
                    ->visible(fn (Customer $record) => filled($record->phone)),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}