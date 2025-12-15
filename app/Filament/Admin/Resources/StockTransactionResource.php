<?php

namespace App\Filament\Admin\Resources;

use App\Enums\StockAdjustmentType;
use App\Filament\Admin\Resources\StockTransactionResource\Pages;
use App\Http\Traits\NavigationCount;
use App\Models\Book;
use App\Models\StockTransaction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StockTransactionResource extends Resource
{
    use NavigationCount;

    protected static ?string $model = StockTransaction::class;

    protected static ?string $navigationIcon = "heroicon-o-rectangle-stack";

    protected static ?string $navigationGroup = "Stock Transactions";

    protected static ?string $navigationLabel = "Stock Adjustments";

    protected static ?string $modelLabel = "Stock Adjustment";

    protected static ?string $pluralModelLabel = "Stock Adjustments";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make("Adjustment Details")
                ->schema([
                    Select::make("type")
                        ->label("Adjustment Type")
                        ->options(StockAdjustmentType::class)
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpanFull(),

                    TextInput::make("donator_name")
                        ->label("Donator Name")
                        ->placeholder(
                            "Enter the name of the person or organization",
                        )
                        ->maxLength(255)
                        ->visible(
                            fn(Get $get) => $get("type") ===
                                StockAdjustmentType::Donation->value,
                        )
                        ->columnSpanFull(),

                    Textarea::make("notes")
                        ->label("Notes")
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder(
                            "e.g., Supplier name, reason for adjustment, etc.",
                        )
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make("Books")->schema([
                Repeater::make("items")
                    ->required()
                    ->minItems(1)
                    ->schema([
                        Select::make("book_id")
                            ->label("Book")
                            ->options(
                                Book::with(["author", "publisher"])
                                    ->get()
                                    ->mapWithKeys(function ($book) {
                                        return [
                                            $book->id =>
                                                $book->title .
                                                " - " .
                                                $book->author->name .
                                                " (ISBN: " .
                                                $book->isbn .
                                                ")",
                                        ];
                                    }),
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (
                                Set $set,
                                ?string $state,
                            ) {
                                if ($state) {
                                    $book = Book::find($state);
                                    $set("current_stock", $book?->stock ?? 0);
                                    $set("old_stock", $book?->stock ?? 0);
                                    $set("isbn_display", $book?->isbn ?? "N/A");
                                }
                            })
                            ->disableOptionWhen(function (
                                $value,
                                $state,
                                Get $get,
                            ) {
                                return collect($get("../../items"))
                                    ->pluck("book_id")
                                    ->contains($value);
                            })
                            ->columnSpan(2),

                        TextInput::make("isbn_display")
                            ->label("ISBN")
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder("Select a book")
                            ->suffixIcon("heroicon-o-qr-code")
                            ->columnSpan(1),

                        TextInput::make("current_stock")
                            ->label("Current Stock")
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0),

                        TextInput::make("quantity")
                            ->label("Quantity")
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(function (
                                Set $set,
                                Get $get,
                                ?string $state,
                            ) {
                                $currentStock = (int) $get("current_stock");
                                $quantity = (int) $state;
                                $type = $get("../../type");

                                if ($type && $quantity > 0) {
                                    $adjustmentType = StockAdjustmentType::tryFrom(
                                        $type,
                                    );
                                    if (
                                        $adjustmentType ===
                                        StockAdjustmentType::Correction
                                    ) {
                                        $newStock = $quantity;
                                    } elseif (
                                        in_array($adjustmentType, [
                                            StockAdjustmentType::Purchase,
                                            StockAdjustmentType::Donation,
                                        ])
                                    ) {
                                        $newStock = $currentStock + $quantity;
                                    } else {
                                        $newStock = max(
                                            0,
                                            $currentStock - $quantity,
                                        );
                                    }
                                    $set("new_stock_preview", $newStock);
                                    $set("new_stock", $newStock);
                                }
                            }),

                        TextInput::make("new_stock_preview")
                            ->label("New Stock")
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0)
                            ->extraAttributes(["class" => "font-bold"]),

                        TextInput::make("old_stock")->hidden()->dehydrated(),

                        TextInput::make("new_stock")->hidden()->dehydrated(),
                    ])
                    ->columns(6)
                    ->defaultItems(1)
                    ->addActionLabel("Add Another Book")
                    ->reorderable(false)
                    ->collapsible()
                    ->cloneable()
                    ->columnSpanFull()
                    ->itemLabel(
                        fn(array $state): ?string => !empty($state["book_id"])
                            ? Book::find($state["book_id"])?->title
                            : null,
                    ),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("reference_number")
                    ->label("Reference #")
                    ->searchable()
                    ->sortable()
                    ->weight("bold")
                    ->copyable(),

                TextColumn::make("type")->label("Type")->badge()->sortable(),

                TextColumn::make("donator_name")
                    ->label("Donator")
                    ->searchable()
                    ->sortable()
                    ->placeholder("N/A")
                    ->toggleable()
                    ->visible(
                        fn($record) => $record &&
                            $record->type === StockAdjustmentType::Donation,
                    ),

                TextColumn::make("total_books")
                    ->label("Books")
                    ->alignCenter()
                    ->getStateUsing(fn($record) => $record->items()->count())
                    ->suffix(" book(s)"),

                TextColumn::make("total_quantity")
                    ->label("Total Qty")
                    ->alignCenter()
                    ->getStateUsing(
                        fn($record) => $record->items()->sum("quantity"),
                    ),

                TextColumn::make("user.name")
                    ->label("Adjusted By")
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make("created_at")
                    ->label("Date")
                    ->dateTime("M d, Y h:i A")
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort("created_at", "desc")
            ->filters([
                SelectFilter::make("type")
                    ->label("Adjustment Type")
                    ->options(StockAdjustmentType::class)
                    ->native(false),
            ])
            ->actions([ActionGroup::make([ViewAction::make()])])
            ->bulkActions([
                //
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
            "index" => Pages\ListStockTransactions::route("/"),
            "create" => Pages\CreateStockTransaction::route("/create"),
            "view" => Pages\ViewStockTransaction::route("/{record}"),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
