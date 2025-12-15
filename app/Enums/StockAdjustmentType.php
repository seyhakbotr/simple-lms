<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StockAdjustmentType: string implements HasColor, HasIcon, HasLabel
{
    case Purchase = 'purchase';
    case Damage = 'damage';
    case Lost = 'lost';
    case Donation = 'donation';
    case Correction = 'correction';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Damage => 'Damage',
            self::Lost => 'Lost',
            self::Donation => 'Donation',
            self::Correction => 'Stock Correction',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Purchase => 'success',
            self::Damage => 'warning',
            self::Lost => 'danger',
            self::Donation => 'info',
            self::Correction => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Purchase => 'heroicon-s-arrow-up-circle',
            self::Damage => 'heroicon-s-exclamation-triangle',
            self::Lost => 'heroicon-s-x-circle',
            self::Donation => 'heroicon-s-gift',
            self::Correction => 'heroicon-s-pencil-square',
        };
    }

    public function isPositive(): bool
    {
        return match ($this) {
            self::Purchase, self::Donation, self::Correction => true,
            self::Damage, self::Lost => false,
        };
    }
}
