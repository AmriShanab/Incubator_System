<?php

namespace App\Filament\Resources\IncubatorResource\Pages;

use App\Filament\Resources\IncubatorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncubator extends CreateRecord
{
    protected static string $resource = IncubatorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sku'] = IncubatorResource::generateSku($data['name'] ?? null);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
