<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundTransferResource\Pages;
use App\Models\FundTransfer;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FundTransferResource extends Resource
{
    protected static ?string $model = FundTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    
    protected static ?string $navigationGroup = 'Accounting';
    
    protected static ?string $navigationLabel = 'Fund Transfers';

    // RBAC: STRICTLY ADMIN ONLY
    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('transfer_date')
                    ->default(now())
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Select::make('from_account_id')
                    ->relationship('fromAccount', 'name')
                    ->label('Transfer OUT of (Source)')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live() // Make it react instantly
                    ->hint(function (Get $get) {
                        $accountId = $get('from_account_id');
                        if (!$accountId) return null;
                        
                        $account = Account::find($accountId);
                        return $account ? 'Available: LKR ' . number_format($account->balance, 2) : null;
                    })
                    ->hintColor('danger'),

                Forms\Components\Select::make('to_account_id')
                    ->relationship('toAccount', 'name')
                    ->label('Transfer INTO (Destination)')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->different('from_account_id')
                    ->validationMessages([
                        'different' => 'The destination account must be different from the source account.',
                    ])
                    ->live() // Make it react instantly
                    ->hint(function (Get $get) {
                        $accountId = $get('to_account_id');
                        if (!$accountId) return null;
                        
                        $account = Account::find($accountId);
                        return $account ? 'Current Balance: LKR ' . number_format($account->balance, 2) : null;
                    })
                    ->hintColor('success'),

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('LKR')
                    ->minValue(1)
                    // BONUS: Prevent transferring more money than the source account actually has!
                    ->maxValue(function (Get $get) {
                        $sourceAccountId = $get('from_account_id');
                        if ($sourceAccountId) {
                            $account = Account::find($sourceAccountId);
                            return $account ? $account->balance : null;
                        }
                        return null;
                    })
                    ->columnSpanFull(),

                // ADDED: The Pool Type Selection
                Forms\Components\Select::make('pool_type')
                    ->label('Source of Funds')
                    ->options([
                        'capital' => 'Investment / Capital Pool',
                        'profit' => 'Profit / Revenue Pool',
                    ])
                    ->required()
                    ->default('capital')
                    ->helperText('Which pool is this money being transferred from?')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('reference_note')
                    ->label('Note / Reason')
                    ->placeholder('e.g., Deposited weekend cash into the bank')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['fromAccount', 'toAccount']))
            ->columns([
                Tables\Columns\TextColumn::make('transfer_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fromAccount.name')
                    ->label('From')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-arrow-up-right'),

                Tables\Columns\TextColumn::make('toAccount.name')
                    ->label('To')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-right'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('LKR')
                    ->weight('bold')
                    ->sortable(),

                // ADDED: The Pool Type Badge
                Tables\Columns\TextColumn::make('pool_type')
                    ->label('Fund Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'profit' => 'success',
                        'capital' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('reference_note')
                    ->label('Note')
                    ->limit(30)
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFundTransfers::route('/'),
        ];
    }
}