<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BorrowedStatus;
use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Http\Traits\NavigationCount;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
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
                                    ->required(),

                                DatePicker::make("borrowed_date")
                                    ->live()
                                    ->required()
                                    ->default(now()), // Set a default for convenience

                                // --- Repeater for Multiple Books ---
                                Repeater::make("transactions") // This name is used in CreateTransaction.php
                                    ->label("Books to Borrow")
                                    ->hiddenOn("edit") // Hide the repeater when editing a single transaction
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
                                            ->minValue(0)
                                            ->maxValue(30)
                                            ->required(),
                                    ])
                                    ->defaultItems(1)
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->itemLabel(
                                        fn(array $state): ?string => isset( // Display the book title as the label for each repeater item
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
                                                    ? '$' .
                                                        number_format(
                                                            $record->fine ?? 0,
                                                            2,
                                                        )
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
                                                $get("status") === "delayed"),
                                    )
                                    ->afterOrEqual("borrowed_date")
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        $set,
                                        $get,
                                    ) {
                                        // This will trigger fine recalculation when returned_date changes
                                    })
                                    ->required(
                                        fn(string $context) => $context ===
                                            "edit",
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
                                    Placeholder::make("fine")
                                        ->label('$10 Per Day After Delay')
                                        ->content(function (
                                            Get $get,
                                            $record,
                                        ): string {
                                            // Guard against null record or missing returned_date
                                            if (!$record) {
                                                return "N/A";
                                            }

                                            if (!$record->returned_date) {
                                                return "N/A";
                                            }

                                            $totalFine =
                                                $record->total_fine ?? 0;

                                            if ($totalFine > 0) {
                                                return 'Total: $' .
                                                    number_format(
                                                        $totalFine,
                                                        2,
                                                    );
                                            }

                                            return "No fine";
                                        })
                                        ->live()
                                        ->visible(
                                            fn(Get $get, $record) => $record &&
                                                $get("returned_date") &&
                                                $get("status") === "delayed",
                                        ),
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
                    ->money("usd")
                    ->getStateUsing(
                        fn($record) => ($record->total_fine ?? 0) / 100,
                    ) // Convert cents to dollars
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
