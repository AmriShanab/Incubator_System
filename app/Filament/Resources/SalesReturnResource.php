<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReturnResource\Pages;
use App\Models\SalesReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Sales'; 
    
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::check() && in_array(\Illuminate\Support\Facades\Auth::user()->role, ['admin', 'cashier']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Return Details')
                            ->description('Link this return to the original invoice.')
                            ->icon('heroicon-o-document-magnifying-glass')
                            ->schema([
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Original Invoice')
                                    ->relationship('invoice', 'id')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "INV-{$record->id} | {$record->customer->name} (Total: LKR " . number_format($record->total_amount, 2) . ")")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $invoice = \App\Models\Invoice::find($state);
                                            $set('refund_amount', $invoice?->total_amount ?? 0);
                                        }
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('reason')
                                    ->label('Reason for Return')
                                    ->options([
                                        'defective' => 'Defective / Broken',
                                        'wrong_item' => 'Wrong Item Sent',
                                        'customer_change' => 'Customer Changed Mind',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Financials')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\DatePicker::make('return_date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('refund_amount')
                                    ->label('Amount to Refund')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required()
                                    ->helperText('This amount will be automatically split and deducted from the Capital and Profit pools of the account that originally received the payment.'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.id')
                    ->label('Invoice')
                    ->weight('bold')
                    ->prefix('INV-')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SalesReturn $record): string => $record->invoice->customer->name ?? 'Unknown Customer'),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Returned On')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Refunded')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'completed' ? 'success' : 'warning')
                    ->alignEnd(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('complete')
                    ->label('Process Refund')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Process Return & Refund?')
                    ->modalDescription('Please select which account you are taking the refund money out of. This will restock inventory and deduct the funds.')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form(fn (SalesReturn $record) => [
                        Forms\Components\Placeholder::make('refund_display')
                            ->label('Amount to Refund')
                            ->content('LKR ' . number_format($record->refund_amount, 2))
                            ->extraAttributes(['class' => 'text-xl font-bold text-danger-600']),

                        Forms\Components\Select::make('refund_account_id')
                            ->label('Issue Refund From (Account)')
                            ->options(\App\Models\Account::pluck('name', 'id'))
                            ->default(fn() => $record->invoice->account_id) 
                            ->required()
                            ->native(false)
                            ->rules([
                                fn (): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    $account = \App\Models\Account::find($value);
                                    if ($account && $account->balance < $record->refund_amount) {
                                        $fail("Insufficient funds! {$account->name} only has LKR " . number_format($account->balance, 2) . " available.");
                                    }
                                },
                            ]),
                    ])
                    ->action(function (SalesReturn $record, array $data, \App\Services\SalesReturnService $service) {
                        try {
                            $service->processRefund($record, $data['refund_account_id']);
                            
                            Notification::make()
                                ->title('Refund Processed')
                                ->body('Inventory restocked and funds deducted successfully.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Refund Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => \Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->role === 'admin'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesReturns::route('/'),
            'create' => Pages\CreateSalesReturn::route('/create'),
            'edit' => Pages\EditSalesReturn::route('/{record}/edit'),
        ];
    }
}