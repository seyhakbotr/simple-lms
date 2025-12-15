<?php

namespace App\Filament\Staff\Resources;

use App\Enums\BorrowedStatus;
use App\Filament\Staff\Resources\TransactionResource\Pages;
use App\Http\Traits\NavigationCount;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\FeeCalculator;
use App\Settings\FeeSettings;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Repeater;
class TransactionResource extends Resource
{
    use NavigationCount;

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = "heroicon-o-credit-card";

    protected static ?string $navigationGroup = "Books & Transactions";

    protected static ?string $recordTitleAttribute = "user.name";

    protected static ?int $globalSearchResultLimit = 20;

    /**
     * @param Transaction $record
     * @return array
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $bookTitles = $record->items->pluck("book.title")->join(", ");

        return [
            "Borrower" => $record->user->name,
            "Books Borrowed" => $bookTitles,
            "Status" => $record->status,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                Group::make()
                    ->schema([
                        Section::make("Borrowing Details")
                            ->schema([
                                // --- Reference Number ---
                                TextInput::make("reference_no")
                                    ->label("Reference No.")
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(
                                        fn($record) => $record?->reference_no ??
                                            "Auto-generated",
                                    )
                                    ->visibleOn("edit")
                                    ->helperText(
                                        "Unique transaction reference number",
                                    ),

                                // --- Common Fields (Outside Repeater) ---
                                Select::make("user_id")
                                    ->options(
                                        fn() => User::whereStatus(true)
                                            ->whereRelation(
                                                "role",
                                                "name",
                                                "borrower",
                                            )
                                            ->pluck("name", "id"),
                                    )
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->label("Borrower")
                                    ->required()
                                    ->live()
                                    ->helperText(function (Get $get) {
                                        if (!$get("user_id")) {
                                            return null;
                                        }

                                        $user = User::with(
                                            "membershipType",
                                        )->find($get("user_id"));
                                        if (!$user || !$user->membershipType) {
                                            return "User has no membership type assigned";
                                        }

                                        $current = $user->getCurrentBorrowedBooksCount();
                                        $max =
                                            $user->membershipType
                                                ->max_books_allowed;
                                        $remaining = $max - $current;

                                        if ($remaining <= 0) {
                                            return "⚠️ User has reached borrowing limit (" .
                                                $current .
                                                "/" .
                                                $max .
                                                ")";
                                        }

                                        return "✓ Can borrow " .
                                            $remaining .
                                            " more book(s) (Currently: " .
                                            $current .
                                            "/" .
                                            $max .
                                            ")";
                                    }),

                                DatePicker::make("borrowed_date")
                                    ->live()
                                    ->required()
                                    ->default(now()), // Set a default for convenience

                                // --- Repeater for Multiple Books ---
                                Repeater::make("transactions") // This name is used in CreateTransaction.php
                                    ->label("Books to Borrow")
                                    ->hiddenOn("edit") // Hide the repeater when editing a single transaction
                                    ->maxItems(function (Get $get) {
                                        if (!$get("user_id")) {
                                            return 1;
                                        }

                                        $user = User::with(
                                            "membershipType",
                                        )->find($get("user_id"));
                                        if (!$user || !$user->membershipType) {
                                            return 1;
                                        }

                                        $current = $user->getCurrentBorrowedBooksCount();
                                        $max =
                                            $user->membershipType
                                                ->max_books_allowed;
                                        $remaining = $max - $current;

                                        return max(1, $remaining);
                                    })
                                    ->schema([
                                        Select::make("book_id") // Book ID for the individual transaction
                                            ->options(
                                                fn() => Book::whereAvailable(
                                                    true,
                                                )->pluck("title", "id"),
                                            )
                                            ->native(false)
                                            ->searchable()
                                            ->preload()
                                            ->label("Book")
                                            ->required()
                                            ->distinct()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(), // Prevent duplicate books in the same form

                                        TextInput::make("borrowed_for") // Borrow duration for the individual book
                                            ->suffix("Days")
                                            ->numeric()
                                            ->live()
                                            ->minValue(1)
                                            ->maxValue(function (Get $get) {
                                                $userId = $get("../../user_id");
                                                if (!$userId) {
                                                    return 30;
                                                }

                                                $user = User::with(
                                                    "membershipType",
                                                )->find($userId);
                                                if (
                                                    !$user ||
                                                    !$user->membershipType
                                                ) {
                                                    return 30;
                                                }

                                                return $user
                                                    ->membershipType->max_borrow_days;
                                            })
                                            ->default(function (Get $get) {
                                                $userId = $get("../../user_id");
                                                if (!$userId) {
                                                    return 14;
                                                }

                                                $user = User::with(
                                                    "membershipType",
                                                )->find($userId);
                                                if (
                                                    !$user ||
                                                    !$user->membershipType
                                                ) {
                                                    return 14;
                                                }

                                                return $user
                                                    ->membershipType->max_borrow_days;
                                            })
                                            ->helperText(function (Get $get) {
                                                $userId = $get("../../user_id");
                                                if (!$userId) {
                                                    return null;
                                                }

                                                $user = User::with(
                                                    "membershipType",
                                                )->find($userId);
                                                if (
                                                    !$user ||
                                                    !$user->membershipType
                                                ) {
                                                    return null;
                                                }

                                                return "Max: " .
                                                    $user->membershipType
                                                        ->max_borrow_days .
                                                    " days for " .
                                                    $user->membershipType
                                                        ->name .
                                                    " membership";
                                            })
                                            ->required(),
                                    ])
                                    ->defaultItems(1)
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->itemLabel(
                                        fn(array $state): ?string => isset(
                                            // Display the book title as the label for each repeater item
                                            $state["book_id"],
                                        )
                                            ? Book::find($state["book_id"])
                                                ?->title
                                            : null,
                                    ),
                                // --- End Repeater ---

                                // Transaction Items repeater for editing
                                Repeater::make("items")
                                    ->relationship("items")
                                    ->label("Books in Transaction")
                                    ->visibleOn("edit")
                                    ->schema([
                                        Select::make("book_id")
                                            ->options(
                                                fn() => Book::pluck(
                                                    "title",
                                                    "id",
                                                ),
                                            )
                                            ->native(false)
                                            ->searchable()
                                            ->preload()
                                            ->label("Book")
                                            ->required()
                                            ->disabled(), // Don't allow changing books in edit mode

                                        TextInput::make("borrowed_for")
                                            ->suffix("Days")
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(30)
                                            ->required(),

                                        Placeholder::make("fine")
                                            ->label("Fine")
                                            ->content(function (
                                                $record,
                                                Get $get,
                                            ) {
                                                if (!$record) {
                                                    return "N/A";
                                                }

                                                $currentStatus = $get(
                                                    "../../status",
                                                );
                                                $feeCalculator = app(
                                                    \App\Services\FeeCalculator::class,
                                                );

                                                // If status is Lost, calculate lost book fine in real-time
                                                if (
                                                    $currentStatus ===
                                                    BorrowedStatus::Lost->value
                                                ) {
                                                    if ($record->book) {
                                                        $lostFine = $feeCalculator->calculateLostBookFine(
                                                            $record->book,
                                                        );
                                                        return $feeCalculator->formatFine(
                                                            $lostFine,
                                                        ) . " (Lost)";
                                                    }
                                                }

                                                // If status is Damaged, show existing damage fine
                                                if (
                                                    $currentStatus ===
                                                    BorrowedStatus::Damaged
                                                        ->value
                                                ) {
                                                    $damageFine =
                                                        $record->damage_fine ??
                                                        0;
                                                    if ($damageFine > 0) {
                                                        return $feeCalculator->formatFine(
                                                            $damageFine,
                                                        ) . " (Damage)";
                                                    }
                                                    return "Set damage fine";
                                                }

                                                // For Returned/Delayed, show stored fine
                                                return $record->formatted_fine;
                                            })
                                            ->live(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->deletable(false)
                                    ->addable(false),

                                DatePicker::make("returned_date")
                                    ->visible(
                                        fn(
                                            Get $get,
                                            string $operation,
                                        ): bool => $operation === "edit" &&
                                            $get("status") === "borrowed", // Only show for active transactions
                                    )
                                    ->afterOrEqual("borrowed_date")
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        $set,
                                        $get,
                                        $record,
                                    ) {
                                        // Real-time fine calculation preview and auto-status update
                                        if (!$state || !$record) {
                                            return;
                                        }

                                        $feeCalculator = app(
                                            \App\Services\FeeCalculator::class,
                                        );
                                        $totalFine = 0;
                                        $returnDate = \Illuminate\Support\Carbon::parse(
                                            $state,
                                        );

                                        // Calculate preview fine for each item
                                        foreach ($record->items as $item) {
                                            $fine = $feeCalculator->calculateOverdueFine(
                                                $item,
                                                $returnDate,
                                            );
                                            $totalFine += $fine;
                                        }

                                        // Auto-determine status based on return date
                                        // ONLY if not manually set to Lost or Damaged
                                        $currentStatus = $get("status");
                                        if (
                                            $currentStatus !==
                                                BorrowedStatus::Lost->value &&
                                            $currentStatus !==
                                                BorrowedStatus::Damaged->value
                                        ) {
                                            $dueDate = $record->due_date;
                                            if ($returnDate->lte($dueDate)) {
                                                $set(
                                                    "status",
                                                    BorrowedStatus::Returned
                                                        ->value,
                                                );
                                            } else {
                                                $set(
                                                    "status",
                                                    BorrowedStatus::Delayed
                                                        ->value,
                                                );
                                            }
                                        }

                                        // Store preview in a hidden field or state
                                        $set("fine_preview", $totalFine);
                                    })
                                    ->helperText(function (Get $get, $record) {
                                        if (
                                            !$get("returned_date") ||
                                            !$record
                                        ) {
                                            return "📅 Set the return date to finalize this transaction";
                                        }

                                        $feeCalculator = app(
                                            \App\Services\FeeCalculator::class,
                                        );
                                        $totalFine = 0;
                                        $returnDate = \Illuminate\Support\Carbon::parse(
                                            $get("returned_date"),
                                        );

                                        foreach ($record->items as $item) {
                                            $fine = $feeCalculator->calculateOverdueFine(
                                                $item,
                                                $returnDate,
                                            );
                                            $totalFine += $fine;
                                        }

                                        $currentStatus = $get("status");

                                        // Show appropriate message based on current status
                                        if (
                                            $currentStatus ===
                                            BorrowedStatus::Lost->value
                                        ) {
                                            $statusText =
                                                "❌ Status: LOST (manually set)";
                                        } elseif (
                                            $currentStatus ===
                                            BorrowedStatus::Damaged->value
                                        ) {
                                            $statusText =
                                                "⚠️ Status: DAMAGED (manually set)";
                                        } else {
                                            $dueDate = $record->due_date;
                                            $statusText = $returnDate->lte(
                                                $dueDate,
                                            )
                                                ? "✓ On Time - Status will be: RETURNED"
                                                : "⚠️ Late - Status will be: DELAYED";
                                        }

                                        if ($totalFine > 0) {
                                            return $statusText .
                                                " | 💰 Fine: " .
                                                $feeCalculator->formatFine(
                                                    $totalFine,
                                                );
                                        }

                                        return $statusText . " | ✓ No fine";
                                    })
                                    ->columnSpanFull(),

                                Placeholder::make("finalized_info")
                                    ->label("Transaction Finalized")
                                    ->content(function ($record) {
                                        if (
                                            !$record ||
                                            !$record->returned_date
                                        ) {
                                            return "";
                                        }

                                        $status = $record->status->getLabel();
                                        $icon = match ($record->status) {
                                            BorrowedStatus::Returned => "✓",
                                            BorrowedStatus::Delayed => "⚠️",
                                            BorrowedStatus::Lost => "❌",
                                            BorrowedStatus::Damaged => "⚠️",
                                            default => "ℹ️",
                                        };

                                        return "{$icon} This transaction was finalized as **{$status}** on " .
                                            $record->returned_date->format(
                                                "M d, Y",
                                            );
                                    })
                                    ->visible(
                                        fn($record) => $record &&
                                            $record->returned_date,
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(["sm" => 2, "md" => 2, "xxl" => 5]),

                // --- Status & Fine Column ---
                Group::make()
                    ->schema([
                        Section::make()->schema([
                            ToggleButtons::make("status")
                                ->options(function (
                                    string $operation,
                                    $record,
                                ) {
                                    if ($operation === "create") {
                                        return [
                                            BorrowedStatus::Borrowed
                                                ->value => BorrowedStatus::Borrowed->getLabel(),
                                        ];
                                    }

                                    // For edit mode, restrict based on current status
                                    if (!$record) {
                                        return [
                                            BorrowedStatus::Borrowed
                                                ->value => BorrowedStatus::Borrowed->getLabel(),
                                            BorrowedStatus::Lost
                                                ->value => BorrowedStatus::Lost->getLabel(),
                                            BorrowedStatus::Damaged
                                                ->value => BorrowedStatus::Damaged->getLabel(),
                                        ];
                                    }

                                    $currentStatus = $record->status;

                                    // If already finalized (Returned, Delayed, Lost, Damaged),
                                    // only show current status (read-only)
                                    if (
                                        in_array($currentStatus, [
                                            BorrowedStatus::Returned,
                                            BorrowedStatus::Delayed,
                                            BorrowedStatus::Lost,
                                            BorrowedStatus::Damaged,
                                        ])
                                    ) {
                                        // Only show current status - it's final
                                        return [
                                            $currentStatus->value => $currentStatus->getLabel(),
                                        ];
                                    }

                                    // If still Borrowed, only allow manual selection of:
                                    // Borrowed, Lost, Damaged
                                    // (Returned and Delayed are auto-determined by return date)
                                    return [
                                        BorrowedStatus::Borrowed
                                            ->value => BorrowedStatus::Borrowed->getLabel(),
                                        BorrowedStatus::Lost
                                            ->value => BorrowedStatus::Lost->getLabel(),
                                        BorrowedStatus::Damaged
                                            ->value => BorrowedStatus::Damaged->getLabel(),
                                    ];
                                })
                                ->default(BorrowedStatus::Borrowed)
                                ->inline()
                                ->live()
                                ->disabled(function ($record) {
                                    // Disable status changes for finalized transactions
                                    if (!$record) {
                                        return false;
                                    }

                                    return in_array($record->status, [
                                        BorrowedStatus::Returned,
                                        BorrowedStatus::Delayed,
                                        BorrowedStatus::Lost,
                                        BorrowedStatus::Damaged,
                                    ]);
                                })
                                ->helperText(function ($record) {
                                    if (!$record) {
                                        return null;
                                    }

                                    if (
                                        in_array($record->status, [
                                            BorrowedStatus::Returned,
                                            BorrowedStatus::Delayed,
                                            BorrowedStatus::Lost,
                                            BorrowedStatus::Damaged,
                                        ])
                                    ) {
                                        return "⚠️ This transaction is finalized. Status cannot be changed.";
                                    }

                                    return "💡 Tip: Set return date to auto-determine Returned/Delayed status. Manually select Lost or Damaged if applicable.";
                                }),
                            Group::make()
                                ->schema([
                                    Placeholder::make("fine_breakdown")
                                        ->label("Fee Breakdown")
                                        ->content(function (
                                            Get $get,
                                            $record,
                                        ): string {
                                            if (!$record) {
                                                return "N/A";
                                            }

                                            $feeCalculator = app(
                                                \App\Services\FeeCalculator::class,
                                            );
                                            $breakdown = [];
                                            $currentStatus = $get("status");

                                            // Calculate fees based on returned_date (real-time or stored)
                                            $returnDate = $get("returned_date")
                                                ? \Illuminate\Support\Carbon::parse(
                                                    $get("returned_date"),
                                                )
                                                : $record->returned_date;

                                            // Calculate Lost book fine in real-time if status is Lost
                                            if (
                                                $currentStatus ===
                                                BorrowedStatus::Lost->value
                                            ) {
                                                $lostFine = 0;
                                                // Ensure items and books are loaded
                                                $record->load("items.book");
                                                foreach (
                                                    $record->items
                                                    as $item
                                                ) {
                                                    if ($item->book) {
                                                        $lostFine += $feeCalculator->calculateLostBookFine(
                                                            $item->book,
                                                        );
                                                    }
                                                }
                                                if ($lostFine > 0) {
                                                    $breakdown[] =
                                                        "Lost Books: " .
                                                        $feeCalculator->formatFine(
                                                            $lostFine,
                                                        );
                                                }

                                                // Also show overdue if return date is set
                                                if ($returnDate) {
                                                    $overdueFine = 0;
                                                    foreach (
                                                        $record->items
                                                        as $item
                                                    ) {
                                                        $overdueFine += $feeCalculator->calculateOverdueFine(
                                                            $item,
                                                            $returnDate,
                                                        );
                                                    }
                                                    if ($overdueFine > 0) {
                                                        $breakdown[] =
                                                            "Overdue: " .
                                                            $feeCalculator->formatFine(
                                                                $overdueFine,
                                                            );
                                                    }
                                                }

                                                if (empty($breakdown)) {
                                                    return "❌ Lost book fee will be calculated";
                                                }

                                                $total =
                                                    ($lostFine ?? 0) +
                                                    ($overdueFine ?? 0);
                                                $breakdown[] =
                                                    "**Total: " .
                                                    $feeCalculator->formatFine(
                                                        $total,
                                                    ) .
                                                    "**";

                                                return implode(
                                                    "\n",
                                                    $breakdown,
                                                );
                                            }

                                            // Calculate Damage fine in real-time if status is Damaged
                                            if (
                                                $currentStatus ===
                                                BorrowedStatus::Damaged->value
                                            ) {
                                                // Ensure items are loaded
                                                $record->load("items.book");
                                                // Show existing damage fine if saved
                                                $damageFine = $record->items->sum(
                                                    "damage_fine",
                                                );
                                                if ($damageFine > 0) {
                                                    $breakdown[] =
                                                        "Damage: " .
                                                        $feeCalculator->formatFine(
                                                            $damageFine,
                                                        );
                                                }

                                                // Also show overdue if return date is set
                                                if ($returnDate) {
                                                    $overdueFine = 0;
                                                    foreach (
                                                        $record->items
                                                        as $item
                                                    ) {
                                                        $overdueFine += $feeCalculator->calculateOverdueFine(
                                                            $item,
                                                            $returnDate,
                                                        );
                                                    }
                                                    if ($overdueFine > 0) {
                                                        $breakdown[] =
                                                            "Overdue: " .
                                                            $feeCalculator->formatFine(
                                                                $overdueFine,
                                                            );
                                                    }
                                                }

                                                if (empty($breakdown)) {
                                                    return "⚠️ Set damage fine manually";
                                                }

                                                $total =
                                                    $damageFine +
                                                    ($overdueFine ?? 0);
                                                $breakdown[] =
                                                    "**Total: " .
                                                    $feeCalculator->formatFine(
                                                        $total,
                                                    ) .
                                                    "**";

                                                return implode(
                                                    "\n",
                                                    $breakdown,
                                                );
                                            }

                                            if (!$returnDate) {
                                                // For active transactions, show current overdue if any
                                                if ($record->isOverdue()) {
                                                    $currentFine = 0;
                                                    foreach (
                                                        $record->items
                                                        as $item
                                                    ) {
                                                        $currentFine += $feeCalculator->calculateCurrentOverdueFine(
                                                            $item,
                                                        );
                                                    }
                                                    if ($currentFine > 0) {
                                                        return "⚠️ Current Overdue: " .
                                                            $feeCalculator->formatFine(
                                                                $currentFine,
                                                            ) .
                                                            " (" .
                                                            $record->getDaysOverdue() .
                                                            " days late)";
                                                    }
                                                }
                                                return "No fines yet";
                                            }

                                            // Calculate overdue fines
                                            $overdueFine = 0;
                                            foreach ($record->items as $item) {
                                                $overdueFine += $feeCalculator->calculateOverdueFine(
                                                    $item,
                                                    $returnDate,
                                                );
                                            }

                                            if ($overdueFine > 0) {
                                                $breakdown[] =
                                                    "Overdue: " .
                                                    $feeCalculator->formatFine(
                                                        $overdueFine,
                                                    );
                                            }

                                            // Lost book fees (from saved data)
                                            $lostFine = $record->items->sum(
                                                "lost_fine",
                                            );
                                            if ($lostFine > 0) {
                                                $breakdown[] =
                                                    "Lost Books: " .
                                                    $feeCalculator->formatFine(
                                                        $lostFine,
                                                    );
                                            }

                                            // Damage fees (from saved data)
                                            $damageFine = $record->items->sum(
                                                "damage_fine",
                                            );
                                            if ($damageFine > 0) {
                                                $breakdown[] =
                                                    "Damage: " .
                                                    $feeCalculator->formatFine(
                                                        $damageFine,
                                                    );
                                            }

                                            if (empty($breakdown)) {
                                                return "✓ No fines";
                                            }

                                            $total =
                                                $overdueFine +
                                                $lostFine +
                                                $damageFine;
                                            $breakdown[] =
                                                "**Total: " .
                                                $feeCalculator->formatFine(
                                                    $total,
                                                ) .
                                                "**";

                                            return implode("\n", $breakdown);
                                        })
                                        ->live()
                                        ->visible(
                                            fn(Get $get, $record) => $record &&
                                                ($get("status") === "delayed" ||
                                                    $get("status") ===
                                                        "returned" ||
                                                    $get("status") === "lost" ||
                                                    $get("status") ===
                                                        "damaged" ||
                                                    $get("returned_date")),
                                        ),

                                    Placeholder::make("fine")
                                        ->label(function (): string {
                                            $feeCalculator = app(
                                                FeeCalculator::class,
                                            );
                                            $feeSummary = $feeCalculator->getFeeSummary();

                                            if (
                                                !$feeSummary["overdue_enabled"]
                                            ) {
                                                return "Overdue Fees (Disabled)";
                                            }

                                            $feeLabel =
                                                $feeSummary["currency_symbol"] .
                                                number_format(
                                                    $feeSummary[
                                                        "overdue_per_day"
                                                    ],
                                                    2,
                                                ) .
                                                " Per Day";

                                            if (
                                                $feeSummary["grace_period"] > 0
                                            ) {
                                                $feeLabel .=
                                                    " (After " .
                                                    $feeSummary[
                                                        "grace_period"
                                                    ] .
                                                    " Day Grace Period)";
                                            }

                                            return $feeLabel;
                                        })
                                        ->content(function (
                                            Get $get,
                                            $record,
                                        ): string {
                                            if (!$record) {
                                                return "N/A";
                                            }

                                            // For delayed (overdue but not returned): show current overdue
                                            if (
                                                !$record->returned_date &&
                                                $record->isOverdue()
                                            ) {
                                                $totalFine =
                                                    $record->total_fine ?? 0;

                                                if ($totalFine > 0) {
                                                    return "Current Overdue: " .
                                                        $record->formatted_total_fine .
                                                        " (" .
                                                        $record->getDaysOverdue() .
                                                        " days late)";
                                                }

                                                return "No fine (within grace period)";
                                            }

                                            // For returned transactions: show final fine
                                            if ($record->returned_date) {
                                                $totalFine =
                                                    $record->total_fine ?? 0;

                                                if ($totalFine > 0) {
                                                    return "Total: " .
                                                        $record->formatted_total_fine;
                                                }

                                                return "No fine";
                                            }

                                            return "No fine yet";
                                        })
                                        ->live()
                                        ->visible(fn() => false), // Hidden, replaced by fee_breakdown
                                ])
                                ->visibleOn("edit"),
                        ]),
                    ])
                    ->columnSpan(["sm" => 2, "md" => 1, "xxl" => 1]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("reference_no")
                    ->label("Reference No.")
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage("Reference number copied")
                    ->placeholder("N/A"),
                TextColumn::make("user.name")
                    ->sortable()
                    ->searchable()
                    ->label("Borrower"),
                TextColumn::make("items_count")
                    ->counts("items")
                    ->label("Books")
                    ->badge()
                    ->color("info"),
                TextColumn::make("items.book.title")
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->searchable()
                    ->label("Book Titles"),
                TextColumn::make("borrowed_date")->date("d M, Y")->sortable(),
                TextColumn::make("returned_date")
                    ->date("d M, Y")
                    ->sortable()
                    ->placeholder("Not returned"),
                TextColumn::make("status")->badge()->sortable(),
                TextColumn::make("total_fine")
                    ->label("Total Fine")
                    ->getStateUsing(
                        fn($record) => $record->formatted_total_fine,
                    )
                    ->placeholder('$0.00'),
            ])
            ->filters([
                Tables\Filters\Filter::make("reference_no")
                    ->form([
                        Forms\Components\TextInput::make("reference_no")
                            ->label("Reference Number")
                            ->placeholder("e.g., TXN-20250115-0001"),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data["reference_no"],
                            fn($query, $ref) => $query->where(
                                "reference_no",
                                "like",
                                "%{$ref}%",
                            ),
                        );
                    }),
            ])
            ->actions([
                ActionGroup::make([EditAction::make(), DeleteAction::make()]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListTransactions::route("/"),
            "create" => Pages\CreateTransaction::route("/create"),
            "edit" => Pages\EditTransaction::route("/{record}/edit"),
        ];
    }
}
