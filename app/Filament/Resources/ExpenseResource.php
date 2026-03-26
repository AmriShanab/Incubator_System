<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Account;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Accounting';

    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->label('Pay From Account')
                            ->options(Account::where('name', '!=', 'COD Partner')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->disabledOn('edit'), // Lock after creation for ledger safety

                        Forms\Components\Select::make('category')
                            ->options([
                                'Utilities' => 'Utilities (Electricity, Internet)',
                                'Salary' => 'Salaries & Wages',
                                'Marketing' => 'Marketing & Ads',
                                'Maintenance' => 'Maintenance & Repairs',
                                'Logistics' => 'Logistics & Transport',
                                'Other' => 'Other / Miscellaneous',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->prefix('LKR')
                            ->disabledOn('edit')
                            // ADD THIS NEW RULES BLOCK:
                            ->rules([
                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $accountId = $get('account_id');
                                    if ($accountId) {
                                        $account = \App\Models\Account::find($accountId);
                                        if ($account && $value > $account->balance) {
                                            $fail("Insufficient funds. This account only has LKR " . number_format($account->balance, 2));
                                        }
                                    }
                                },
                            ]),

                        Forms\Components\DatePicker::make('expense_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->maxLength(255),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Paid From')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->searchable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Are you sure? Deleting this expense will refund the money back to the account balance.')
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
