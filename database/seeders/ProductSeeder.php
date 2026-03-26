<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accessory;
use App\Models\Incubator;
use App\Models\Material;
use Illuminate\Support\Facades\DB; // <-- ADDED: For linking the pivot table

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 1. SEED INCUBATORS (Saved to variables)
        // ==========================================
        
        $inc56 = Incubator::updateOrCreate(
            ['sku' => 'INC-56A'],
            [
                'name' => '56 Egg Fully Automatic Incubator',
                'price' => 18500.00,
                'current_stock' => 12,
                'low_stock_cycles' => 3,
            ]
        );

        $inc112 = Incubator::updateOrCreate(
            ['sku' => 'INC-112D'],
            [
                'name' => '112 Egg Dual Power Incubator',
                'price' => 26000.00,
                'current_stock' => 5,
                'low_stock_cycles' => 2,
            ]
        );

        $inc24 = Incubator::updateOrCreate(
            ['sku' => 'INC-24M'],
            [
                'name' => '24 Egg Mini Manual Incubator',
                'price' => 8500.00,
                'current_stock' => 0, 
                'low_stock_cycles' => 5,
            ]
        );

        // ==========================================
        // 2. SEED ACCESSORIES & SUPPLIES
        // ==========================================

        Accessory::updateOrCreate(
            ['name' => 'Vitamin C Booster Drops'],
            [
                'category' => 'medicine',
                'cost_price' => 450.00,
                'selling_price' => 800.00,
                'current_stock' => 45,
                'min_stock_alert' => 10,
            ]
        );

        Accessory::updateOrCreate(
            ['name' => 'Chick Starter Crumble (1kg)'],
            [
                'category' => 'food',
                'cost_price' => 200.00,
                'selling_price' => 350.00,
                'current_stock' => 100,
                'min_stock_alert' => 20,
            ]
        );

        Accessory::updateOrCreate(
            ['name' => 'Automatic Water Drinker 1L'],
            [
                'category' => 'feeder',
                'cost_price' => 150.00,
                'selling_price' => 300.00,
                'current_stock' => 30,
                'min_stock_alert' => 5,
            ]
        );

        Accessory::updateOrCreate(
            ['name' => 'Incubator Disinfectant Spray'],
            [
                'category' => 'cleaning',
                'cost_price' => 600.00,
                'selling_price' => 1100.00,
                'current_stock' => 15,
                'min_stock_alert' => 5,
            ]
        );

        Accessory::updateOrCreate(
            ['name' => 'Digital Hygrometer & Thermometer'],
            [
                'category' => 'other',
                'cost_price' => 800.00,
                'selling_price' => 1500.00,
                'current_stock' => 3, 
                'min_stock_alert' => 5,
            ]
        );

        // ==========================================
        // 3. SEED RAW MATERIALS (Saved to variables)
        // ==========================================

        $thermostat = Material::updateOrCreate(
            ['name' => 'W1209 Digital Thermostat Controller'],
            ['unit' => 'piece', 'cost_per_unit' => 350.00, 'current_stock' => 50]
        );

        $heater = Material::updateOrCreate(
            ['name' => '12V DC Heating Element'],
            ['unit' => 'piece', 'cost_per_unit' => 250.00, 'current_stock' => 60]
        );

        $pvc = Material::updateOrCreate(
            ['name' => 'PVC Foam Board (18mm)'],
            ['unit' => 'sq_ft', 'cost_per_unit' => 120.00, 'current_stock' => 200]
        );

        $fan = Material::updateOrCreate(
            ['name' => '12V Cooling Fan (Brushless)'],
            ['unit' => 'piece', 'cost_per_unit' => 180.00, 'current_stock' => 80]
        );

        $motor = Material::updateOrCreate(
            ['name' => 'Egg Turner Motor (2.5 RPM)'],
            ['unit' => 'piece', 'cost_per_unit' => 750.00, 'current_stock' => 20]
        );

        // ==========================================
        // 4. LINK MATERIALS TO INCUBATORS (BILL OF MATERIALS)
        // ==========================================
        // We use updateOrInsert so you can run the seeder multiple times without errors!

        // --- Recipe for 56 Egg Incubator ---
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc56->id, 'material_id' => $thermostat->id], ['quantity_required' => 1]
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc56->id, 'material_id' => $heater->id], ['quantity_required' => 1]
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc56->id, 'material_id' => $pvc->id], ['quantity_required' => 12] // 12 sq ft of PVC
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc56->id, 'material_id' => $fan->id], ['quantity_required' => 1]
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc56->id, 'material_id' => $motor->id], ['quantity_required' => 1]
        );

        // --- Recipe for 112 Egg Incubator (Needs more PVC, Heat, and Fans) ---
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc112->id, 'material_id' => $thermostat->id], ['quantity_required' => 1]
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc112->id, 'material_id' => $heater->id], ['quantity_required' => 2] // 2 heaters
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc112->id, 'material_id' => $pvc->id], ['quantity_required' => 20] // 20 sq ft of PVC
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc112->id, 'material_id' => $fan->id], ['quantity_required' => 2] // 2 fans
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc112->id, 'material_id' => $motor->id], ['quantity_required' => 1]
        );

        // --- Recipe for 24 Egg Manual Incubator (No motor needed) ---
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc24->id, 'material_id' => $thermostat->id], ['quantity_required' => 1]
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc24->id, 'material_id' => $heater->id], ['quantity_required' => 1]
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc24->id, 'material_id' => $pvc->id], ['quantity_required' => 6] // 6 sq ft of PVC
        );
        DB::table('incubator_material')->updateOrInsert(
            ['incubator_id' => $inc24->id, 'material_id' => $fan->id], ['quantity_required' => 1]
        );
    }
}