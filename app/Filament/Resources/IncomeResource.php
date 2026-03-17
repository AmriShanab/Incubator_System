<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeResource\Pages;
use App\Models\Income;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    
    protected static ?string $navigationGroup = 'Accounting';

    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Record Income')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Income Title / Source')
                            ->placeholder('e.g., Advance for Custom Order')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name')
                            ->label('Deposit To Account')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('pool_type')
                            ->label('Assign To Pool')
                            ->options([
                                'capital' => 'Investment (Capital Pool)',
                                'profit' => 'Free Profit (Profit Pool)',
                            ])
                            ->required()
                            ->helperText('Capital is for business reinvestment. Profit is free cash.'),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('LKR')
                            ->minValue(0.01),

                        Forms\Components\DatePicker::make('income_date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('income_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('account.name')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pool_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'capital' => 'warning',
                        'profit' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('amount')
                    ->money('LKR')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('LKR')),
            ])
            ->defaultSort('income_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(false), // Disable editing to protect accounting integrity
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
        ];
    }
}