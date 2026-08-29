<?php

namespace Tests\Feature;

use App\Models\PaymentEvent;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_event_restocks_product_from_metadata(): void
    {
        $product = Product::query()->create([
            'sku' => 'SME-FILTER-01',
            'name' => 'Oil Filter',
            'quantity' => 3,
            'reorder_at' => 5,
            'unit_cost_cents' => 45000,
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_test_paid_001',
                'attributes' => [
                    'type' => 'payment.paid',
                    'amount' => 450000,
                    'metadata' => [
                        'sku' => 'SME-FILTER-01',
                        'units' => 12,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/paymongo', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'processed');

        $product->refresh();
        $this->assertSame(15, $product->quantity);

        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'evt_test_paid_001',
            'status' => PaymentEvent::STATUS_PROCESSED,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reason' => 'paymongo_restock',
            'delta' => 12,
            'reference' => 'evt_test_paid_001',
        ]);
    }

    public function test_duplicate_event_id_is_idempotent(): void
    {
        $product = Product::query()->create([
            'sku' => 'SME-FILTER-02',
            'name' => 'Air Filter',
            'quantity' => 1,
            'reorder_at' => 2,
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_test_paid_dup',
                'attributes' => [
                    'type' => 'payment.paid',
                    'amount' => 10000,
                    'metadata' => [
                        'sku' => 'SME-FILTER-02',
                        'units' => 5,
                    ],
                ],
            ],
        ];

        $this->postJson('/webhooks/paymongo', $payload)->assertOk();
        $second = $this->postJson('/webhooks/paymongo', $payload);

        $second->assertOk()->assertJsonPath('status', 'duplicate');

        $product->refresh();
        $this->assertSame(6, $product->quantity);
        $this->assertSame(1, PaymentEvent::query()->where('event_id', 'evt_test_paid_dup')->count());
    }

    public function test_non_paid_event_is_ignored(): void
    {
        $payload = [
            'data' => [
                'id' => 'evt_test_failed_001',
                'attributes' => [
                    'type' => 'payment.failed',
                    'metadata' => [
                        'sku' => 'SME-ANY',
                        'units' => 1,
                    ],
                ],
            ],
        ];

        $this->postJson('/webhooks/paymongo', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'ignored');

        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'evt_test_failed_001',
            'status' => PaymentEvent::STATUS_IGNORED,
        ]);
    }
}
