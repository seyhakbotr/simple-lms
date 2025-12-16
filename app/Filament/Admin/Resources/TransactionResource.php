<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BorrowedStatus;
use App\Enums\LifecycleStatus;
use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Http\Traits\NavigationCount;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class TransactionResource extends Resource
{
    use NavigationCount;

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = "heroicon-o-credit-card";

    protected static ?string $navigationGroup = "Books & Transactions";

    protected static ?string $recordTitleAttribute = "user.name";

    protected static ?int $globalSearchResultLimit = 20;

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $bookTitles = $record->items->pluck("book.title")->join(", ");

        return [
            "Borrower" => $record->user->name,
            "Books Borrowed" => $bookTitles,
            "Lifecycle" => $record->lifecycle_status?->getLabel() ?? "N/A",
            "Status" => $record->status?->getLabel() ?? "N/A",
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // CREATE FORM
            Grid::make(2)
                ->schema([
                    Section::make("Borrower Information")
                        ->schema([
                            Select::make("user_id")
                                ->label("Select Borrower")
                                ->options(
                                    fn() => User::whereStatus(true)
                                        ->whereRelation(
                                            "role",
                                            "name",
                                            "borrower",
                                        )
                                        ->with("membershipType")
                                        ->get()
                                        ->mapWithKeys(
                                            fn($user) => [
                                                $user->id =>
                                                    $user->name .
                                                    ($user->membershipType
                                                        ? " ({$user->membershipType->name})"
                                                        : " (No Membership)"),
                                            ],
                                        ),
                                )
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn($state) => $state)
                                ->helperText(function (Get $get) {
                                    if (!$get("user_id")) {
                                        return "Select a borrower to see their details";
                                    }

                                    $user = User::with("membershipType")->find(
                                        $get("user_id"),
                                    );
                                    if (!$user) {
                                        return null;
                                    }

                                    if (!$user->membershipType) {
                                        return "⚠️ This user has no membership type assigned";
                                    }

                                    if (!$user->hasActiveMembership()) {
                                        return "⚠️ Membership expired on " .
                                            $user->membership_expires_at?->format(
                                                "M d, Y",
                                            );
                                    }

                                    $current = $user->getCurrentBorrowedBooksCount();
                                    $max =
                                        $user->membershipType
                                            ->max_books_allowed;
                                    $remaining = $max - $current;

                                    if ($remaining <= 0) {
                                        return "⚠️ Borrowing limit reached ({$current}/{$max})";
                                    }

                                    return "✓ Can borrow {$remaining} more book(s) | Currently: {$current}/{$max} | Max days: {$user->membershipType->max_borrow_days}";
                                }),

                            DatePicker::make("borrowed_date")
                                ->label("Borrow Date")
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->native(false),

                            TextInput::make("borrow_days")
                                ->label("Borrow Duration (Days)")
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(function (Get $get) {
                                    if (!$get("user_id")) {
                                        return 14;
                                    }
                                    $user = User::with("membershipType")->find(
                                        $get("user_id"),
                                    );
                                    return $user?->membershipType
                                        ?->max_borrow_days ?? 14;
                                })
                                ->maxValue(function (Get $get) {
                                    if (!$get("user_id")) {
                                        return 30;
                                    }
                                    $user = User::with("membershipType")->find(
                                        $get("user_id"),
                                    );
                                    return $user?->membershipType
                                        ?->max_borrow_days ?? 30;
                                })
                                ->suffix("days")
                                ->helperText(function (Get $get) {
                                    if (!$get("user_id")) {
                                        return null;
                                    }
                                    $user = User::with("membershipType")->find(
                                        $get("user_id"),
                                    );
                                    $maxDays =
                                        $user?->membershipType
                                            ?->max_borrow_days ?? 30;
                                    return "Maximum: {$maxDays} days";
                                }),
                        ])
                        ->columnSpan(1)
                        ->hiddenOn("view"),

                    Section::make("Select Books")
                        ->schema([
                            Select::make("books")
                                ->label("Books to Borrow")
                                ->multiple()
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->options(
                                    fn() => Book::where("stock", ">", 0)
                                        ->where("available", true)
                                        ->get()
                                        ->mapWithKeys(
                                            fn($book) => [
                                                $book->id =>
                                                    $book->title .
                                                    " (Stock: {$book->stock})",
                                            ],
                                        ),
                                )
                                ->helperText(function (Get $get) {
                                    if (!$get("user_id")) {
                                        return "Select a borrower first";
                                    }
                                    $user = User::with("membershipType")->find(
                                        $get("user_id"),
                                    );
                                    if (!$user?->membershipType) {
                                        return "User has no membership type";
                                    }
                                    $current = $user->getCurrentBorrowedBooksCount();
                                    $max =
                                        $user->membershipType
                                            ->max_books_allowed;
                                    $available = $max - $current;
                                    return "You can select up to {$available} book(s)";
                                })
                                ->maxItems(function (Get $get) {
                                    if (!$get("user_id")) {
                                        return 10; // Default max if no user selected
                                    }
                                    $user = User::with("membershipType")->find(
                                        $get("user_id"),
                                    );
                                    if (!$user?->membershipType) {
                                        return 10; // Default max if no membership
                                    }
                                    $current = $user->getCurrentBorrowedBooksCount();
                                    $max =
                                        $user->membershipType
                                            ->max_books_allowed;
                                    return max(1, $max - $current);
                                })
                                ->native(false),
                        ])
                        ->columnSpan(1)
                        ->hiddenOn("view"),
                ])
                ->hiddenOn("view"),

            // VIEW FORM
            Grid::make(3)
                ->schema([
                    Section::make("Transaction Details")
                        ->schema([
                            Placeholder::make("reference_no")
                                ->label("Reference No.")
                                ->content(
                                    fn($record) => $record?->reference_no ??
                                        "N/A",
                                ),

                            Placeholder::make("user.name")
                                ->label("Borrower")
                                ->content(
                                    fn($record) => new HtmlString(
                                        '<div class="font-medium">' .
                                            ($record?->user?->name ?? "N/A") .
                                            "</div>" .
                                            '<div class="text-sm text-gray-500">' .
                                            ($record?->user?->email ?? "") .
                                            "</div>",
                                    ),
                                ),

                            Placeholder::make("user.membershipType.name")
                                ->label("Membership Type")
                                ->content(
                                    fn($record) => $record?->user
                                        ?->membershipType?->name ?? "None",
                                ),

                            Placeholder::make("borrowed_date")
                                ->label("Borrowed Date")
                                ->content(
                                    fn(
                                        $record,
                                    ) => $record?->borrowed_date?->format(
                                        "M d, Y",
                                    ) ?? "N/A",
                                ),

                            Placeholder::make("due_date")
                                ->label("Due Date")
                                ->content(function ($record) {
                                    if (!$record || !$record->due_date) {
                                        return "N/A";
                                    }

                                    $color = $record->isOverdue()
                                        ? "text-danger-600 font-semibold"
                                        : "";
                                    $warning = $record->isOverdue()
                                        ? '<div class="text-sm text-danger-500">Overdue by ' .
                                            $record->getDaysOverdue() .
                                            " day(s)</div>"
                                        : "";

                                    return new HtmlString(
                                        '<div class="' .
                                            $color .
                                            '">' .
                                            $record->due_date->format(
                                                "M d, Y",
                                            ) .
                                            "</div>" .
                                            $warning,
                                    );
                                }),

                            Placeholder::make("returned_date")
                                ->label("Returned Date")
                                ->content(
                                    fn(
                                        $record,
                                    ) => $record?->returned_date?->format(
                                        "M d, Y",
                                    ) ?? "Not yet returned",
                                ),

                            Placeholder::make("lifecycle_status")
                                ->label("Lifecycle Status")
                                ->content(function ($record) {
                                    if (
                                        !$record ||
                                        !$record->lifecycle_status
                                    ) {
                                        return "N/A";
                                    }

                                    $lifecycleColor = match (
                                        $record->lifecycle_status->value
                                    ) {
                                        "active"
                                            => "text-blue-700 bg-blue-50 dark:bg-blue-500/10 dark:text-blue-400",
                                        "completed"
                                            => "text-green-700 bg-green-50 dark:bg-green-500/10 dark:text-green-400",
                                        "cancelled"
                                            => "text-gray-700 bg-gray-50 dark:bg-gray-500/10 dark:text-gray-400",
                                        "archived"
                                            => "text-purple-700 bg-purple-50 dark:bg-purple-500/10 dark:text-purple-400",
                                        default => "text-gray-700 bg-gray-50",
                                    };

                                    $lifecycleLabel = $record->lifecycle_status->getLabel();
                                    $lifecycleBadge =
                                        '<span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' .
                                        $lifecycleColor .
                                        '">' .
                                        $lifecycleLabel .
                                        "</span>";

                                    return new HtmlString($lifecycleBadge);
                                }),

                            Placeholder::make("renewed_count")
                                ->label("Times Renewed")
                                ->content(
                                    fn($record) => $record?->renewed_count ?? 0,
                                ),
                        ])
                        ->columnSpan(1)
                        ->visibleOn("view"),

                    Section::make("Borrowed Books")
                        ->schema([
                            Repeater::make("items")
                                ->relationship("items")
                                ->schema([
                                    Placeholder::make("book.title")
                                        ->label("Book Title")
                                        ->content(
                                            fn($record) => $record?->book
                                                ?->title ?? "N/A",
                                        ),

                                    Placeholder::make("borrowed_for")
                                        ->label("Borrowed For")
                                        ->content(
                                            fn(
                                                $record,
                                            ) => ($record?->borrowed_for ?? 0) .
                                                " days",
                                        ),

                                    Placeholder::make("due_date")
                                        ->label("Due Date")
                                        ->content(
                                            fn(
                                                $record,
                                            ) => $record?->due_date?->format(
                                                "M d, Y",
                                            ) ?? "N/A",
                                        ),

                                    Placeholder::make("item_status")
                                        ->label("Condition")
                                        ->content(function ($record) {
                                            if (
                                                !$record ||
                                                !$record->item_status
                                            ) {
                                                return "N/A";
                                            }

                                            $statusColor = match (
                                                $record->item_status
                                            ) {
                                                "returned"
                                                    => "text-green-700 bg-green-50 dark:bg-green-500/10 dark:text-green-400",
                                                "borrowed"
                                                    => "text-blue-700 bg-blue-50 dark:bg-blue-500/10 dark:text-blue-400",
                                                "lost"
                                                    => "text-red-700 bg-red-50 dark:bg-red-500/10 dark:text-red-400",
                                                "damaged"
                                                    => "text-orange-700 bg-orange-50 dark:bg-orange-500/10 dark:text-orange-400",
                                                default
                                                    => "text-gray-700 bg-gray-50",
                                            };

                                            $badge =
                                                '<span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' .
                                                $statusColor .
                                                '">' .
                                                ucfirst($record->item_status) .
                                                "</span>";

                                            return new HtmlString($badge);
                                        }),
                                ])
                                ->columns(4)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ])
                        ->columnSpan(2)
                        ->visibleOn("view"),

                    Section::make("Fee Information")
                        ->schema([
                            Placeholder::make("overdue_status")
                                ->label("Overdue Status")
                                ->content(function ($record) {
                                    if (!$record) {
                                        return "N/A";
                                    }

                                    if ($record->returned_date) {
                                        return "Returned";
                                    }
                                    if ($record->isOverdue()) {
                                        return "⚠️ Overdue by " .
                                            $record->getDaysOverdue() .
                                            " day(s)";
                                    }
                                    return "✓ On time";
                                }),

                            Placeholder::make("total_fine")
                                ->label("Total Fees")
                                ->content(
                                    fn(
                                        $record,
                                    ) => $record?->formatted_total_fine ??
                                        "$0.00",
                                ),
                        ])
                        ->columnSpan(3)
                        ->visibleOn("view"),
                ])
                ->visibleOn("view"),
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
                TextColumn::make("due_date")
                    ->date("d M, Y")
                    ->sortable()
                    ->color(
                        fn($record) => $record->isOverdue() ? "danger" : null,
                    ),
                TextColumn::make("returned_date")
                    ->date("d M, Y")
                    ->sortable()
                    ->placeholder("Not returned"),
                TextColumn::make("lifecycle_status")
                    ->label("Lifecycle")
                    ->badge()
                    ->sortable(),
                TextColumn::make("status")
                    ->label("Condition")
                    ->badge()
                    ->sortable()
                    ->visible(
                        fn($record) => $record && $record->status !== null,
                    )
                    ->placeholder("N/A"),
                TextColumn::make("total_fine")
                    ->label("Total Fine")
                    ->getStateUsing(
                        fn($record) => $record->formatted_total_fine,
                    )
                    ->placeholder('$0.00'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("lifecycle_status")
                    ->label("Lifecycle Status")
                    ->options(LifecycleStatus::class),
                Tables\Filters\SelectFilter::make("status")
                    ->label("Condition Status")
                    ->options(BorrowedStatus::class),
                Tables\Filters\Filter::make("overdue")
                    ->label("Overdue Only")
                    ->query(
                        fn($query) => $query
                            ->where("due_date", "<", now())
                            ->whereNull("returned_date"),
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make("return")
                        ->label("Return Books")
                        ->icon("heroicon-o-arrow-uturn-left")
                        ->color("success")
                        ->url(
                            fn(Transaction $record): string => self::getUrl(
                                "return",
                                ["record" => $record],
                            ),
                        )
                        ->visible(
                            fn(
                                Transaction $record,
                            ): bool => !$record->returned_date,
                        ),

                    Tables\Actions\Action::make("renew")
                        ->label("Renew")
                        ->icon("heroicon-o-arrow-path")
                        ->color("warning")
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $service = app(
                                \App\Services\TransactionService::class,
                            );
                            $result = $service->renewTransaction($record);

                            if ($result["success"]) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title("Transaction Renewed")
                                    ->body($result["message"])
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title("Cannot Renew")
                                    ->body($result["message"])
                                    ->send();
                            }
                        })
                        ->visible(
                            fn(
                                Transaction $record,
                            ): bool => !$record->returned_date,
                        ),

                    Tables\Actions\ViewAction::make()->label("View Details"),

                    DeleteAction::make()->visible(
                        fn(
                            Transaction $record,
                        ): bool => !$record->returned_date,
                    ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort("created_at", "desc");
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
            "view" => Pages\ViewTransaction::route("/{record}"),
            "return" => Pages\ReturnTransaction::route("/{record}/return"),
        ];
    }
}
