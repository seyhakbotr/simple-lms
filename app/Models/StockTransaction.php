<?php

namespace App\Models;

use App\Enums\StockAdjustmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class StockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "type",
        "notes",
        "reference_number",
        "donator_name",
    ];

    protected $casts = [
        "type" => StockAdjustmentType::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->reference_number) {
                $model->reference_number =
                    "ST-" .
                    date("Ymd") .
                    "-" .
                    str_pad(
                        static::whereDate("created_at", today())->count() + 1,
                        4,
                        "0",
                        STR_PAD_LEFT,
                    );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransactionItem::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->items()->sum("quantity");
    }

    public function getTotalBooksAttribute(): int
    {
        return $this->items()->count();
    }

    public static function booted(): void
    {
        parent::boot();

        static::created(function ($model) {
            $cacheKey =
                "NavigationCount_" .
                class_basename($model) .
                $model->getTable();
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
        });

        static::deleted(function ($model) {
            $cacheKey =
                "NavigationCount_" .
                class_basename($model) .
                $model->getTable();
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
        });
    }
}
