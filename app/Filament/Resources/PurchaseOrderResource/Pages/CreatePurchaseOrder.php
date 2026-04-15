<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Models\PurchaseOrder;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(PurchaseService::class);

        try {
            // Hand off to the service
            return $service->createOrder(
                data: $data,
                items: $data['items'] ?? []
            );

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Order Failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();

            $this->halt();
            return new PurchaseOrder();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}