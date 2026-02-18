<?php

namespace App\Filament\Resources\IncubatorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                // We usually don't create NEW materials here, we attach existing ones.
                // So this form is less important than the "Attach" action below.
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('unit'),

                // This column comes from the PIVOT table (the recipe quantity)
                Tables\Columns\TextColumn::make('quantity_required')
                    ->label('Quantity Needed')
                    ->suffix(fn($record) => ' ' . $record->unit),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // This allows you to select an existing material and set the quantity
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn(Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('quantity_required')
                            ->label('Quantity Required per Incubator')
                            ->required()
                            ->numeric()
                            ->default(1),
                    ]),
            ])
            ->actions([
                // Allows you to edit the quantity later
                Tables\Actions\EditAction::make(),
                // Allows you to remove a material from the recipe
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
