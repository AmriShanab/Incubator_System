<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Forms\Get;
use App\Models\Incubator;
use App\Models\Accessory;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class PosTerminal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'POS Terminal';
    protected static ?string $navigationGroup = 'Sales';
    
    // Puts it at the very top of the Sales menu!
    protected static ?int $navigationSort = 0; 
    
    protected static string $view = 'filament.pages.pos-terminal';

    public ?array $data = [];
    public $grandTotal = 0;

    public function mount(): void
    {
        // 1. Auto-create or fetch a default "Walk-in" customer
        $walkIn = Customer::firstOrCreate(
            ['phone' => '0000000000'],
            ['name' => 'Walk-in Customer', 'address' => 'Store Front']
        );

        // 2. Pre-fill the cart so it's ready immediately
        $this->form->fill([
            'customer_id' => $walkIn->id,
            'items' => [['quantity' => 1]], 
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Quick Cart')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->schema([
                                // THE MAGIC UNIFIED SEARCH BAR
                                Select::make('item_key')
                                    ->label('Search Product or Supply')
                                    ->options([
                                        'Manufactured Products' => Incubator::pluck('name', 'id')->mapWithKeys(fn($name, $id) => [Incubator::class . '-' . $id => $name])->toArray(),
                                        'Accessories & Supplies' => Accessory::pluck('name', 'id')->mapWithKeys(fn($name, $id) => [Accessory::class . '-' . $id => $name])->toArray(),
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->autofocus() // Automatically puts the cursor here so you can scan a barcode or type instantly
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(3)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            // Split the string back into Model and ID
                                            [$type, $id] = explode('-', $state);
                                            $record = $type::find($id);
                                            
                                            $price = $record->selling_price ?? $record->price ?? 0;
                                            $set('unit_price', $price);
                                            $set('row_total', $price * (int)$get('quantity'));
                                            $this->updateTotal();
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('row_total', (int)$state * (float)$get('unit_price'));
                                        $this->updateTotal();
                                    }),

                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                TextInput::make('row_total')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->addActionLabel('Add Another Item')
                            ->afterStateUpdated(fn() => $this->updateTotal()),

                        // Only show customer if they explicitly want to log a specific person
                        Select::make('customer_id')
                            ->label('Customer (Defaults to Walk-in)')
                            ->options(Customer::pluck('name', 'id'))
                            ->searchable()
                            ->columnSpanFull(),
                    ])
            ])
            ->statePath('data');
    }

    public function updateTotal()
    {
        $items = $this->form->getRawState()['items'] ?? [];
        $this->grandTotal = collect($items)->sum(fn($item) => (float)($item['row_total'] ?? 0));
    }

    public function processSale()
    {
        $data = $this->form->getState();

        if (empty($data['items']) || $this->grandTotal <= 0) {
            Notification::make()->title('Cart is empty')->danger()->send();
            return;
        }

        $cashAccount = Account::where('name', 'Cash')->first();

        DB::transaction(function () use ($data, $cashAccount) {
            
            // 1. Create Invoice silently in the background
            $invoice = Invoice::create([
                'customer_id' => $data['customer_id'],
                'invoice_date' => now(),
                'status' => 'draft', // Start as draft
                'payment_method' => 'cash',
                'account_id' => $cashAccount->id ?? 1,
                'total_amount' => $this->grandTotal,
            ]);

            // 2. Attach Items and Deduct Stock
            foreach ($data['items'] as $item) {
                if (empty($item['item_key'])) continue;
                
                [$type, $id] = explode('-', $item['item_key']);
                
                $invoice->items()->create([
                    'sellable_type' => $type,
                    'sellable_id' => $id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'row_total' => $item['row_total'],
                ]);

                // Auto-deduct physical stock
                $product = $type::find($id);
                if ($product) {
                    $product->decrement('current_stock', $item['quantity']);
                }
            }

            // 3. Trigger the Financial Engine
            // By shifting to delivered, your Invoice Model's booted() method takes over 
            // and automatically adds the money to the Cash Account ledger!
            $invoice->update(['status' => 'delivered']);

            Notification::make()->title('Sale Completed Successfully!')->success()->send();
            
            // Reset the POS screen for the next customer instantly
            $this->mount();
            $this->grandTotal = 0;
        });
    }
}