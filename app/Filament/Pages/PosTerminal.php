<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Incubator;
use App\Models\Accessory;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Invoice;
use App\Services\CheckoutService;
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

// 1. ADD THIS IMPORT FOR SESSION RECOVERY
use Livewire\Attributes\Session;

class PosTerminal extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon  = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'POS Terminal';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int    $navigationSort  = 0;

    protected static string $view = 'filament.pages.pos-terminal';

    // ── Product browser state ──────────────────────────────────
    public string $search        = '';
    public string $activeCategory = 'all';

    // ── Cart (CACHED) ─────────────────────────────────────────
    #[Session]
    public array $cart = [];

    // ── Financials ────────────────────────────────────────────
    public float  $subTotal       = 0;
    
    #[Session]
    public string $discountType   = 'amount'; // 'amount' | 'percentage'
    
    #[Session]
    public mixed  $discountValue  = 0;
    
    public float  $discountAmount = 0;
    public float  $grandTotal     = 0;

    // ── Checkout (CACHED) ─────────────────────────────────────
    #[Session]
    public mixed  $customerId     = null;
    
    public array  $customers      = [];
    
    #[Session]
    public mixed  $accountId      = null;
    
    public array  $paymentMethods = [];
    public mixed  $amountPaid     = 0;

    // ──────────────────────────────────────────────────────────
    #[Session]
    public bool $isCreditSale = false;


    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->loadCustomers();

        $this->paymentMethods = Account::where('type', '!=', 'credit_receivable')
            ->orWhereNull('type')
            ->pluck('name', 'id')
            ->toArray();

        // FIX: Only set a default account if the Session didn't just load one!
        if (! $this->accountId) {
            $this->accountId = Account::where('type', 'cash')->first()?->id
                ?? array_key_first($this->paymentMethods);
        }

        // FIX: If the page was refreshed and the cart was recovered, recalculate the totals immediately.
        $this->calculateTotal();
    }

    // ── Customers ─────────────────────────────────────────────

    public function loadCustomers(): void
    {
        $walkIn = Customer::firstOrCreate(
            ['phone' => '0000000000'],
            ['name' => 'Walk-in Customer', 'address' => 'Store Front']
        );

        $this->customers = Customer::pluck('name', 'id')->toArray();

        // Only set default if the session didn't recover a customer
        if (! $this->customerId) {
            $this->customerId = $walkIn->id;
        }
    }

    public function createCustomerAction(): Action
    {
        return Action::make('createCustomer')
            ->label('New Customer')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->form([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('phone')->tel()->required()->maxLength(255),
                Textarea::make('address')->maxLength(65535)->rows(2),
            ])
            ->action(function (array $data) {
                $customer = Customer::create($data);
                $this->loadCustomers();
                $this->customerId = $customer->id;
                Notification::make()->title('Customer created')->success()->send();
            });
    }


    public function getCategoriesProperty(): array
    {
        $categories = [
            ['id' => 'all',        'name' => 'All Items'],
            ['id' => 'incubators', 'name' => 'Products'],
        ];

        Accessory::select('category')
            ->distinct()
            ->pluck('category')
            ->each(function ($cat) use (&$categories) {
                $categories[] = [
                    'id'   => $cat,
                    'name' => ucwords(str_replace('_', ' ', $cat)),
                ];
            });

        return $categories;
    }

    public function getProductsProperty()
    {
        $like    = '%' . $this->search . '%';
        $results = collect();

        // Incubators
        if (in_array($this->activeCategory, ['all', 'incubators'])) {
            $results = $results->concat(
                Incubator::where('name', 'like', $like)
                    ->get()
                    ->map(fn($item) => [
                        'id'    => $item->id,
                        'type'  => Incubator::class,
                        'name'  => $item->name,
                        'price' => $item->price,
                        'stock' => $item->current_stock,
                        'uom'   => $item->uom,
                    ])
            );
        }

        if ($this->activeCategory !== 'incubators') {
            $query = Accessory::where('name', 'like', $like);

            if ($this->activeCategory !== 'all') {
                $query->where('category', $this->activeCategory);
            }

            $results = $results->concat(
                $query->get()->map(fn($item) => [
                    'id'    => $item->id,
                    'type'  => Accessory::class,
                    'name'  => $item->name,
                    'price' => $item->selling_price,
                    'stock' => $item->current_stock,
                    'uom'   => $item->uom ?? 'pcs',
                ])
            );
        }

        return $results;
    }

    // ── Category selection ────────────────────────────────────

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }

    // ── Cart operations ───────────────────────────────────────

    public function addToCart(string $type, int $id): void
    {
        $record = $type::find($id);

        if (! $record || $record->current_stock <= 0) {
            Notification::make()->title('Out of stock!')->danger()->send();
            return;
        }

        $cartKey = $type . '-' . $id;

        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['quantity'] < $record->current_stock) {
                $this->cart[$cartKey]['quantity']++;
                $this->cart[$cartKey]['row_total'] =
                    $this->cart[$cartKey]['quantity'] * $this->cart[$cartKey]['unit_price'];
            } else {
                Notification::make()->title('Max stock reached!')->warning()->send();
            }
        } else {
            $cost = $this->resolveCost($type, $record);

            $price = class_basename($type) === 'Accessory'
                ? $record->selling_price
                : $record->price;

            $this->cart[$cartKey] = [
                'type'       => $type,
                'id'         => $id,
                'name'       => $record->name,
                'unit_price' => $price,
                'unit_cost'  => $cost,
                'quantity'   => 1,
                'row_total'  => $price,
                'stock'      => $record->current_stock,
                'uom'        => $record->uom ?? 'pcs',
            ];
        }

        $this->calculateTotal();
    }

    /**
     * Resolve COGS for an item (Accessory = cost_price, Incubator = cost or BOM sum).
     */
    private function resolveCost(string $type, $record): float
    {
        if (class_basename($type) === 'Accessory') {
            return (float) ($record->cost_price ?? 0);
        }

        if ($record->cost) {
            return (float) $record->cost;
        }

        // BOM-based cost
        return (float) (DB::table('incubator_material')
            ->join('materials', 'incubator_material.material_id', '=', 'materials.id')
            ->where('incubator_id', $record->id)
            ->selectRaw('SUM(incubator_material.quantity_required * materials.cost_per_unit) as total')
            ->value('total') ?? 0);
    }

    public function increaseQuantity(string $key): void
    {
        if (isset($this->cart[$key]) && $this->cart[$key]['quantity'] < $this->cart[$key]['stock']) {
            $this->cart[$key]['quantity']++;
            $this->cart[$key]['row_total'] =
                $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
            $this->calculateTotal();
        } else {
            Notification::make()->title('Max stock reached!')->warning()->send();
        }
    }

    public function decreaseQuantity(string $key): void
    {
        if (! isset($this->cart[$key])) return;

        if ($this->cart[$key]['quantity'] > 1) {
            $this->cart[$key]['quantity']--;
            $this->cart[$key]['row_total'] =
                $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
        } else {
            unset($this->cart[$key]);
        }

        $this->calculateTotal();
    }

    public function updateItemPrice(string $key, mixed $newPrice): void
    {
        if (! isset($this->cart[$key])) return;

        $price = max(0, (float) $newPrice);
        $this->cart[$key]['unit_price'] = $price;
        $this->cart[$key]['row_total']  = $this->cart[$key]['quantity'] * $price;
        $this->calculateTotal();
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
        $this->calculateTotal();
    }

    /**
     * Remove the last item added to the cart (called by Esc key via Alpine).
     */
    public function removeLastCartItem(): void
    {
        if (empty($this->cart)) return;

        $lastKey = array_key_last($this->cart);
        unset($this->cart[$lastKey]);
        $this->calculateTotal();
    }

    // ── Totals ────────────────────────────────────────────────

    public function updatedDiscountValue(): void
    {
        $this->calculateTotal();
    }
    public function updatedDiscountType(): void
    {
        $this->calculateTotal();
    }

    public function calculateTotal(): void
    {
        $this->subTotal = (float) collect($this->cart)->sum('row_total');

        $val = (float) $this->discountValue;
        $this->discountAmount = $this->discountType === 'percentage'
            ? $this->subTotal * ($val / 100)
            : $val;

        if ($this->discountAmount > $this->subTotal) {
            $this->discountAmount = $this->subTotal;
        }

        $this->grandTotal = max(0, $this->subTotal - $this->discountAmount);

        // FIX: Only auto-fill if it's NOT a credit sale
        if ($this->isCreditSale) {
            $this->amountPaid = 0;
        } else {
            $this->amountPaid = $this->grandTotal;
        }
    }

    public function toggleCreditSale(): void
    {
        $this->isCreditSale = !$this->isCreditSale;
        $this->calculateTotal();
    }

    // ── Process Sale ──────────────────────────────────────────
    public function processSale(CheckoutService $service): void
    {
        if (empty($this->cart) || $this->grandTotal <= 0) {
            Notification::make()->title('Empty Cart')->danger()->send();
            return;
        }

        if (!$this->accountId) {
            Notification::make()
                ->title('Payment Method Required')
                ->body('Please select an account to receive the payment.')
                ->danger()
                ->send();
            return;
        }

        try {
            $invoice = $service->processSale(
                $this->cart,
                (float) $this->amountPaid,
                (int) $this->customerId,
                (int) $this->accountId,
                (float) $this->discountAmount
            );

            Notification::make()->title('Sale Completed!')->success()->send();
            $this->dispatch('print-receipt', ['invoiceId' => $invoice->id]);
            $this->resetAfterSale();
        } catch (\Exception $e) {
            Notification::make()->title('Sale Failed')->body($e->getMessage())->danger()->send();
        }
    }

    private function resetAfterSale(): void
    {
        $this->cart           = [];
        $this->subTotal       = 0;
        $this->grandTotal     = 0;
        $this->discountValue  = 0;
        $this->discountAmount = 0;
        $this->amountPaid     = 0;
        $this->search         = '';
        $this->activeCategory = 'all';
        $this->isCreditSale   = false; 
        
        // Ensure defaults are reset properly for the next sale
        $this->loadCustomers();
        $this->accountId = Account::where('type', 'cash')->first()?->id ?? array_key_first($this->paymentMethods);
    }

    public function updateQuantity(string $key, mixed $newQty): void
    {
        if (!isset($this->cart[$key])) return;

        $qty = max(0.01, (float) $newQty);

        if ($qty <= $this->cart[$key]['stock']) {
            $this->cart[$key]['quantity'] = $qty;
            $this->cart[$key]['row_total'] = $qty * $this->cart[$key]['unit_price'];
        } else {
            // Revert to max available if they type too much
            $this->cart[$key]['quantity'] = $this->cart[$key]['stock'];
            $this->cart[$key]['row_total'] = $this->cart[$key]['stock'] * $this->cart[$key]['unit_price'];
            Notification::make()->title('Exceeds available stock!')->warning()->send();
        }

        $this->calculateTotal();
    }
}