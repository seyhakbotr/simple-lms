<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_books_allowed',
        'max_borrow_days',
        'renewal_limit',
        'fine_rate',
        'membership_duration_months',
        'membership_fee',
        'is_active',
    ];

    protected $casts = [
        'max_books_allowed' => 'integer',
        'max_borrow_days' => 'integer',
        'renewal_limit' => 'integer',
        'fine_rate' => 'decimal:2',
        'membership_duration_months' => 'integer',
        'membership_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get only active membership types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if membership type allows more books
     */
    public function allowsMoreBooks(int $currentCount): bool
    {
        return $currentCount < $this->max_books_allowed;
    }

    /**
     * Get the default borrow duration for this membership type
     */
    public function getDefaultBorrowDays(): int
    {
        return $this->max_borrow_days;
    }

    /**
     * Calculate fine for this membership type
     */
    public function calculateFine(int $daysLate): float
    {
        return $daysLate * $this->fine_rate;
    }
}
