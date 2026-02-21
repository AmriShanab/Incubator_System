<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReturnResource\Pages;
use App\Filament\Resources\SalesReturnResource\RelationManagers;
use App\Models\SalesReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Return Details')->schema([
                    Forms\Components\Select::make('invoice_id')
                        ->label('Select Invoice')
                        ->relationship('invoice', 'id')
                        ->getOptionLabelFromRecordUsing(fn($record) => "Inv #{$record->id} - {$record->customer->name}")
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),

                    Forms\Components\DatePicker::make('return_date')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('refund_amount')
                        ->numeric()
                        ->prefix('LKR')
                        ->required(),

                    Forms\Components\Select::make('reason')
                        ->options([
                            'defective' => 'Defective / Broken',
                            'wrong_item' => 'Wrong Item Sent',
                            'customer_change' => 'Customer Changed Mind',
                        ])
                        ->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.id')->label('Inv #'),
                Tables\Columns\TextColumn::make('invoice.customer.name')->label('Customer'),
                Tables\Columns\TextColumn::make('return_date')->date(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn(string $state): string => $state === 'completed' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('refund_amount')->money('LKR'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // THE LOGIC BUTTON: PROCESS RETURN
                Tables\Actions\Action::make('complete')
                    ->label('Process & Restock')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        // Loop through the items in the original invoice
                        foreach ($record->invoice->items as $item) {
                            $product = $item->sellable;
                            if ($product) {
                                // PUT THE ITEMS BACK IN STOCK
                                $product->increment('current_stock', $item->quantity);
                            }
                        }

                        $record->update(['status' => 'completed']);

                        \Filament\Notifications\Notification::make()
                            ->title('Items Restocked')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListSalesReturns::route('/'),
            'create' => Pages\CreateSalesReturn::route('/create'),
            'edit' => Pages\EditSalesReturn::route('/{record}/edit'),
        ];
    }
}
