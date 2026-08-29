<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    public const REASON_SALE = 'sale';
    public const REASON_RESTOCK = 'restock';
    public const REASON_ADJUSTMENT = 'adjustment';
    public const REASON_PAYMONGO_RESTOCK = 'paymongo_restock';

    protected $fillable = [
        'product_id',
        'delta',
        'quantity_after',
        'reason',
        'reference',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'quantity_after' => 'integer',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
