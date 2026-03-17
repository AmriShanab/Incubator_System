<?php

namespace App\Filament\Resources\FundTransferResource\Pages;

use App\Filament\Resources\FundTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFundTransfers extends ManageRecords
{
    protected static string $resource = FundTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
