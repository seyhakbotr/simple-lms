<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add("general.site_address", "123 Library Street");
        $this->migrator->add("general.site_city", "City");
        $this->migrator->add("general.site_state", "State");
        $this->migrator->add("general.site_zip", "12345");
        $this->migrator->add("general.site_phone", "(123) 456-7890");
        $this->migrator->add("general.site_email", "library@example.com");
    }

    public function down(): void
    {
        $this->migrator->delete("general.site_address");
        $this->migrator->delete("general.site_city");
        $this->migrator->delete("general.site_state");
        $this->migrator->delete("general.site_zip");
        $this->migrator->delete("general.site_phone");
        $this->migrator->delete("general.site_email");
    }
};
