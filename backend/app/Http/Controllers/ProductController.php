<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {
    }

    public function index(): Response
    {
        $products = Product::query()
            ->orderBy('sku')
            ->get()
            ->map(fn (Product $p): array => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'quantity' => $p->quantity,
                'reorder_at' => $p->reorder_at,
                'is_low_stock' => $p->isLowStock(),
                'unit_cost_cents' => $p->unit_cost_cents,
            ]);

        $lowStockCount = $products->where('is_low_stock', true)->count();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'summary' => [
                'sku_count' => $products->count(),
                'low_stock_count' => $lowStockCount,
                'units_on_hand' => $products->sum('quantity'),
            ],
        ]);
    }

    public function recordSale(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'units' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:191'],
        ]);

        $this->inventory->recordSale(
            product: $product,
            units: (int) $data['units'],
            reference: $data['reference'] ?? null,
        );

        return redirect()->route('products.index');
    }

    public function restock(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'units' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:191'],
        ]);

        $this->inventory->restock(
            product: $product,
            units: (int) $data['units'],
            reference: $data['reference'] ?? 'manual',
        );

        return redirect()->route('products.index');
    }
}
