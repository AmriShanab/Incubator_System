<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Incubator;
use App\Models\Accessory;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class PosTerminal extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'POS Terminal';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.pos-terminal';

    // POS State
    public $search = '';
    public $activeCategory = 'all';
    public $cart = [];
    public $grandTotal = 0;
    public $customerId;
    public $customers = [];

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        $walkIn = Customer::firstOrCreate(
            ['phone' => '0000000000'],
            ['name' => 'Walk-in Customer', 'address' => 'Store Front']
        );

        $this->customers = Customer::pluck('name', 'id')->toArray();
        
        // Ensure customerId is set, defaulting to walk-in if currently empty
        if (!$this->customerId) {
            $this->customerId = $walkIn->id;
        }
    }

    // Action to create a new customer via a modal
    public function createCustomerAction(): Action
    {
        return Action::make('createCustomer')
            ->label('New')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->form([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Textarea::make('address')
                    ->maxLength(65535),
            ])
            ->action(function (array $data) {
                $customer = Customer::create($data);
                
                // Reload the dropdown list and select the newly created customer
                $this->loadCustomers();
                $this->customerId = $customer->id;

                Notification::make()
                    ->title('Customer created successfully')
                    ->success()
                    ->send();
            });
    }

    public function getCategoriesProperty()
    {
        $categories = [
            ['id' => 'all', 'name' => 'All Items', 'icon' => 'heroicon-o-squares-2x2', 'color' => 'gray'],
            ['id' => 'incubators', 'name' => 'Products', 'icon' => 'heroicon-o-cube', 'color' => 'primary'],
        ];

        $accessoryCategories = Accessory::select('category')->distinct()->pluck('category');

        foreach ($accessoryCategories as $cat) {
            $categories[] = [
                'id' => $cat,
                'name' => ucwords(str_replace('_', ' ', $cat)),
                'icon' => 'heroicon-o-tag',
                'color' => 'success',
            ];
        }

        return $categories;
    }

    public function getProductsProperty()
    {
        $querySearch = '%' . $this->search . '%';
        $results = collect();

        if (in_array($this->activeCategory, ['all', 'incubators'])) {
            $incubators = Incubator::where('name', 'like', $querySearch)
                ->get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => Incubator::class,
                        'name' => $item->name,
                        'price' => $item->price,
                        'stock' => $item->current_stock,
                        'color' => 'primary',
                        'icon' => 'heroicon-o-cube'
                    ];
                });
            $results = $results->concat($incubators);
        }

        if ($this->activeCategory !== 'incubators') {
            $accessoryQuery = Accessory::where('name', 'like', $querySearch);

            if ($this->activeCategory !== 'all') {
                $accessoryQuery->where('category', $this->activeCategory);
            }

            $accessories = $accessoryQuery->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => Accessory::class,
                    'name' => $item->name,
                    'price' => $item->selling_price,
                    'stock' => $item->current_stock,
                    'color' => 'success',
                    'icon' => 'heroicon-o-tag'
                ];
            });
            $results = $results->concat($accessories);
        }

        return $results;
    }

    public function setCategory($category)
    {
        $this->activeCategory = $category;
    }

    public function addToCart($type, $id)
    {
        $record = $type::find($id);
        if (!$record || $record->current_stock <= 0) {
            Notification::make()->title('Out of stock!')->danger()->send();
            return;
        }

        $cartKey = $type . '-' . $id;

        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['quantity'] < $record->current_stock) {
                $this->cart[$cartKey]['quantity']++;
                $this->cart[$cartKey]['row_total'] = $this->cart[$cartKey]['quantity'] * $this->cart[$cartKey]['unit_price'];
            } else {
                Notification::make()->title('Not enough stock!')->warning()->send();
            }
        } else {
            $cost = 0;
            if (class_basename($type) === 'Accessory') {
                $cost = $record->cost_price ?? 0;
            } else {
                $cost = $record->cost ?? DB::table('incubator_material')
                    ->join('materials', 'incubator_material.material_id', '=', 'materials.id')
                    ->where('incubator_id', $record->id)
                    ->selectRaw('SUM(incubator_material.quantity_required * materials.cost_per_unit) as calculated_cost')
                    ->value('calculated_cost') ?? 0;
            }

            $price = class_basename($type) === 'Accessory' ? $record->selling_price : $record->price;

            $this->cart[$cartKey] = [
                'type' => $type,
                'id' => $id,
                'name' => $record->name,
                'unit_price' => $price,
                'unit_cost' => $cost,
                'quantity' => 1,
                'row_total' => $price,
                'stock' => $record->current_stock
            ];
        }

        $this->calculateTotal();
    }

    public function increaseQuantity($key)
    {
        if (!isset($this->cart[$key])) {
            return;
        }

        if ($this->cart[$key]['quantity'] < $this->cart[$key]['stock']) {
            $this->cart[$key]['quantity']++;
            $this->cart[$key]['row_total'] = $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
            $this->calculateTotal();
        }
    }

    public function decreaseQuantity($key)
    {
        if (!isset($this->cart[$key])) {
            return;
        }

        if ($this->cart[$key]['quantity'] > 1) {
            $this->cart[$key]['quantity']--;
            $this->cart[$key]['row_total'] = $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
        } else {
            unset($this->cart[$key]);
        }

        $this->calculateTotal();
    }

    public function removeFromCart($key)
    {
        if (!isset($this->cart[$key])) {
            return;
        }

        unset($this->cart[$key]);
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->grandTotal = collect($this->cart)->sum('row_total');
    }

    public function processSale()
    {
        if (empty($this->cart) || $this->grandTotal <= 0) {
            Notification::make()->title('Cart is empty')->danger()->send();
            return;
        }

        $cashAccount = Account::where('name', 'Cash')->first();

        $invoice = DB::transaction(function () use ($cashAccount) {

            $createdInvoice = Invoice::create([
                'customer_id' => $this->customerId,
                'invoice_date' => now(),
                'status' => 'draft',
                'payment_method' => 'cash',
                'account_id' => $cashAccount->id ?? 1,
                'total_amount' => $this->grandTotal,
            ]);

            foreach ($this->cart as $item) {
                $createdInvoice->items()->create([
                    'sellable_type' => $item['type'],
                    'sellable_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'row_total' => $item['row_total'],
                ]);

                $product = $item['type']::find($item['id']);
                if ($product) {
                    $product->decrement('current_stock', $item['quantity']);
                }
            }

            $createdInvoice->update(['status' => 'delivered']);

            return $createdInvoice;
        });

        Notification::make()->title('Sale Completed!')->success()->send();

        $this->dispatch('print-receipt', ['invoiceId' => $invoice->id]);

        $this->cart = [];
        $this->grandTotal = 0;
        $this->search = '';
        $this->activeCategory = 'all';
    }
}