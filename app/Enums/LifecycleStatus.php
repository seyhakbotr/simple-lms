<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LifecycleStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = "active";
    case Completed = "completed";
    case Cancelled = "cancelled";
    case Archived = "archived";

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => "Active",
            self::Completed => "Completed",
            self::Cancelled => "Cancelled",
            self::Archived => "Archived",
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => "info",
            self::Completed => "success",
            self::Cancelled => "gray",
            self::Archived => "secondary",
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active => "heroicon-s-arrow-path",
            self::Completed => "heroicon-s-check-circle",
            self::Cancelled => "heroicon-s-x-circle",
            self::Archived => "heroicon-s-archive-box",
        };
    }

    /**
     * Check if this lifecycle allows renewal
     */
    public function canRenew(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if this lifecycle allows return processing
     */
    public function canReturn(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if transaction is in a completed state
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed || $this === self::Archived;
    }

    /**
     * Check if transaction is still in progress
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if transaction can be edited
     */
    public function canEdit(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if transaction can be deleted
     */
    public function canDelete(): bool
    {
        // Only active transactions can be deleted (cancellation)
        return $this === self::Active;
    }

    /**
     * Get description for this status
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Active => "Books are currently checked out",
            self::Completed => "Books have been returned, transaction closed",
            self::Cancelled => "Transaction was cancelled before completion",
            self::Archived => "Historical record, archived transaction",
        };
    }
}
