<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Services\BusyBeeSmsGateway;
use App\Services\PayMongoWebhookService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGateway::class, function (): SmsGateway {
            return new BusyBeeSmsGateway(
                apiKey: (string) config('services.busybee.api_key', ''),
                sender: (string) config('services.busybee.sender', 'STOCKLANE'),
            );
        });

        $this->app->singleton(PayMongoWebhookService::class, function ($app): PayMongoWebhookService {
            return new PayMongoWebhookService(
                inventory: $app->make(\App\Services\InventoryService::class),
                webhookSecret: (string) config('services.paymongo.webhook_secret', ''),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
