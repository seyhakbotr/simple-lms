<?php

namespace App\Filament\Admin\Pages;

use App\Settings\FeeSettings;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageFees extends SettingsPage
{
    protected static ?string $navigationIcon = "heroicon-o-banknotes";

    protected static string $settings = FeeSettings::class;

    protected static ?string $navigationGroup = "Settings";

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = "Fee Management";

    protected static ?string $title = "Fee Management";

    public function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                // Left Column - Overdue Fees & Lost Book Fines
                Group::make()
                    ->schema([
                        Section::make("Overdue Fee Settings")
                            ->description("Configure fees for overdue books")
                            ->icon("heroicon-o-clock")
                            ->schema([
                                Toggle::make("overdue_fee_enabled")
                                    ->label("Enable Overdue Fees")
                                    ->default(true)
                                    ->live()
                                    ->helperText(
                                        "Turn on/off overdue fee calculation",
                                    ),

                                TextInput::make("overdue_fee_per_day")
                                    ->label("Fee Per Day")
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(10.0)
                                    ->prefix('$')
                                    ->suffix("per day")
                                    ->helperText(
                                        "Amount charged per day for overdue books",
                                    )
                                    ->disabled(
                                        fn($get) => !$get(
                                            "overdue_fee_enabled",
                                        ),
                                    ),

                                Grid::make(2)->schema([
                                    TextInput::make("overdue_fee_max_days")
                                        ->label("Maximum Days to Charge")
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix("days")
                                        ->placeholder("Unlimited")
                                        ->helperText(
                                            "Leave empty for unlimited days",
                                        )
                                        ->disabled(
                                            fn($get) => !$get(
                                                "overdue_fee_enabled",
                                            ),
                                        ),

                                    TextInput::make("overdue_fee_max_amount")
                                        ->label("Maximum Fee Cap")
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$')
                                        ->placeholder("Unlimited")
                                        ->helperText("Leave empty for no cap")
                                        ->disabled(
                                            fn($get) => !$get(
                                                "overdue_fee_enabled",
                                            ),
                                        ),
                                ]),

                                TextInput::make("grace_period_days")
                                    ->label("Grace Period")
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix("days")
                                    ->helperText(
                                        "Days after due date before fees start",
                                    )
                                    ->disabled(
                                        fn($get) => !$get(
                                            "overdue_fee_enabled",
                                        ),
                                    ),
                            ])
                            ->collapsible(),

                        Section::make("Lost Book Fine Settings")
                            ->description(
                                "Configure fines for lost or damaged books",
                            )
                            ->icon("heroicon-o-exclamation-triangle")
                            ->schema([
                                Select::make("lost_book_fine_type")
                                    ->label("Fine Calculation Type")
                                    ->required()
                                    ->options([
                                        "fixed" => "Fixed Amount",
                                        "percentage" =>
                                            "Percentage of Book Price",
                                    ])
                                    ->default("percentage")
                                    ->native(false)
                                    ->live()
                                    ->helperText(
                                        "How to calculate the fine for lost books",
                                    ),

                                TextInput::make("lost_book_fine_rate")
                                    ->label(
                                        fn($get) => $get(
                                            "lost_book_fine_type",
                                        ) === "percentage"
                                            ? "Fine Percentage"
                                            : "Fixed Fine Amount",
                                    )
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(100.0)
                                    ->prefix(
                                        fn($get) => $get(
                                            "lost_book_fine_type",
                                        ) === "percentage"
                                            ? ""
                                            : '$',
                                    )
                                    ->suffix(
                                        fn($get) => $get(
                                            "lost_book_fine_type",
                                        ) === "percentage"
                                            ? "%"
                                            : "",
                                    )
                                    ->helperText(
                                        fn($get) => $get(
                                            "lost_book_fine_type",
                                        ) === "percentage"
                                            ? "Percentage of book price to charge (e.g., 100% = full price)"
                                            : "Fixed amount to charge for any lost book",
                                    ),

                                Grid::make(2)->schema([
                                    TextInput::make("lost_book_minimum_fine")
                                        ->label("Minimum Fine")
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$')
                                        ->placeholder("No minimum")
                                        ->helperText("Minimum fine amount")
                                        ->visible(
                                            fn($get) => $get(
                                                "lost_book_fine_type",
                                            ) === "percentage",
                                        ),

                                    TextInput::make("lost_book_maximum_fine")
                                        ->label("Maximum Fine")
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$')
                                        ->placeholder("No maximum")
                                        ->helperText("Maximum fine amount")
                                        ->visible(
                                            fn($get) => $get(
                                                "lost_book_fine_type",
                                            ) === "percentage",
                                        ),
                                ]),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(1),

                // Right Column - Payment & Notification Settings
                Group::make()
                    ->schema([
                        Section::make("Payment Settings")
                            ->description("Configure payment options")
                            ->icon("heroicon-o-credit-card")
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make("currency_symbol")
                                        ->label("Currency Symbol")
                                        ->required()
                                        ->default('$')
                                        ->maxLength(5)
                                        ->helperText(
                                            'Symbol to display (e.g., $, €, £)',
                                        ),

                                    TextInput::make("currency_code")
                                        ->label("Currency Code")
                                        ->required()
                                        ->default("USD")
                                        ->maxLength(3)
                                        ->formatStateUsing(
                                            fn($state) => strtoupper($state),
                                        )
                                        ->dehydrateStateUsing(
                                            fn($state) => strtoupper($state),
                                        )
                                        ->helperText(
                                            "ISO currency code (e.g., USD, EUR)",
                                        ),
                                ]),

                                Toggle::make("allow_partial_payment")
                                    ->label("Allow Partial Payments")
                                    ->default(true)
                                    ->helperText(
                                        "Allow members to pay fines in installments",
                                    ),

                                Toggle::make("waive_small_amounts")
                                    ->label("Auto-waive Small Amounts")
                                    ->default(false)
                                    ->live()
                                    ->helperText(
                                        "Automatically waive fees below threshold",
                                    ),

                                TextInput::make("small_amount_threshold")
                                    ->label("Small Amount Threshold")
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(1.0)
                                    ->prefix('$')
                                    ->helperText(
                                        "Fees below this amount will be waived",
                                    )
                                    ->visible(
                                        fn($get) => $get("waive_small_amounts"),
                                    ),
                            ])
                            ->collapsible(),

                        Section::make("Notification Settings")
                            ->description("Configure overdue notifications")
                            ->icon("heroicon-o-bell")
                            ->schema([
                                Toggle::make("send_overdue_notifications")
                                    ->label("Send Overdue Notifications")
                                    ->default(true)
                                    ->live()
                                    ->helperText(
                                        "Notify members about overdue books",
                                    ),

                                TextInput::make("overdue_notification_days")
                                    ->label("Send Notice After")
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(3)
                                    ->suffix("days")
                                    ->helperText(
                                        "Days after due date to send first notice",
                                    )
                                    ->disabled(
                                        fn($get) => !$get(
                                            "send_overdue_notifications",
                                        ),
                                    ),
                            ])
                            ->collapsible(),

                        Section::make("Quick Reference")
                            ->description("Current fee structure summary")
                            ->icon("heroicon-o-information-circle")
                            ->schema([
                                Grid::make(1)->schema([
                                    \Filament\Forms\Components\Placeholder::make(
                                        "fee_summary",
                                    )
                                        ->label("")
                                        ->content(function ($get) {
                                            $summary = [];

                                            if ($get("overdue_fee_enabled")) {
                                                $overdueInfo =
                                                    "• **Overdue Fee:** " .
                                                    $get("currency_symbol") .
                                                    number_format(
                                                        $get(
                                                            "overdue_fee_per_day",
                                                        ) ?? 10,
                                                        2,
                                                    ) .
                                                    " per day";
                                                if ($get("grace_period_days")) {
                                                    $overdueInfo .=
                                                        " (after " .
                                                        $get(
                                                            "grace_period_days",
                                                        ) .
                                                        " day grace period)";
                                                }
                                                $summary[] = $overdueInfo;

                                                if (
                                                    $get("overdue_fee_max_days")
                                                ) {
                                                    $summary[] =
                                                        "  - Maximum " .
                                                        $get(
                                                            "overdue_fee_max_days",
                                                        ) .
                                                        " days charged";
                                                }
                                                if (
                                                    $get(
                                                        "overdue_fee_max_amount",
                                                    )
                                                ) {
                                                    $summary[] =
                                                        "  - Capped at " .
                                                        $get(
                                                            "currency_symbol",
                                                        ) .
                                                        number_format(
                                                            $get(
                                                                "overdue_fee_max_amount",
                                                            ),
                                                            2,
                                                        );
                                                }
                                            } else {
                                                $summary[] =
                                                    "• **Overdue Fees:** Disabled";
                                            }

                                            $summary[] = "";

                                            if (
                                                $get("lost_book_fine_type") ===
                                                "percentage"
                                            ) {
                                                $summary[] =
                                                    "• **Lost Book Fine:** " .
                                                    number_format(
                                                        $get(
                                                            "lost_book_fine_rate",
                                                        ) ?? 100,
                                                        0,
                                                    ) .
                                                    "% of book price";
                                                if (
                                                    $get(
                                                        "lost_book_minimum_fine",
                                                    )
                                                ) {
                                                    $summary[] =
                                                        "  - Minimum: " .
                                                        $get(
                                                            "currency_symbol",
                                                        ) .
                                                        number_format(
                                                            $get(
                                                                "lost_book_minimum_fine",
                                                            ),
                                                            2,
                                                        );
                                                }
                                                if (
                                                    $get(
                                                        "lost_book_maximum_fine",
                                                    )
                                                ) {
                                                    $summary[] =
                                                        "  - Maximum: " .
                                                        $get(
                                                            "currency_symbol",
                                                        ) .
                                                        number_format(
                                                            $get(
                                                                "lost_book_maximum_fine",
                                                            ),
                                                            2,
                                                        );
                                                }
                                            } else {
                                                $summary[] =
                                                    "• **Lost Book Fine:** " .
                                                    $get("currency_symbol") .
                                                    number_format(
                                                        $get(
                                                            "lost_book_fine_rate",
                                                        ) ?? 50,
                                                        2,
                                                    ) .
                                                    " (fixed)";
                                            }

                                            $summary[] = "";
                                            $summary[] =
                                                "• **Partial Payments:** " .
                                                ($get("allow_partial_payment")
                                                    ? "Allowed"
                                                    : "Not allowed");

                                            if ($get("waive_small_amounts")) {
                                                $summary[] =
                                                    "• **Auto-waive:** Fees under " .
                                                    $get("currency_symbol") .
                                                    number_format(
                                                        $get(
                                                            "small_amount_threshold",
                                                        ) ?? 1,
                                                        2,
                                                    );
                                            }

                                            return new \Illuminate\Support\HtmlString(
                                                implode("<br>", $summary),
                                            );
                                        }),
                                ]),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                    ])
                    ->columnSpan(1),
            ]),
        ]);
    }
}
