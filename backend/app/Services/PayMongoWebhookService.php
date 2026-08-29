<?php

namespace App\Services;

use App\Models\PaymentEvent;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayMongoWebhookService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly string $webhookSecret,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, payment_event_id: int|null, message: string}
     */
    public function handle(array $payload, ?string $signatureHeader): array
    {
        $this->assertSignature($payload, $signatureHeader);

        $eventId = (string) data_get($payload, 'data.id', data_get($payload, 'id', ''));
        $eventType = (string) data_get($payload, 'data.attributes.type', data_get($payload, 'type', 'unknown'));

        if ($eventId === '') {
            throw new RuntimeException('PayMongo payload missing event id.');
        }

        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($payload, $eventId, $eventType, $hash): array {
            $existing = PaymentEvent::query()->where('event_id', $eventId)->first();
            if ($existing !== null) {
                return [
                    'status' => 'duplicate',
                    'payment_event_id' => $existing->id,
                    'message' => 'Event already processed.',
                ];
            }

            $sku = (string) data_get($payload, 'data.attributes.metadata.sku', data_get($payload, 'metadata.sku', ''));
            $units = (int) data_get($payload, 'data.attributes.metadata.units', data_get($payload, 'metadata.units', 0));
            $amountCents = (int) data_get($payload, 'data.attributes.amount', data_get($payload, 'amount', 0));

            $product = $sku !== ''
                ? Product::query()->where('sku', $sku)->first()
                : null;

            $event = PaymentEvent::query()->create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => PaymentEvent::STATUS_RECEIVED,
                'product_id' => $product?->id,
                'amount_cents' => $amountCents > 0 ? $amountCents : null,
                'payload_hash' => $hash,
                'payload' => $payload,
            ]);

            if (! $this->isPaidEvent($eventType)) {
                $event->update([
                    'status' => PaymentEvent::STATUS_IGNORED,
                    'processed_at' => now(),
                ]);

                return [
                    'status' => 'ignored',
                    'payment_event_id' => $event->id,
                    'message' => "Ignored event type {$eventType}.",
                ];
            }

            if ($product === null || $units < 1) {
                $event->update([
                    'status' => PaymentEvent::STATUS_FAILED,
                    'processed_at' => now(),
                ]);

                Log::warning('stocklane.paymongo.missing_restock_target', [
                    'event_id' => $eventId,
                    'sku' => $sku,
                    'units' => $units,
                ]);

                return [
                    'status' => 'failed',
                    'payment_event_id' => $event->id,
                    'message' => 'Paid event missing sku/units metadata.',
                ];
            }

            $this->inventory->restockFromPayment(
                product: $product,
                units: $units,
                paymentEventId: $eventId,
                meta: [
                    'amount_cents' => $amountCents,
                    'event_type' => $eventType,
                ],
            );

            $event->update([
                'status' => PaymentEvent::STATUS_PROCESSED,
                'product_id' => $product->id,
                'processed_at' => now(),
            ]);

            return [
                'status' => 'processed',
                'payment_event_id' => $event->id,
                'message' => "Restocked {$units} of {$product->sku}.",
            ];
        });
    }

    private function isPaidEvent(string $eventType): bool
    {
        return in_array($eventType, [
            'payment.paid',
            'checkout_session.payment.paid',
        ], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSignature(array $payload, ?string $signatureHeader): void
    {
        if ($this->webhookSecret === '' || str_starts_with($this->webhookSecret, 'whsec_test_')) {
            // Portfolio mode: accept missing/test signatures; log loudly.
            Log::debug('stocklane.paymongo.signature_skipped_portfolio_mode');

            return;
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            throw new RuntimeException('Missing PayMongo signature header.');
        }

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $expected = hash_hmac('sha256', $raw, $this->webhookSecret);

        // Accept either bare hex or t=...,te=... style headers used in demos.
        $candidates = preg_split('/[,\\s]+/', $signatureHeader) ?: [];
        $ok = false;
        foreach ($candidates as $candidate) {
            $value = str_contains($candidate, '=')
                ? substr($candidate, strpos($candidate, '=') + 1)
                : $candidate;
            if (hash_equals($expected, $value)) {
                $ok = true;
                break;
            }
        }

        if (! $ok) {
            throw new RuntimeException('Invalid PayMongo signature.');
        }
    }
}
