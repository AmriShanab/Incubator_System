<?php

namespace App\Filament\Pages;

use App\Models\Incubator;
use App\Models\Material;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;

class ProductionPlanner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Production Planner';
    protected static ?string $navigationGroup = 'Production';

    protected static string $view = 'filament.pages.production-planner';

    // Form Data
    public ?array $data = [];

    // Results Data
    public ?array $analysisResults = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Production Goal')->schema([
                    Select::make('incubator_id')
                        ->label('What do you want to build?')
                        ->options(Incubator::all()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    TextInput::make('quantity')
                        ->label('How many?')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required(),
                ])->columns(2)
            ])
            ->statePath('data');
    }


    
    public function calculate()
    {
        $data = $this->form->getState();
        $incubator = Incubator::find($data['incubator_id']);
        $qty = $data['quantity'];

        $results = [];
        $isFeasible = true;

        // FIXED: Use 'materials' instead of 'bomItems'
        foreach ($incubator->materials as $material) {

            // 1. Get quantity from the Pivot table (incubator_material)
            $requiredPerUnit = $material->pivot->quantity_required;

            $totalRequired = $requiredPerUnit * $qty;
            $currentStock = $material->current_stock;

            $missing = $currentStock - $totalRequired;

            $status = $missing >= 0 ? 'Enough Stock' : 'MISSING ' . abs($missing);
            $color = $missing >= 0 ? 'success' : 'danger';

            if ($missing < 0) $isFeasible = false;

            $results[] = [
                'material_name' => $material->name,
                'stock' => $currentStock,
                'required' => $totalRequired,
                'status' => $status,
                'color' => $color,
            ];
        }

        $this->analysisResults = [
            'product' => $incubator->name,
            'quantity' => $qty,
            'feasible' => $isFeasible,
            'materials' => $results,
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('calculate')
                ->label('Check Availability')
                ->submit('calculate'),
        ];
    }
}
