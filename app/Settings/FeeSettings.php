<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FeeSettings extends Settings
{
    // Overdue Fee Settings
    public float $overdue_fee_per_day;

    public bool $overdue_fee_enabled;

    public ?int $overdue_fee_max_days; // Maximum days to charge (null = unlimited)

    public ?float $overdue_fee_max_amount; // Maximum overdue fee cap (null = unlimited)

    // Lost Book Fine Settings
    public float $lost_book_fine_rate;

    public string $lost_book_fine_type; // 'percentage' or 'fixed'

    public ?float $lost_book_minimum_fine; // Minimum fine for lost books

    public ?float $lost_book_maximum_fine; // Maximum fine for lost books

    // Late Return Grace Period
    public int $grace_period_days;

    // Payment Settings
    public bool $allow_partial_payment;

    public bool $waive_small_amounts;

    public float $small_amount_threshold;

    // Notification Settings
    public bool $send_overdue_notifications;

    public int $overdue_notification_days; // Days before sending overdue notice

    // Currency Settings
    public string $currency_symbol;

    public string $currency_code;

    public static function group(): string
    {
        return 'fees';
    }
}
