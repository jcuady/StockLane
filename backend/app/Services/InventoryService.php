<?php

namespace App\Services;

use App\Jobs\SendLowStockSmsJob;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function recordSale(Product $product, int $units, ?string $reference = null): StockMovement
    {
        if ($units < 1) {
            throw new InvalidArgumentException('Sale units must be at least 1.');
        }

        return $this->applyDelta(
            product: $product,
            delta: -$units,
            reason: StockMovement::REASON_SALE,
            reference: $reference,
            meta: ['units' => $units],
            dispatchLowStock: true,
        );
    }

    public function restock(Product $product, int $units, string $reason = StockMovement::REASON_RESTOCK, ?string $reference = null, array $meta = []): StockMovement
    {
        if ($units < 1) {
            throw new InvalidArgumentException('Restock units must be at least 1.');
        }

        return $this->applyDelta(
            product: $product,
            delta: $units,
            reason: $reason,
            reference: $reference,
            meta: array_merge(['units' => $units], $meta),
            dispatchLowStock: false,
        );
    }

    public function restockFromPayment(Product $product, int $units, string $paymentEventId, array $meta = []): StockMovement
    {
        return $this->restock(
            product: $product,
            units: $units,
            reason: StockMovement::REASON_PAYMONGO_RESTOCK,
            reference: $paymentEventId,
            meta: $meta,
        );
    }

    private function applyDelta(
        Product $product,
        int $delta,
        string $reason,
        ?string $reference,
        array $meta,
        bool $dispatchLowStock,
    ): StockMovement {
        return DB::transaction(function () use ($product, $delta, $reason, $reference, $meta, $dispatchLowStock): StockMovement {
            /** @var Product $locked */
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $next = $locked->quantity + $delta;
            if ($next < 0) {
                throw new InvalidArgumentException("Insufficient stock for {$locked->sku}.");
            }

            $locked->quantity = $next;
            $locked->save();

            $movement = $locked->movements()->create([
                'delta' => $delta,
                'quantity_after' => $next,
                'reason' => $reason,
                'reference' => $reference,
                'meta' => $meta,
            ]);

            if ($dispatchLowStock && $locked->isLowStock()) {
                SendLowStockSmsJob::dispatch($locked->id);
            }

            return $movement;
        });
    }
}
