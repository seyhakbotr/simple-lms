<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make("Invoice Details")
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make("invoice_number")
                            ->label("Invoice Number")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->weight("bold")
                            ->copyable(),

                        Infolists\Components\TextEntry::make("status")
                            ->badge()
                            ->color(
                                fn(string $state): string => match ($state) {
                                    "unpaid" => "danger",
                                    "partially_paid" => "warning",
                                    "paid" => "success",
                                    "waived" => "secondary",
                                    default => "gray",
                                },
                            )
                            ->formatStateUsing(
                                fn(string $state): string => ucfirst(
                                    str_replace("_", " ", $state),
                                ),
                            ),

                        Infolists\Components\TextEntry::make(
                            "transaction.reference_no",
                        )
                            ->label("Transaction")
                            ->url(
                                fn(Invoice $record) => route(
                                    "filament.admin.resources.transactions.view",
                                    ["record" => $record->transaction_id],
                                ),
                            )
                            ->color("primary"),
                    ]),
                ])
                ->columns(1),

            Infolists\Components\Section::make("Borrower Information")
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make(
                            "user.name",
                        )->label("Borrower Name"),

                        Infolists\Components\TextEntry::make(
                            "user.email",
                        )->label("Email"),

                        Infolists\Components\TextEntry::make(
                            "user.membershipType.name",
                        )
                            ->label("Membership Type")
                            ->default("N/A"),
                    ]),
                ])
                ->columns(1),

            Infolists\Components\Section::make("Fee Breakdown")
                ->schema([
                    Infolists\Components\Grid::make(4)->schema([
                        Infolists\Components\TextEntry::make(
                            "formatted_overdue_fee",
                        )
                            ->label("Overdue Fee")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->color("warning"),

                        Infolists\Components\TextEntry::make(
                            "formatted_lost_fee",
                        )
                            ->label("Lost Book Fee")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->color("danger"),

                        Infolists\Components\TextEntry::make(
                            "formatted_damage_fee",
                        )
                            ->label("Damage Fee")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->color("warning"),

                        Infolists\Components\TextEntry::make(
                            "formatted_total_amount",
                        )
                            ->label("Total Amount")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->weight("bold")
                            ->color("danger"),
                    ]),
                ])
                ->columns(1),

            Infolists\Components\Section::make("Payment Information")
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make(
                            "formatted_amount_paid",
                        )
                            ->label("Amount Paid")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->weight("bold")
                            ->color("success"),

                        Infolists\Components\TextEntry::make(
                            "formatted_amount_due",
                        )
                            ->label("Amount Due")
                            ->size(
                                Infolists\Components\TextEntry\TextEntrySize::Large,
                            )
                            ->weight("bold")
                            ->color(
                                fn(Invoice $record) => $record->isOverdue()
                                    ? "danger"
                                    : "warning",
                            ),

                        Infolists\Components\TextEntry::make("paid_at")
                            ->label("Paid At")
                            ->dateTime("M d, Y H:i")
                            ->placeholder("Not paid yet"),
                    ]),
                ])
                ->columns(1),

            Infolists\Components\Section::make("Important Dates")
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make("invoice_date")
                            ->label("Invoice Date")
                            ->date("M d, Y"),

                        Infolists\Components\TextEntry::make("due_date")
                            ->label("Due Date")
                            ->date("M d, Y")
                            ->color(
                                fn(Invoice $record) => $record->isOverdue()
                                    ? "danger"
                                    : null,
                            )
                            ->suffix(
                                fn(Invoice $record) => $record->isOverdue()
                                    ? " (" .
                                        $record->getDaysOverdue() .
                                        " days overdue)"
                                    : "",
                            ),

                        Infolists\Components\TextEntry::make("created_at")
                            ->label("Created At")
                            ->dateTime("M d, Y H:i"),
                    ]),
                ])
                ->columns(1),

            Infolists\Components\Section::make("Transaction Details")
                ->schema([
                    Infolists\Components\RepeatableEntry::make(
                        "transaction.items",
                    )
                        ->label("Books")
                        ->schema([
                            Infolists\Components\TextEntry::make(
                                "book.title",
                            )->label("Title"),
                            Infolists\Components\TextEntry::make(
                                "book.isbn",
                            )->label("ISBN"),
                            Infolists\Components\TextEntry::make("item_status")
                                ->label("Status")
                                ->badge()
                                ->color(
                                    fn(?string $state): string => match (
                                        $state
                                    ) {
                                        "lost" => "danger",
                                        "damaged" => "warning",
                                        default => "success",
                                    },
                                )
                                ->formatStateUsing(
                                    fn(?string $state): string => $state
                                        ? ucfirst($state)
                                        : "Returned",
                                ),
                            Infolists\Components\TextEntry::make("damage_notes")
                                ->label("Damage Notes")
                                ->placeholder("N/A")
                                ->columnSpan(3),
                        ])
                        ->columns(4),
                ])
                ->columns(1)
                ->collapsed(false),

            Infolists\Components\Section::make("Additional Information")
                ->schema([
                    Infolists\Components\TextEntry::make("notes")
                        ->label("Notes")
                        ->placeholder("No notes")
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->collapsed(true),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("record_payment")
                ->label("Record Payment")
                ->icon("heroicon-o-currency-dollar")
                ->color("success")
                ->visible(
                    fn(Invoice $record) => !$record->isPaid() &&
                        $record->status !== "waived",
                )
                ->form([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make("amount")
                            ->label("Payment Amount")
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn() => $this->record->amount_due)
                            ->helperText(
                                fn() => "Amount due: " .
                                    $this->record->formatted_amount_due,
                            ),

                        Forms\Components\Select::make("payment_method")
                            ->label("Payment Method")
                            ->options([
                                "cash" => "Cash",
                                "card" => "Credit/Debit Card",
                                "check" => "Check",
                                "bank_transfer" => "Bank Transfer",
                                "online" => "Online Payment",
                            ])
                            ->required(),
                    ]),

                    Forms\Components\Textarea::make("notes")
                        ->label("Payment Notes")
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    try {
                        $invoiceService = app(InvoiceService::class);
                        $invoiceService->recordPayment(
                            $this->record,
                            $data["amount"],
                            $data["payment_method"],
                            $data["notes"] ?? null,
                        );

                        Notification::make()
                            ->success()
                            ->title("Payment Recorded")
                            ->body(
                                "Payment of $" .
                                    number_format($data["amount"], 2) .
                                    " has been recorded successfully.",
                            )
                            ->send();

                        // Refresh the record
                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title("Error Recording Payment")
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make("waive")
                ->label("Waive Invoice")
                ->icon("heroicon-o-x-circle")
                ->color("warning")
                ->visible(
                    fn(Invoice $record) => !$record->isPaid() &&
                        $record->status !== "waived",
                )
                ->requiresConfirmation()
                ->modalHeading("Waive Invoice")
                ->modalDescription(
                    "Are you sure you want to waive this invoice? This action will set the amount due to zero.",
                )
                ->form([
                    Forms\Components\Textarea::make("reason")
                        ->label("Reason for Waiving")
                        ->required()
                        ->rows(4)
                        ->helperText(
                            "Please provide a detailed reason for waiving this invoice.",
                        ),
                ])
                ->action(function (array $data) {
                    try {
                        $invoiceService = app(InvoiceService::class);
                        $invoiceService->waiveInvoice(
                            $this->record,
                            $data["reason"],
                        );

                        Notification::make()
                            ->success()
                            ->title("Invoice Waived")
                            ->body("The invoice has been waived successfully.")
                            ->send();

                        // Refresh the record
                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title("Error Waiving Invoice")
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make("download_pdf")
                ->label("Download PDF")
                ->icon("heroicon-o-arrow-down-tray")
                ->color("primary")
                ->action(function () {
                    $invoiceService = app(\App\Services\InvoiceService::class);
                    $data = $invoiceService->getInvoiceData($this->record);

                    return response()->streamDownload(function () use ($data) {
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                            "pdf.invoice",
                            [
                                "data" => $data,
                            ],
                        );
                        echo $pdf->output();
                    }, "invoice-{$data["invoice_number"]}.pdf");
                }),

            Actions\Action::make("print")
                ->label("Print")
                ->icon("heroicon-o-printer")
                ->color("secondary")
                ->action(function () {
                    Notification::make()
                        ->info()
                        ->title("Coming Soon")
                        ->body("Print functionality will be available soon.")
                        ->send();
                }),

            Actions\DeleteAction::make()->visible(
                fn() => auth()->user()?->role?->name === "Admin",
            ),
        ];
    }
}
