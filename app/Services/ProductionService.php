<?php

namespace App\Services;

use App\Models\Incubator;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function logProduction(int $incubatorId, float $quantity, string $date): ProductionLog
    {
        return DB::transaction(function () use ($incubatorId, $quantity, $date) {
            $product = Incubator::with('materials')->findOrFail($incubatorId);

            foreach ($product->materials as $material) {
                $material->lockForUpdate();

                $totalNeeded = (float) $material->pivot->quantity_required * $quantity;

                if ($material->current_stock < $totalNeeded) {
                    throw new \Exception(
                        "Production Failed: Insufficient {$material->name}. " .
                            "Required: {$totalNeeded}, Available: {$material->current_stock}."
                    );
                }

                $material->decrement('current_stock', $totalNeeded);
            }

            $product->increment('current_stock', $quantity);

            return ProductionLog::create([
                'incubator_id' => $incubatorId,
                'quantity_produced' => $quantity,
                'production_date' => $date,
            ]);
        });
    }
}
