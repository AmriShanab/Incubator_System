<?php

namespace App\Filament\Resources\ProductionLogResource\Pages;

use App\Filament\Resources\ProductionLogResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\ProductionService;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Models\ProductionLog; // <-- Make sure to import your model

class CreateProductionLog extends CreateRecord
{
    protected static string $resource = ProductionLogResource::class;

    /**
     * Hijack Filament's default save behavior and route it through our Service.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $service = app(ProductionService::class);

        try {
            return $service->logProduction(
                incubatorId: (int) $data['incubator_id'],
                quantity: (float) $data['quantity_produced'],
                date: $data['production_date']
            );
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Production Blocked')
                ->body($e->getMessage()) 
                ->persistent()
                ->send();

            $this->halt();
            return new ProductionLog();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
