<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public ?string $site_logo;
    public ?string $site_logo_dark;
    public string $site_logoHeight;
    public string $site_logoWidth;
    public ?string $site_favicon;
    public bool $site_active;

    // Add new fields for invoice
    public ?string $site_address;
    public ?string $site_city;
    public ?string $site_state;
    public ?string $site_zip;
    public ?string $site_phone;
    public ?string $site_email;

    public static function group(): string
    {
        return "general";
    }
}
