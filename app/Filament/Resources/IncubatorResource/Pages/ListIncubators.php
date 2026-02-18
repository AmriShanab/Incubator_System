<?php

namespace App\Filament\Resources\IncubatorResource\Pages;

use App\Filament\Resources\IncubatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncubators extends ListRecords
{
    protected static string $resource = IncubatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
