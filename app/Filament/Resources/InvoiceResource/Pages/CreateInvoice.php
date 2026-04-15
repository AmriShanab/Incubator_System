<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\CheckoutService;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * Hijack Filament's default save behavior and route it through our Service.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $cart = [];
        foreach ($data['items'] ?? [] as $item) {
            $cart[] = [
                'type'       => $item['sellable_type'], 
                'id'         => $item['sellable_id'],
                'quantity'   => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'unit_cost'  => (float) ($item['unit_cost'] ?? 0),
                'row_total'  => (float) $item['quantity'] * (float) $item['unit_price'],
            ];
        }

        $service = app(CheckoutService::class);

        return $service->processSale(
            cart: $cart,
            amountPaid: (float) ($data['total_amount'] ?? 0), 
            customerId: (int) $data['customer_id'],
            accountId: (int) $data['account_id'],
            discountAmount: (float) ($data['discount_amount'] ?? 0)
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}