<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BorrowedStatus: string implements HasColor, HasIcon, HasLabel
{
    case Borrowed = "borrowed";
    case Returned = "returned";
    case Delayed = "delayed";
    case Lost = "lost";
    case Damaged = "damaged";

    public function getLabel(): string
    {
        return __($this->name);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Borrowed => "info",
            self::Returned => "success",
            self::Delayed => "warning",
            self::Lost => "danger",
            self::Damaged => "danger",
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Borrowed => "heroicon-s-arrow-path",
            self::Returned => "heroicon-s-check-badge",
            self::Delayed => "heroicon-s-clock",
            self::Lost => "heroicon-s-exclamation-triangle",
            self::Damaged => "heroicon-s-exclamation-circle",
        };
    }

    public static function randomValue()
    {
        $cases = self::cases();
        $key = array_rand($cases);

        return $cases[$key]->value;
    }
}
