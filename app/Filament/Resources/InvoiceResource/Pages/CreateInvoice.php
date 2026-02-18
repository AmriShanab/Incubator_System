<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function afterCreate(): void
    {
        $invoice = $this->record;

        foreach($invoice->items as $item){
            $product = $item->sellable;

            if($product){
                if($product->current_stock < $item->quantity){
                    \Filament\Notifications\Notification::make()
                    ->title("Warning: Negative Stock for {$product->name}")
                    ->warning()
                    ->send();
                }

                $product->decrement('current_stock', $item->quantity);
            }
        }
    }
}
