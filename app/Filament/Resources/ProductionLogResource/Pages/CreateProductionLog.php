<?php

namespace App\Filament\Resources\ProductionLogResource\Pages;

use App\Filament\Resources\ProductionLogResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProductionLog extends CreateRecord
{
    protected static string $resource = ProductionLogResource::class;

    protected function afterCreate(): void
    {
        // 1. Get the Log we just created
        $log = $this->record;
        
        // 2. Get the Incubator and its Recipe (Materials)
        $incubator = $log->incubator; 
        
        // We need to load the materials relationship if it's not already loaded
        $incubator->load('materials');

        $qtyBuilt = $log->quantity_produced;
        $deductedMaterials = [];

        // 3. Start a Database Transaction (Safety first!)
        DB::transaction(function () use ($incubator, $qtyBuilt, &$deductedMaterials) {
            foreach ($incubator->materials as $material) {
                // How much do we need per unit? (from the pivot table)
                $requiredPerUnit = $material->pivot->quantity_required;
                
                // Total to deduct
                $totalToDeduct = $requiredPerUnit * $qtyBuilt;
                
                // Perform the deduction
                $material->decrement('current_stock', $totalToDeduct);
                
                $deductedMaterials[] = "{$material->name}: -{$totalToDeduct} {$material->unit}";
            }
            $incubator->increment('current_stock', $qtyBuilt);
        });

        Notification::make()
            ->title('Stock Deducted Successfully')
            ->body(implode("\n", $deductedMaterials))
            ->success()
            ->send();
    }
}
