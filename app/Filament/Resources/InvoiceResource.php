<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

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
                        Forms\Components\Section::make('Invoice Items')
                            ->icon('heroicon-o-shopping-cart')
                            ->headerActions([
                                Forms\Components\Actions\Action::make('reset')
                                    ->modalHeading('Clear all items?')
                                    ->color('danger')
                                    ->action(fn(Forms\Set $set) => $set('items', [])),
                            ])
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\MorphToSelect::make('sellable')
                                            ->label('Product / Supply')
                                            ->types([
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Incubator::class)->titleAttribute('name')->label('Product'),
                                                Forms\Components\MorphToSelect\Type::make(\App\Models\Accessory::class)->titleAttribute('name')->label('Supply'),
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(2)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state['sellable_type'] && $state['sellable_id']) {
                                                    $modelClass = $state['sellable_type'];
                                                    $record = $modelClass::find($state['sellable_id']);

                                                    if ($record) {
                                                        $price = $record->selling_price ?? $record->price ?? 0;

                                                        $cost = 0;
                                                        if (class_basename($modelClass) === 'Accessory') {
                                                            $cost = $record->cost_price ?? 0;
                                                        } else {
                                                            $cost = $record->cost ?? \Illuminate\Support\Facades\DB::table('incubator_material')
                                                                ->join('materials', 'incubator_material.material_id', '=', 'materials.id')
                                                                ->where('incubator_id', $record->id)
                                                                ->selectRaw('SUM(incubator_material.quantity_required * materials.cost_per_unit) as calculated_cost')
                                                                ->value('calculated_cost') ?? 0;
                                                        }

                                                        $set('unit_price', $price);
                                                        $set('unit_cost', $cost);
                                                        $set('row_total', $price * (int)$get('quantity'));
                                                    }
                                                }
                                                $total = collect($get('../../items'))->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                                $set('../../total_amount', $total);
                                            }),

                                        Forms\Components\Hidden::make('unit_cost')->default(0),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('LKR')
                                            ->required()
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $set('row_total', (float)$state * (int)$get('quantity'));
                                                $total = collect($get('../../items'))->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                                $set('../../total_amount', $total);
                                            }),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->rules([
                                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $type = $get('sellable_type');
                                                    $id = $get('sellable_id');
                                                    if ($type && $id) {
                                                        $product = $type::find($id);
                                                        if ($product && $value > $product->current_stock) {
                                                            $fail("Out of stock! Only {$product->current_stock} available.");
                                                        }
                                                    }
                                                },
                                            ]),

                                        Forms\Components\TextInput::make('row_total')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('LKR')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(5)
                                    ->defaultItems(1)
                                    ->reorderable(true)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $items = $get('items');
                                        $total = collect($items)->sum(fn($item) => (float)($item['row_total'] ?? 0));
                                        $set('total_amount', $total);
                                    }),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Client & Logistics')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->label('Customer')
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('phone')->tel(),
                                    ]),

                                Forms\Components\DatePicker::make('invoice_date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('tracking_number')
                                    ->label('Tracking Number')
                                    ->placeholder('e.g. PX12345678')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-qr-code'),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'processing' => 'Processing',
                                        'out_for_delivery' => 'Out for Delivery',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\Section::make('Payment Info')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->relationship('account', 'name')
                                    ->label('Payment Method')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Grand Total')
                                    ->content(fn(Forms\Get $get) => 'LKR ' . number_format($get('total_amount') ?? 0, 2))
                                    ->extraAttributes(['class' => 'text-2xl font-bold text-success-600']),

                                Forms\Components\Hidden::make('total_amount')->default(0),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Order Reference')
                    ->searchable(['tracking_number', 'id'])
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->color(fn(Invoice $record) => $record->tracking_number ? 'primary' : 'gray')
                    ->icon(fn(Invoice $record) => $record->tracking_number ? 'heroicon-m-qr-code' : 'heroicon-m-hashtag')
                    ->url(function (Invoice $record) {
                        if (!$record->tracking_number) return null;
                        return "https://www.trackingwebsite.com/track/{$record->tracking_number}";
                    })
                    ->openUrlInNewTab()
                    ->getStateUsing(fn(Invoice $record) => $record->tracking_number ?: 'INV-' . str_pad($record->id, 5, '0', STR_PAD_LEFT))
                    ->description(function (Invoice $record): string {
                        $invStr = 'INV-' . str_pad($record->id, 5, '0', STR_PAD_LEFT);
                        return $record->tracking_number ? "Internal: {$invStr} | Date: {$record->invoice_date}" : "Date: {$record->invoice_date}";
                    }),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn(Invoice $record): string => ucwords(str_replace('_', ' ', $record->payment_method))),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'out_for_delivery' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'credit' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('LKR')
                    ->sortable()
                    ->weight('bold')
                    ->alignEnd()
                    ->description(fn(Invoice $record) => $record->payment_status !== 'paid' ? 'Due: LKR ' . number_format($record->total_amount - $record->amount_paid, 2) : '')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('LKR')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([

                    // ── POST-SALE FIX: Revert to Credit ──
                    Tables\Actions\Action::make('markAsCredit')
                        ->label('Revert to Credit')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Convert to Credit Sale?')
                        ->modalDescription('This will set the amount paid to zero, mark the invoice as "Credit", and reverse the physical account balances.')
                        ->visible(fn(Invoice $record) => $record->payment_status !== 'credit' && $record->amount_paid > 0)
                        ->action(function (Invoice $record) {

                            DB::transaction(function () use ($record) {
                                $paidAmount = (float) $record->amount_paid;

                                if ($paidAmount > 0) {
                                    // Identify the ultimate destination for Credit debt
                                    $arAccount = \App\Models\Account::where('name', 'Accounts Receivable')->first();

                                    // Identify where the money is CURRENTLY sitting
                                    $cashAccount = $record->account ?? \App\Models\Account::where('name', 'Cash')->first();

                                    if ($arAccount && $cashAccount) {

                                        $capitalToReverse = min($paidAmount, $record->total_cost ?? 0);
                                        $profitToReverse  = max(0, $paidAmount - $capitalToReverse);

                                        // 1. Take the money OUT of Cash/Bank/COD
                                        $record->transactions()->create([
                                            'account_id'       => $cashAccount->id,
                                            'type'             => 'out',
                                            'amount'           => $paidAmount,
                                            'description'      => "Reversed payment for Invoice #{$record->id}",
                                            'transaction_date' => now()->toDateString(),
                                        ]);
                                        $cashAccount->decrement('balance', $paidAmount);
                                        $cashAccount->decrement('capital_pool', $capitalToReverse);
                                        $cashAccount->decrement('profit_pool', $profitToReverse);

                                        // 2. Put the debt back INTO Accounts Receivable
                                        $record->transactions()->create([
                                            'account_id'       => $arAccount->id,
                                            'type'             => 'in',
                                            'amount'           => $paidAmount,
                                            'description'      => "Debt reinstated for Invoice #{$record->id}",
                                            'transaction_date' => now()->toDateString(),
                                        ]);
                                        $arAccount->increment('balance', $paidAmount);
                                        $arAccount->increment('capital_pool', $capitalToReverse);
                                        $arAccount->increment('profit_pool', $profitToReverse);
                                    }
                                }

                                // 3. Update Invoice status AND properly link it to Accounts Receivable
                                $record->update([
                                    'amount_paid'    => 0,
                                    'payment_status' => 'credit',
                                    'account_id'     => isset($arAccount) ? $arAccount->id : $record->account_id,
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Sale reverted to credit')
                                ->body('Account balances have been successfully reversed.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('process')
                        ->label('Start Processing')
                        ->icon('heroicon-m-play')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => $record->status === 'draft')
                        ->action(fn(Invoice $record) => $record->update(['status' => 'processing'])),

                    Tables\Actions\Action::make('dispatch')
                        ->label('Dispatch')
                        ->icon('heroicon-m-truck')
                        ->color('warning')
                        ->visible(fn(Invoice $record) => $record->status === 'processing')
                        ->form([
                            Forms\Components\TextInput::make('tracking_number')
                                ->label('Courier Tracking Number')
                                ->placeholder('Scan barcode or type here...')
                                ->required()
                                ->autofocus()
                                ->maxLength(255),
                        ])
                        ->action(function (Invoice $record, array $data) {
                            $record->update([
                                'status' => 'out_for_delivery',
                                'tracking_number' => $data['tracking_number'],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Dispatched!')
                                ->body('Tracking number saved.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('deliver')
                        ->label('Mark Delivered')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => $record->status === 'out_for_delivery')
                        ->action(fn(Invoice $record) => $record->update(['status' => 'delivered'])),

                    // ── FIX: Receive Payment (Now respects the specific Invoice's pending Account) ──
                    Tables\Actions\Action::make('receive_payment')
                        ->label('Receive Payment')
                        ->icon('heroicon-m-banknotes')
                        ->color('success')
                        ->visible(fn(Invoice $record) => in_array($record->payment_status, ['partial', 'credit']) && $record->status === 'delivered')
                        ->form(function (Invoice $record) {
                            $due = max(0, $record->total_amount - $record->amount_paid);
                            return [
                                Forms\Components\Placeholder::make('amount_due_display')
                                    ->label('Remaining Balance')
                                    ->content('LKR ' . number_format($due, 2))
                                    ->extraAttributes(['class' => 'text-xl font-bold text-danger-600']),

                                Forms\Components\TextInput::make('payment_amount')
                                    ->label('Payment Amount')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->default($due)
                                    ->maxValue($due)
                                    ->minValue(1)
                                    ->required(),

                                Forms\Components\Select::make('account_id')
                                    ->label('Deposit To')
                                    ->options(\App\Models\Account::where('name', '!=', 'Accounts Receivable')->pluck('name', 'id'))
                                    ->required()
                                    ->default(fn() => \App\Models\Account::where('name', 'Cash')->first()?->id),
                            ];
                        })
                        ->action(function (Invoice $record, array $data) {
                            DB::transaction(function () use ($record, $data) {
                                $paidNow = (float) $data['payment_amount'];
                                $destAccount = \App\Models\Account::find($data['account_id']);
                                
                                // NEW: Dynamically pull from the exact account holding this debt (COD, AR, etc.)
                                $pendingAccount = $record->account; 

                                if (!$destAccount || !$pendingAccount) return;

                                // Recalculate how much capital vs profit we are collecting right now
                                $oldAmountPaid = $record->amount_paid;
                                $remainingCapitalToRecover = max(0, $record->total_cost - $oldAmountPaid);

                                $recoveredCapital = min($paidNow, $remainingCapitalToRecover);
                                $recoveredProfit = max(0, $paidNow - $recoveredCapital);

                                // 1. Pull the money OUT of the pending pool (COD, AR, etc.)
                                $record->transactions()->create([
                                    'account_id' => $pendingAccount->id,
                                    'type' => 'out',
                                    'amount' => $paidNow,
                                    'description' => "Debt collected for Invoice #{$record->id}",
                                    'transaction_date' => now()->toDateString(),
                                ]);
                                $pendingAccount->decrement('balance', $paidNow);
                                $pendingAccount->decrement('capital_pool', $recoveredCapital);
                                $pendingAccount->decrement('profit_pool', $recoveredProfit);

                                // 2. Put the physical cash INTO the actual Cash/Bank account
                                $record->transactions()->create([
                                    'account_id' => $destAccount->id,
                                    'type' => 'in',
                                    'amount' => $paidNow,
                                    'description' => "Late payment received for Invoice #{$record->id}",
                                    'transaction_date' => now()->toDateString(),
                                ]);
                                $destAccount->increment('balance', $paidNow);
                                $destAccount->increment('capital_pool', $recoveredCapital);
                                $destAccount->increment('profit_pool', $recoveredProfit);

                                // 3. Update the Invoice status
                                $newTotalPaid = $oldAmountPaid + $paidNow;
                                $newStatus = $newTotalPaid >= $record->total_amount ? 'paid' : 'partial';

                                $record->updateQuietly([
                                    'amount_paid' => $newTotalPaid,
                                    'payment_status' => $newStatus,
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Payment Applied Successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Invoice')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(Invoice $record) => in_array($record->status, ['draft', 'processing', 'out_for_delivery']))
                        ->action(fn(Invoice $record) => $record->update(['status' => 'cancelled'])),

                    Tables\Actions\Action::make('print')
                        ->label('Print Invoice')
                        ->icon('heroicon-m-printer')
                        ->color('gray')
                        ->url(fn(Invoice $record) => route('invoice.print', $record))
                        ->openUrlInNewTab(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}