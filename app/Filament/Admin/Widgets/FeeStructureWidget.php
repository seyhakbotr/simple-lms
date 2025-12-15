<?php

namespace App\Filament\Admin\Widgets;

use App\Settings\FeeSettings;
use Filament\Widgets\Widget;

class FeeStructureWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.fee-structure-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function getFeeSettings(): FeeSettings
    {
        return app(FeeSettings::class);
    }

    public function getFeeData(): array
    {
        $settings = $this->getFeeSettings();

        return [
            'overdue_enabled' => $settings->overdue_fee_enabled,
            'overdue_fee' => $settings->currency_symbol . number_format($settings->overdue_fee_per_day, 2),
            'grace_period' => $settings->grace_period_days,
            'max_days' => $settings->overdue_fee_max_days,
            'max_amount' => $settings->overdue_fee_max_amount
                ? $settings->currency_symbol . number_format($settings->overdue_fee_max_amount, 2)
                : null,
            'lost_book_type' => $settings->lost_book_fine_type,
            'lost_book_rate' => $settings->lost_book_fine_type === 'percentage'
                ? number_format($settings->lost_book_fine_rate, 0) . '%'
                : $settings->currency_symbol . number_format($settings->lost_book_fine_rate, 2),
            'lost_book_min' => $settings->lost_book_minimum_fine
                ? $settings->currency_symbol . number_format($settings->lost_book_minimum_fine, 2)
                : null,
            'lost_book_max' => $settings->lost_book_maximum_fine
                ? $settings->currency_symbol . number_format($settings->lost_book_maximum_fine, 2)
                : null,
            'partial_payments' => $settings->allow_partial_payment,
            'auto_waive' => $settings->waive_small_amounts,
            'waive_threshold' => $settings->currency_symbol . number_format($settings->small_amount_threshold, 2),
        ];
    }
}
