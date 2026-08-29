<?php

namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLowStockSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $productId,
    ) {
    }

    public function handle(SmsGateway $sms): void
    {
        $product = Product::query()->find($this->productId);
        if ($product === null) {
            Log::warning('stocklane.sms.product_missing', ['product_id' => $this->productId]);

            return;
        }

        if (! $product->isLowStock()) {
            Log::info('stocklane.sms.skipped_recovered', ['sku' => $product->sku]);

            return;
        }

        $to = (string) config('services.busybee.to', env('LOW_STOCK_SMS_TO', ''));
        if ($to === '') {
            Log::warning('stocklane.sms.missing_recipient', ['sku' => $product->sku]);

            return;
        }

        $message = sprintf(
            'StockLane low stock: %s (%s) qty=%d reorder_at=%d',
            $product->name,
            $product->sku,
            $product->quantity,
            $product->reorder_at,
        );

        $sms->send($to, $message);
    }
}
