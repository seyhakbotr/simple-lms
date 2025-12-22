<?php

namespace App\Providers\Filament;

use App\Filament\Staff\Pages\Auth\EditProfile;
use App\Filament\Staff\Pages\Auth\Login;
use App\Filament\Staff\Pages\Auth\Register;
use App\Http\Middleware\RedirectAdminFromStaffPanel;
use App\Settings\GeneralSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Staff\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('staff')
            ->path('staff')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->profile(EditProfile::class)
            ->emailVerification()
            ->favicon(fn (GeneralSettings $settings) => Storage::disk('public')
                ->url($settings->site_favicon))
            ->brandName(fn (GeneralSettings $settings) => $settings->site_name)
            ->brandLogo(fn (GeneralSettings $settings) => Storage::disk('public')
                ->url($settings->site_logo))
            ->darkModeBrandLogo(function (GeneralSettings $settings) {
                $darkBrandLogo = $settings->site_logo_dark
                ? $settings->site_logo_dark
                : $settings->site_logo;

                return Storage::disk('public')->url($darkBrandLogo);
            })
            ->brandLogoHeight(fn (GeneralSettings $settings) => $settings->site_logoHeight)
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->globalSearchKeyBindings(['ctrl+k, command+k'])
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => __('messages.english'))
                    ->postAction(fn () => route('locale.switch.post', 'en')),
                MenuItem::make()
                    ->label(fn () => __('messages.khmer'))
                    ->postAction(fn () => route('locale.switch.post', 'km')),
            ])
            ->discoverResources(in: app_path('Filament/Staff/Resources'), for: 'App\\Filament\\Staff\\Resources')
            ->discoverPages(in: app_path('Filament/Staff/Pages'), for: 'App\\Filament\\Staff\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Staff/Widgets'), for: 'App\\Filament\\Staff\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectAdminFromStaffPanel::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->spa();
    }
}
