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

class PosTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'POS Terminal';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.pos-terminal';

    // POS State
    public $search = '';
    public $cart = [];
    public $grandTotal = 0;
    public $customerId;
    public $customers = [];

    public function mount(): void
    {
        $walkIn = Customer::firstOrCreate(
            ['phone' => '0000000000'],
            ['name' => 'Walk-in Customer', 'address' => 'Store Front']
        );

        $this->customerId = $walkIn->id;
        $this->customers = Customer::pluck('name', 'id')->toArray();
    }

    // Unifies Incubators and Accessories into one searchable grid
    public function getProductsProperty()
    {
        $querySearch = '%' . $this->search . '%';

        $incubators = Incubator::where('name', 'like', $querySearch)
            ->get()->map(function($item) {
                return [
                    'id' => $item->id,
                    'type' => Incubator::class,
                    'name' => $item->name,
                    'price' => $item->price,
                    'stock' => $item->current_stock,
                    'color' => 'primary', // Blue theme
                    'icon' => 'heroicon-o-cube'
                ];
            });

        $accessories = Accessory::where('name', 'like', $querySearch)
            ->get()->map(function($item) {
                return [
                    'id' => $item->id,
                    'type' => Accessory::class,
                    'name' => $item->name,
                    'price' => $item->selling_price,
                    'stock' => $item->current_stock,
                    'color' => 'success', // Green theme
                    'icon' => 'heroicon-o-tag'
                ];
            });

        return $incubators->concat($accessories);
    }

    public function addToCart($type, $id)
    {
        $record = $type::find($id);
        if (!$record || $record->current_stock <= 0) {
            Notification::make()->title('Out of stock!')->danger()->send();
            return;
        }

        $cartKey = $type . '-' . $id;

        // If already in cart, just increase quantity
        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['quantity'] < $record->current_stock) {
                $this->cart[$cartKey]['quantity']++;
                $this->cart[$cartKey]['row_total'] = $this->cart[$cartKey]['quantity'] * $this->cart[$cartKey]['unit_price'];
            } else {
                Notification::make()->title('Not enough stock!')->warning()->send();
            }
        } else {
            // Smart Cost Fetcher
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
        if ($this->cart[$key]['quantity'] < $this->cart[$key]['stock']) {
            $this->cart[$key]['quantity']++;
            $this->cart[$key]['row_total'] = $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
            $this->calculateTotal();
        }
    }

    public function decreaseQuantity($key)
    {
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

        DB::transaction(function () use ($cashAccount) {
            $invoice = Invoice::create([
                'customer_id' => $this->customerId,
                'invoice_date' => now(),
                'status' => 'draft',
                'payment_method' => 'cash',
                'account_id' => $cashAccount->id ?? 1,
                'total_amount' => $this->grandTotal,
            ]);

            foreach ($this->cart as $item) {
                $invoice->items()->create([
                    'sellable_type' => $item['type'],
                    'sellable_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'row_total' => $item['row_total'],
                ]);

                // Auto-deduct physical stock
                $product = $item['type']::find($item['id']);
                if ($product) {
                    $product->decrement('current_stock', $item['quantity']);
                }
            }

            // Trigger Financial Engine
            $invoice->update(['status' => 'delivered']);
        });

        Notification::make()->title('Sale Completed!')->success()->send();

        // Clear Cart
        $this->cart = [];
        $this->grandTotal = 0;
        $this->search = '';
    }
}