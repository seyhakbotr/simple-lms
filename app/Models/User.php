<?php

namespace App\Models;

use App\Observers\UserObserver;
use Filament\AvatarProviders\UiAvatarsProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements
    FilamentUser,
    HasAvatar,
    MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "name",
        "email",
        "password",
        "role_id",
        "membership_type_id",
        "membership_started_at",
        "membership_expires_at",
        "status",
        "address",
        "phone",
        "avatar_url",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
        "status" => "boolean",
        "membership_started_at" => "date",
        "membership_expires_at" => "date",
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function membershipInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->whereNotNull('membership_type_id');
    }

    /**
     * Check if membership is active
     */
    public function hasActiveMembership(): bool
    {
        return $this->membership_expires_at &&
            $this->membership_expires_at->isFuture();
    }

    /**
     * Check if membership is expired
     */
    public function membershipExpired(): bool
    {
        return $this->membership_expires_at &&
            $this->membership_expires_at->isPast();
    }

    /**
     * Get current active transactions (borrowed books)
     */
    public function activeTransactions()
    {
        return $this->transactions()
            ->where("status", "borrowed")
            ->with("items.book");
    }

    /**
     * Get count of currently borrowed books
     */
    public function getCurrentBorrowedBooksCount(): int
    {
        return $this->activeTransactions()
            ->get()
            ->sum(fn($transaction) => $transaction->items->count());
    }

    /**
     * Check if user can borrow more books
     */
    public function canBorrowMoreBooks(): bool
    {
        if (!$this->membershipType) {
            return false;
        }

        $currentCount = $this->getCurrentBorrowedBooksCount();
        return $currentCount < $this->membershipType->max_books_allowed;
    }

    /**
     * Renew membership
     */
    public function renewMembership(): void
    {
        if (!$this->membershipType) {
            return;
        }

        $startDate = $this->membershipExpired()
            ? now()
            : $this->membership_expires_at;

        $this->update([
            "membership_started_at" => $startDate,
            "membership_expires_at" => $startDate
                ->copy()
                ->addMonths($this->membershipType->membership_duration_months),
        ]);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $role = $this->role?->name;

        $canAccessPanel = match ($panel->getId()) {
            'admin' => $role === 'admin',
            'staff' => in_array($role, ['admin', 'staff'], true),
            default => false,
        };

        return $canAccessPanel && $this->hasVerifiedEmail();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $uiAvatarsProvider = new UiAvatarsProvider();

        if ($this->avatar_url) {
            return Storage::url($this->avatar_url);
        }

        // If avatar_url is not available, use the UiAvatarsProvider directly
        return $uiAvatarsProvider->get($this);
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
            $borrowerKey =
                "BorrowerCount_" . class_basename($model) . $model->getTable();
            if (Cache::has($borrowerKey)) {
                Cache::forget($borrowerKey);
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
            $borrowerKey =
                "BorrowerCount_" . class_basename($model) . $model->getTable();
            if (Cache::has($borrowerKey)) {
                Cache::forget($borrowerKey);
            }
        });
    }
}
