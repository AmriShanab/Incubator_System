<?php

namespace App\Filament\Resources\IncubatorResource\Pages;

use App\Filament\Resources\IncubatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncubator extends EditRecord
{
    protected static string $resource = IncubatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
