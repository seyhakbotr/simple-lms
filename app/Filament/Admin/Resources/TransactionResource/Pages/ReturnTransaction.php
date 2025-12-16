<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\TransactionService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ReturnTransaction extends Page
{
    protected static string $resource = TransactionResource::class;

    protected static string $view = "filament.staff.resources.transaction-resource.pages.return-transaction";

    protected static ?string $title = "Return Books";

    public ?array $data = [];

    public Transaction $record;

    public function mount(Transaction $record): void
    {
        $this->record = $record->load(["items.book", "user.membershipType"]);

        if ($this->record->returned_date) {
            Notification::make()
                ->warning()
                ->title("Already Returned")
                ->body("This transaction has already been returned.")
                ->persistent()
                ->send();

            $this->redirect(TransactionResource::getUrl("index"));
            return;
        }

        // Initialize form with default values
        $this->form->fill([
            "returned_date" => now()->format("Y-m-d"),
            "items" => $this->record->items
                ->map(function ($item) {
                    return [
                        "id" => $item->id,
                        "book_id" => $item->book_id,
                        "book_title" => $item->book->title,
                        "is_lost" => false,
                        "is_damaged" => false,
                        "damage_fine" => 0,
                        "damage_notes" => null,
                    ];
                })
                ->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    // Left Column - Transaction Info
                    Section::make("Transaction Information")
                        ->columnSpan(1)
                        ->schema([
                            Placeholder::make("reference_no")
                                ->label("Reference No.")
                                ->content($this->record->reference_no),

                            Placeholder::make("borrower")
                                ->label("Borrower")
                                ->content(
                                    new HtmlString(
                                        '<div class="font-medium">' .
                                            $this->record->user->name .
                                            "</div>" .
                                            '<div class="text-sm text-gray-500">' .
                                            $this->record->user->email .
                                            "</div>",
                                    ),
                                ),

                            Placeholder::make("membership_type")
                                ->label("Membership Type")
                                ->content(
                                    $this->record->user->membershipType
                                        ?->name ?? "None",
                                ),

                            Placeholder::make("borrowed_date")
                                ->label("Borrowed Date")
                                ->content(
                                    $this->record->borrowed_date->format(
                                        "M d, Y",
                                    ),
                                ),

                            Placeholder::make("due_date")
                                ->label("Due Date")
                                ->content(
                                    new HtmlString(
                                        '<div class="' .
                                            ($this->record->isOverdue()
                                                ? "text-danger-600 font-semibold"
                                                : "") .
                                            '">' .
                                            $this->record->due_date->format(
                                                "M d, Y",
                                            ) .
                                            "</div>" .
                                            ($this->record->isOverdue()
                                                ? '<div class="text-sm text-danger-500">Overdue by ' .
                                                    $this->record->getDaysOverdue() .
                                                    " day(s)</div>"
                                                : ""),
                                    ),
                                ),

                            Placeholder::make("renewed_count")
                                ->label("Times Renewed")
                                ->content($this->record->renewed_count),
                        ]),

                    // Middle & Right Columns - Return Form
                    Section::make("Return Details")
                        ->columnSpan(2)
                        ->schema([
                            DatePicker::make("returned_date")
                                ->label("Return Date")
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->live()
                                ->helperText(
                                    "Select the date when books were/are being returned. You can select past dates for delayed returns.",
                                ),

                            Repeater::make("items")
                                ->label("Returned Books")
                                ->live()
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make("book_title")
                                            ->label("Book")
                                            ->disabled()
                                            ->columnSpan(1),

                                        Toggle::make("is_lost")
                                            ->label("Mark as Lost")
                                            ->inline(false)
                                            ->live()
                                            ->helperText(
                                                "Book was not returned",
                                            ),

                                        Toggle::make("is_damaged")
                                            ->label("Mark as Damaged")
                                            ->inline(false)
                                            ->live()
                                            ->helperText(
                                                "Book returned with damage",
                                            ),
                                    ]),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make("damage_fine")
                                                ->label("Damage Fine Amount")
                                                ->numeric()
                                                ->prefix('$')
                                                ->step(0.01)
                                                ->minValue(0)
                                                ->default(0)
                                                ->live()
                                                ->visible(
                                                    fn(Get $get) => $get(
                                                        "is_damaged",
                                                    ),
                                                ),

                                            Textarea::make("damage_notes")
                                                ->label("Damage Notes")
                                                ->rows(2)
                                                ->visible(
                                                    fn(Get $get) => $get(
                                                        "is_damaged",
                                                    ),
                                                )
                                                ->placeholder(
                                                    "Describe the damage...",
                                                ),
                                        ])
                                        ->visible(
                                            fn(Get $get) => $get("is_damaged"),
                                        ),
                                ])
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false)
                                ->defaultItems(0)
                                ->columnSpanFull(),

                            Placeholder::make("fee_preview")
                                ->label("Fee Calculation Preview")
                                ->content(
                                    fn(Get $get) => $this->renderFeePreview(
                                        $get,
                                    ),
                                )
                                ->columnSpanFull(),
                        ]),
                ]),
            ])
            ->statePath("data");
    }

    public function processReturn(): void
    {
        $data = $this->form->getState();

        try {
            $transactionService = app(TransactionService::class);

            // Prepare return data
            $returnData = [
                "returned_date" => Carbon::parse($data["returned_date"]),
                "lost_items" => [],
                "damaged_items" => [],
            ];

            // Process each item
            foreach ($data["items"] as $itemData) {
                if ($itemData["is_lost"]) {
                    $returnData["lost_items"][] = $itemData["id"];
                }

                if ($itemData["is_damaged"]) {
                    $returnData["damaged_items"][$itemData["id"]] = [
                        "fine" => (float) ($itemData["damage_fine"] ?? 0), // In dollars - MoneyCast converts to cents
                        "notes" => $itemData["damage_notes"] ?? null,
                    ];
                }
            }

            // Process the return
            $transaction = $transactionService->returnTransaction(
                $this->record,
                $returnData,
            );

            // Get fee summary
            $fees = $transactionService->getActualFees($transaction);

            // Show success notification
            $feeMessage =
                $fees["total_all_fees"] > 0
                    ? "Total fees: {$fees["formatted_total_all"]}"
                    : "No fees";

            Notification::make()
                ->success()
                ->title("Transaction Returned Successfully")
                ->body(
                    "Status: {$transaction->status->getLabel()} | {$feeMessage}",
                )
                ->send();

            // Redirect to view page or list
            $this->redirect(TransactionResource::getUrl("index"));
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title("Error Processing Return")
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    protected function renderFeePreview(
        Get $get,
    ): \Illuminate\Support\HtmlString {
        \Log::info("=== FEE PREVIEW RENDER CALLED ===");

        // Read values using $get to establish reactive dependencies
        $returnDate = $get("returned_date");
        $itemsData = $get("items") ?? [];

        \Log::info("Return Date from \$get: " . ($returnDate ?? "null"));
        \Log::info("Items data from \$get: " . json_encode($itemsData));

        if (!$returnDate) {
            $returnDate = now();
        } else {
            $returnDate = \Illuminate\Support\Carbon::parse($returnDate);
        }

        $feeCalculator = app(\App\Services\FeeCalculator::class);

        $preview = [
            "items" => [],
            "total_overdue" => 0,
            "total_lost" => 0,
            "total_damage" => 0,
            "total_all" => 0,
        ];

        foreach ($this->record->items as $index => $item) {
            // Find the matching item data by item ID, not by array index
            $itemData = collect($itemsData)->firstWhere("id", $item->id) ?? [];

            \Log::info(
                "Item {$index} (ID: {$item->id}): " . $item->book->title,
            );
            \Log::info(
                "  - is_lost from form: " .
                    json_encode($itemData["is_lost"] ?? false),
            );
            \Log::info(
                "  - is_damaged from form: " .
                    json_encode($itemData["is_damaged"] ?? false),
            );

            // Calculate overdue fee (returns dollars, MoneyCast handles storage)
            $overdueFine = $feeCalculator->calculateOverdueFine(
                $item,
                $returnDate,
            );
            \Log::info("  - Overdue Fine: {$overdueFine}");

            // Lost book fee (returns dollars, MoneyCast handles storage)
            $lostFine = 0;
            if (!empty($itemData["is_lost"])) {
                $lostFine = $feeCalculator->calculateLostBookFine($item->book);
                \Log::info("  - LOST BOOK FEE CALCULATED: {$lostFine}");
            } else {
                \Log::info("  - is_lost is empty or false, no lost fee");
            }

            // Damage fee (already in dollars from user input)
            $damageFine = 0;
            if (
                !empty($itemData["is_damaged"]) &&
                !empty($itemData["damage_fine"])
            ) {
                $damageFine = (float) $itemData["damage_fine"];
                \Log::info("  - Damage Fine: {$damageFine}");
            }

            $totalFine = $overdueFine + $lostFine + $damageFine;
            \Log::info("  - Total Fine for this item: {$totalFine}");

            $preview["items"][] = [
                "book_title" => $item->book->title,
                "overdue_fine" => $overdueFine,
                "lost_fine" => $lostFine,
                "damage_fine" => $damageFine,
                "total_fine" => $totalFine,
                "is_lost" => $itemData["is_lost"] ?? false,
                "is_damaged" => $itemData["is_damaged"] ?? false,
            ];

            $preview["total_overdue"] += $overdueFine;
            $preview["total_lost"] += $lostFine;
            $preview["total_damage"] += $damageFine;
            $preview["total_all"] += $totalFine;
        }

        \Log::info("PREVIEW TOTALS:");
        \Log::info("  - Total Overdue: {$preview["total_overdue"]}");
        \Log::info("  - Total Lost: {$preview["total_lost"]}");
        \Log::info("  - Total Damage: {$preview["total_damage"]}");
        \Log::info("  - Grand Total: {$preview["total_all"]}");
        \Log::info("=== END FEE PREVIEW ===");

        // Build HTML
        $html =
            '<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">';
        $html .=
            '<h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Fee Preview</h3>';
        $html .= '<div class="space-y-3">';

        foreach ($preview["items"] as $item) {
            $html .=
                '<div class="border-b border-gray-200 dark:border-gray-700 pb-3 last:border-b-0">';
            $html .= '<div class="flex justify-between items-start mb-2">';
            $html .=
                '<div class="font-medium text-gray-900 dark:text-gray-100">' .
                htmlspecialchars($item["book_title"]) .
                "</div>";

            if ($item["is_lost"]) {
                $html .=
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Lost</span>';
            } elseif ($item["is_damaged"]) {
                $html .=
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Damaged</span>';
            }

            $html .= "</div>";
            $html .= '<div class="space-y-1 text-sm">';

            if ($item["overdue_fine"] > 0) {
                $html .=
                    '<div class="flex justify-between text-gray-600 dark:text-gray-400">';
                $html .= "<span>Overdue Fine:</span>";
                $html .=
                    '<span class="font-medium">' .
                    $feeCalculator->formatFine($item["overdue_fine"]) .
                    "</span>";
                $html .= "</div>";
            }

            if ($item["lost_fine"] > 0) {
                $html .=
                    '<div class="flex justify-between text-gray-600 dark:text-gray-400">';
                $html .= "<span>Lost Book Fine:</span>";
                $html .=
                    '<span class="font-medium text-red-600 dark:text-red-400">' .
                    $feeCalculator->formatFine($item["lost_fine"]) .
                    "</span>";
                $html .= "</div>";
            }

            if ($item["damage_fine"] > 0) {
                $html .=
                    '<div class="flex justify-between text-gray-600 dark:text-gray-400">';
                $html .= "<span>Damage Fine:</span>";
                $html .=
                    '<span class="font-medium text-orange-600 dark:text-orange-400">' .
                    $feeCalculator->formatFine($item["damage_fine"]) .
                    "</span>";
                $html .= "</div>";
            }

            if ($item["total_fine"] > 0) {
                $html .=
                    '<div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100 pt-1 border-t border-gray-200 dark:border-gray-700">';
                $html .= "<span>Item Total:</span>";
                $html .=
                    "<span>" .
                    $feeCalculator->formatFine($item["total_fine"]) .
                    "</span>";
                $html .= "</div>";
            } else {
                $html .=
                    '<div class="text-gray-500 dark:text-gray-400 italic">No fees for this item</div>';
            }

            $html .= "</div>";
            $html .= "</div>";
        }

        $html .= "</div>";

        // Total Summary
        $html .=
            '<div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600">';
        $html .= '<div class="space-y-2">';

        if ($preview["total_overdue"] > 0) {
            $html .=
                '<div class="flex justify-between text-gray-700 dark:text-gray-300">';
            $html .= "<span>Total Overdue Fees:</span>";
            $html .=
                '<span class="font-medium">' .
                $feeCalculator->formatFine($preview["total_overdue"]) .
                "</span>";
            $html .= "</div>";
        }

        if ($preview["total_lost"] > 0) {
            $html .=
                '<div class="flex justify-between text-gray-700 dark:text-gray-300">';
            $html .= "<span>Total Lost Book Fees:</span>";
            $html .=
                '<span class="font-medium text-red-600 dark:text-red-400">' .
                $feeCalculator->formatFine($preview["total_lost"]) .
                "</span>";
            $html .= "</div>";
        }

        if ($preview["total_damage"] > 0) {
            $html .=
                '<div class="flex justify-between text-gray-700 dark:text-gray-300">';
            $html .= "<span>Total Damage Fees:</span>";
            $html .=
                '<span class="font-medium text-orange-600 dark:text-orange-400">' .
                $feeCalculator->formatFine($preview["total_damage"]) .
                "</span>";
            $html .= "</div>";
        }

        $color = $preview["total_all"] > 0 ? "danger" : "success";
        $html .=
            '<div class="flex justify-between text-lg font-bold text-gray-900 dark:text-gray-100">';
        $html .= "<span>Grand Total:</span>";
        $html .=
            '<span class="text-' .
            $color .
            '-600">' .
            $feeCalculator->formatFine($preview["total_all"]) .
            "</span>";
        $html .= "</div>";

        $html .= "</div>";

        // Days overdue warning
        if (
            $this->record->isOverdue() ||
            $returnDate->gt($this->record->due_date)
        ) {
            $daysOverdue = $this->record->due_date
                ->startOfDay()
                ->diffInDays($returnDate->copy()->startOfDay());
            $html .=
                '<div class="mt-3 flex items-center gap-2 text-sm text-orange-600 dark:text-orange-400">';
            $html .=
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .=
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
            $html .= "</svg>";
            $html .= "<span>" . $daysOverdue . " day(s) overdue</span>";
            $html .= "</div>";
        }

        $html .=
            '<div class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic">';
        $html .= "* Preview updates as you change return date and item status";
        $html .= "</div>";

        $html .= "</div>";
        $html .= "</div>";

        return new \Illuminate\Support\HtmlString($html);
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make("process_return")
                ->label("Process Return")
                ->color("success")
                ->icon("heroicon-o-check-circle")
                ->action("processReturn")
                ->requiresConfirmation()
                ->modalHeading("Confirm Return")
                ->modalDescription(
                    "Are you sure you want to process this return? Fees will be calculated and the transaction will be finalized.",
                )
                ->modalSubmitActionLabel("Yes, Process Return"),

            \Filament\Actions\Action::make("cancel")
                ->label("Cancel")
                ->color("gray")
                ->url(TransactionResource::getUrl("index")),
        ];
    }
}
