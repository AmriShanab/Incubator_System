<?php

namespace App\Filament\Resources\InventoryAdjustmentResource\Pages;

use App\Filament\Resources\InventoryAdjustmentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInventoryAdjustment extends CreateRecord
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function afterCreate(): void
    {
        $adjustment = $this->record;

        // Get the item (Material, Accessory, or Incubator)
        $item = $adjustment->adjustable;

        if ($item) {
            // "increment" handles negative numbers correctly too!
            // If quantity is -5, incrementing by -5 effectively decrements.
            $item->increment('current_stock', $adjustment->quantity);

            Notification::make()
                ->title('Stock Updated')
                ->body("{$item->name} stock adjusted by {$adjustment->quantity}.")
                ->success()
                ->send();
        }
    }
}