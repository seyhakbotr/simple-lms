<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Overdue Fee Settings
        $this->migrator->add("fees.overdue_fee_per_day", 10.0);
        $this->migrator->add("fees.overdue_fee_enabled", true);
        $this->migrator->add("fees.overdue_fee_max_days", null);
        $this->migrator->add("fees.overdue_fee_max_amount", null);

        // Lost Book Fine Settings
        $this->migrator->add("fees.lost_book_fine_rate", 100.0);
        $this->migrator->add("fees.lost_book_fine_type", "percentage");
        $this->migrator->add("fees.lost_book_minimum_fine", null);
        $this->migrator->add("fees.lost_book_maximum_fine", null);

        // Late Return Grace Period
        $this->migrator->add("fees.grace_period_days", 0);

        // Payment Settings
        $this->migrator->add("fees.allow_partial_payment", true);
        $this->migrator->add("fees.waive_small_amounts", false);
        $this->migrator->add("fees.small_amount_threshold", 1.0);

        // Notification Settings
        $this->migrator->add("fees.send_overdue_notifications", true);
        $this->migrator->add("fees.overdue_notification_days", 3);

        // Currency Settings
        $this->migrator->add("fees.currency_symbol", '$');
        $this->migrator->add("fees.currency_code", "USD");
    }
};
