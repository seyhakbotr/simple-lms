<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Book extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        "author_id",
        "publisher_id",
        "genre_id",
        "title",
        "cover_image",
        "isbn",
        "price",
        "description",
        "stock",
        "available",
        "published",
    ];

    protected $casts = [
        "available" => "boolean",
        "published" => "date",
        "price" => MoneyCast::class, // Stores as cents in DB, works as dollars in code
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function stockTransactionItems(): HasMany
    {
        return $this->hasMany(StockTransactionItem::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
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
