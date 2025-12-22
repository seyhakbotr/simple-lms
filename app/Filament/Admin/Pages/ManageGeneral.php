<?php

namespace App\Filament\Admin\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\Storage;

class ManageGeneral extends SettingsPage
{
    protected static ?string $navigationIcon = "heroicon-o-cog-6-tooth";

    protected static string $settings = GeneralSettings::class;

    protected static ?string $navigationGroup = "Settings";

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = "General Settings";

    public function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                // Left Column (Span 2): General Info & Contact Details
                Grid::make(1)
                    ->schema([
                        Section::make("General Settings")
                            ->description(
                                "Configure your site's identity and status.",
                            )
                            ->schema([
                                TextInput::make("site_name")
                                    ->label("Site Name")
                                    ->required(),

                                Toggle::make("site_active")
                                    ->label("Site Status (Active)")
                                    ->helperText(
                                        "Toggle whether the site is visible to the public.",
                                    )
                                    ->default(true),

                                Grid::make(2)->schema([
                                    TextInput::make("site_logoWidth")
                                        ->label("Logo Width (px)")
                                        ->numeric(),
                                    TextInput::make("site_logoHeight")
                                        ->label("Logo Height (px)")
                                        ->numeric(),
                                ]),
                            ]),

                        Section::make("Contact Information")
                            ->description(
                                "These details are used for footer info and automated emails.",
                            )
                            ->columns(2)
                            ->schema([
                                TextInput::make("site_email")
                                    ->email()
                                    ->columnSpanFull(),
                                TextInput::make("site_phone")->tel(),
                                TextInput::make(
                                    "site_address",
                                )->columnSpanFull(),
                                TextInput::make("site_city"),
                                TextInput::make("site_state"),
                                TextInput::make("site_zip")->label(
                                    "Zip/Postal Code",
                                ),
                            ]),
                    ])
                    ->columnSpan(["lg" => 2]),

                // Right Column (Span 1): Images & Assets
                Grid::make(1)
                    ->schema([
                        Section::make("Favicon & Logo")->schema([
                            FileUpload::make("site_favicon")
                                ->label("Favicon")
                                ->image()
                                ->directory("sites")
                                ->acceptedFileTypes([
                                    "image/x-icon",
                                    "image/vnd.microsoft.icon",
                                    "image/png",
                                ])
                                ->imageEditor()
                                ->deleteUploadedFileUsing(
                                    fn($file) => Storage::disk(
                                        "public",
                                    )->delete($file),
                                ),

                            FileUpload::make("site_logo")
                                ->label("Site Logo (General)")
                                ->image()
                                ->directory("sites")
                                ->imageEditor()
                                ->deleteUploadedFileUsing(
                                    fn($file) => Storage::disk(
                                        "public",
                                    )->delete($file),
                                ),
                        ]),

                        Section::make("Dark Mode Logo")
                            ->description(
                                "Optional logo for dark mode support.",
                            )
                            ->collapsed()
                            ->schema([
                                FileUpload::make("site_logo_dark")
                                    ->label("Site Logo (Dark Mode)")
                                    ->image()
                                    ->directory("sites")
                                    ->imageEditor()
                                    ->deleteUploadedFileUsing(
                                        fn($file) => Storage::disk(
                                            "public",
                                        )->delete($file),
                                    ),
                            ]),
                    ])
                    ->columnSpan(["lg" => 1]),
            ]),
        ]);
    }
}
