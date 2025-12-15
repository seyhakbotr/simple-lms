<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BorrowedStatus;
use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Http\Traits\NavigationCount;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\FeeCalculator;
use App\Settings\FeeSettings;
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
                                            ->content(
                                                fn($record) => $record
                                                    ? $record->formatted_fine
                                                    : "N/A",
                                            ),
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
                                            ($get("status") === "returned" ||
                                                $get("status") === "delayed" ||
                                                $get("status") === "lost" ||
                                                $get("status") === "damaged"),
                                    )
                                    ->afterOrEqual("borrowed_date")
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        $set,
                                        $get,
                                        $record,
                                    ) {
                                        // Real-time fine calculation preview
                                        if (!$state || !$record) {
                                            return;
                                        }

                                        $feeCalculator = app(
                                            \App\Services\FeeCalculator::class,
                                        );
                                        $totalFine = 0;

                                        // Calculate preview fine for each item
                                        foreach ($record->items as $item) {
                                            $fine = $feeCalculator->calculateOverdueFine(
                                                $item,
                                                \Illuminate\Support\Carbon::parse(
                                                    $state,
                                                ),
                                            );
                                            $totalFine += $fine;
                                        }

                                        // Store preview in a hidden field or state
                                        $set("fine_preview", $totalFine);
                                    })
                                    ->required(
                                        fn(string $context) => $context ===
                                            "edit",
                                    )
                                    ->helperText(function (Get $get, $record) {
                                        if (
                                            !$get("returned_date") ||
                                            !$record
                                        ) {
                                            return null;
                                        }

                                        $feeCalculator = app(
                                            \App\Services\FeeCalculator::class,
                                        );
                                        $totalFine = 0;

                                        foreach ($record->items as $item) {
                                            $fine = $feeCalculator->calculateOverdueFine(
                                                $item,
                                                \Illuminate\Support\Carbon::parse(
                                                    $get("returned_date"),
                                                ),
                                            );
                                            $totalFine += $fine;
                                        }

                                        if ($totalFine > 0) {
                                            return "💰 Estimated fine: " .
                                                $feeCalculator->formatFine(
                                                    $totalFine,
                                                );
                                        }

                                        return "✓ No fine - returned on time";
                                    })
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
                                ->options(
                                    fn(string $operation) => $operation ===
                                    "create"
                                        ? [
                                            BorrowedStatus::Borrowed
                                                ->value => BorrowedStatus::Borrowed->getLabel(),
                                        ]
                                        : BorrowedStatus::class,
                                )
                                ->default(BorrowedStatus::Borrowed)
                                ->inline()
                                ->live(),
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

                                            // Calculate fees based on returned_date (real-time or stored)
                                            $returnDate = $get("returned_date")
                                                ? \Illuminate\Support\Carbon::parse(
                                                    $get("returned_date"),
                                                )
                                                : $record->returned_date;

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

                                            // Lost book fees
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

                                            // Damage fees
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
                //
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
