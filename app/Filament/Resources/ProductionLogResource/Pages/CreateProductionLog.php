<?php

namespace App\Filament\Resources\ProductionLogResource\Pages;

use App\Filament\Resources\ProductionLogResource;
use App\Models\Incubator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProductionLog extends CreateRecord
{
    protected static string $resource = ProductionLogResource::class;

    // FIXED: Spelled "beforeCreate" correctly so Filament actually runs it
    protected function beforeCreate(): void
    {
        $incubatorId = $this->data['incubator_id'];
        $quantityProduced = $this->data['quantity_produced'];

        $incubator = Incubator::with('materials')->find($incubatorId);

        if(!$incubator || $incubator->materials->isEmpty()){
            Notification::make()
            ->danger()
            ->title('Missing Bill Of Materials')
            ->body("This product does not have any materials assigned to it. Please add supplies first.")
            ->send();

            $this->halt();
        }

        foreach ($incubator->materials as $key => $material) {
            // FIXED: Using 'quantity_required' to match your afterCreate logic
            $needed = $material->pivot->quantity_required * $quantityProduced;

            // FIXED: Using 'current_stock' to match your Material database column
            if ($material->current_stock < $needed) {
                Notification::make()
                    ->danger()
                    ->title('Stock Shortage: ' . $material->name)
                    ->body("You need {$needed} but only have {$material->current_stock} in stock. Production blocked.")
                    ->persistent() 
                    ->send();
                
                $this->halt(); // STOPS THE CREATION
            }
        }
    }

    protected function afterCreate(): void
    {
        $log = $this->record;
        $incubator = $log->incubator; 
        
        $incubator->load('materials');

        $qtyBuilt = $log->quantity_produced;
        $deductedMaterials = [];

        DB::transaction(function () use ($incubator, $qtyBuilt, &$deductedMaterials) {
            foreach ($incubator->materials as $material) {
                $requiredPerUnit = $material->pivot->quantity_required;
                $totalToDeduct = $requiredPerUnit * $qtyBuilt;
                
                // Perform the deduction
                $material->decrement('current_stock', $totalToDeduct);
                
                $deductedMaterials[] = "{$material->name}: -{$totalToDeduct} {$material->unit}";
            }
            // Assuming your Incubator model also uses 'current_stock'
            $incubator->increment('current_stock', $qtyBuilt);
        });

        Notification::make()
            ->title('Stock Deducted Successfully')
            ->body(implode("\n", $deductedMaterials))
            ->success()
            ->send();
    }
}