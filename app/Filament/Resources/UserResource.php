<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';

    // ── Authorization: Only check your 'role' column ──
    public static function canViewAny(): bool
    {
        // Checks the actual 'role' column in your users table
        return Auth::user()->role === 'admin'; 
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Security')
                    ->description('Leave blank on edit if you do not want to change the password.')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::default())
                            // Only save the password if it's filled
                            ->dehydrated(fn ($state) => filled($state))
                            // Hash the password before saving
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->revealable()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Role Assignment')
                    ->schema([
                        // Using a simple Select that writes directly to your 'role' column
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'cashier' => 'Cashier',
                                'inventory' => 'Inventory Manager',
                            ])
                            ->required()
                            ->default('cashier')
                            ->native(false), // Makes the dropdown look nicer in Filament
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                // Displaying your 'role' column with nice formatting
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'cashier' => 'Cashier',
                        'inventory' => 'Inventory Manager',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'inventory' => 'warning',
                        'cashier' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'cashier' => 'Cashier',
                        'inventory' => 'Inventory Manager',
                    ])
            ])
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}