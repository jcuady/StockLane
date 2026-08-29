<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PayMongoWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class PayMongoController extends Controller
{
    public function __construct(
        private readonly PayMongoWebhookService $webhooks,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('Paymongo-Signature')
                ?? $request->header('X-PayMongo-Signature');

            $result = $this->webhooks->handle($payload, $signature);

            return response()->json($result, 200);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'rejected',
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed.',
            ], 500);
        }
    }
}
