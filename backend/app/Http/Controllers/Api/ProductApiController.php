<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {
    }

    public function index(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('sku')
            ->get(['id', 'sku', 'name', 'quantity', 'reorder_at', 'unit_cost_cents']);

        return response()->json([
            'data' => $products->map(fn (Product $p): array => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'quantity' => $p->quantity,
                'reorder_at' => $p->reorder_at,
                'is_low_stock' => $p->isLowStock(),
                'unit_cost_cents' => $p->unit_cost_cents,
            ]),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'quantity' => $product->quantity,
                'reorder_at' => $product->reorder_at,
                'is_low_stock' => $product->isLowStock(),
                'unit_cost_cents' => $product->unit_cost_cents,
                'recent_movements' => $product->movements()
                    ->latest()
                    ->limit(10)
                    ->get(['id', 'delta', 'quantity_after', 'reason', 'reference', 'created_at']),
            ],
        ]);
    }

    public function recordSale(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'units' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:191'],
        ]);

        $movement = $this->inventory->recordSale(
            product: $product,
            units: (int) $data['units'],
            reference: $data['reference'] ?? null,
        );

        $product->refresh();

        return response()->json([
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'quantity' => $product->quantity,
                    'is_low_stock' => $product->isLowStock(),
                ],
                'movement' => [
                    'id' => $movement->id,
                    'delta' => $movement->delta,
                    'quantity_after' => $movement->quantity_after,
                    'reason' => $movement->reason,
                ],
            ],
        ], 201);
    }
}
