<?php

namespace Tests\Feature;

use App\Jobs\SendLowStockSmsJob;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_decrements_stock_and_writes_movement(): void
    {
        Queue::fake();

        $product = Product::query()->create([
            'sku' => 'SME-BOLT-M6',
            'name' => 'Hex Bolt M6',
            'quantity' => 20,
            'reorder_at' => 5,
            'unit_cost_cents' => 150,
        ]);

        /** @var InventoryService $inventory */
        $inventory = $this->app->make(InventoryService::class);
        $movement = $inventory->recordSale($product, 3, 'POS-1001');

        $product->refresh();

        $this->assertSame(17, $product->quantity);
        $this->assertSame(-3, $movement->delta);
        $this->assertSame(17, $movement->quantity_after);
        $this->assertSame('sale', $movement->reason);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference' => 'POS-1001',
        ]);

        Queue::assertNotPushed(SendLowStockSmsJob::class);
    }

    public function test_sale_into_reorder_window_dispatches_low_stock_sms_job(): void
    {
        Queue::fake();

        $product = Product::query()->create([
            'sku' => 'SME-NUT-M6',
            'name' => 'Hex Nut M6',
            'quantity' => 6,
            'reorder_at' => 5,
            'unit_cost_cents' => 80,
        ]);

        /** @var InventoryService $inventory */
        $inventory = $this->app->make(InventoryService::class);
        $inventory->recordSale($product, 2, 'POS-1002');

        $product->refresh();
        $this->assertSame(4, $product->quantity);
        $this->assertTrue($product->isLowStock());

        Queue::assertPushed(SendLowStockSmsJob::class, function (SendLowStockSmsJob $job) use ($product): bool {
            return $job->productId === $product->id;
        });
    }

    public function test_sale_rejects_insufficient_stock(): void
    {
        $product = Product::query()->create([
            'sku' => 'SME-WASHER',
            'name' => 'Washer',
            'quantity' => 2,
            'reorder_at' => 1,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        /** @var InventoryService $inventory */
        $inventory = $this->app->make(InventoryService::class);
        $inventory->recordSale($product, 5);
    }
}
