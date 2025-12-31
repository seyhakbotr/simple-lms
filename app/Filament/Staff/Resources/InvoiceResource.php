<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = "heroicon-o-document-text";

    protected static ?string $navigationGroup = "Finance";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Invoice Information")
                ->schema([
                    Forms\Components\TextInput::make("invoice_number")
                        ->label("Invoice Number")
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn() => Invoice::generateInvoiceNumber()),

                    Forms\Components\Select::make("transaction_id")
                        ->label("Transaction")
                        ->relationship("transaction", "reference_no")
                        ->required(fn ($get) => $get('membership_type_id') === null)
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $transaction = \App\Models\Transaction::find($state);
                            if ($transaction) {
                                $set('user_id', $transaction->user_id);

                                // Calculate overdue fees from transaction items
                                $overdue_fee = $transaction->items->sum('overdue_fine');
                                $set('overdue_fee', $overdue_fee / 100);

                                // update total amount
                                $total_amount = $overdue_fee / 100 + ($get('lost_fee') ?? 0) + ($get('damage_fee') ?? 0);
                                $set('total_amount', $total_amount);
                            }
                        })
                        ->disabled(fn(?Invoice $record) => $record !== null),

                    Forms\Components\Select::make("user_id")
                        ->label("Borrower")
                        ->relationship("user", "name")
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn(?Invoice $record) => $record !== null)
                        ->dehydrated(),
                ])
                ->columns(3),

            Forms\Components\Section::make("Fee Breakdown")
                ->schema([
                    Forms\Components\TextInput::make("overdue_fee")
                        ->label("Overdue Fee")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(fn(?Invoice $record) => $record !== null)
                        ->dehydrated(),

                    Forms\Components\TextInput::make("lost_fee")
                        ->label("Lost Book Fee")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(fn(?Invoice $record) => $record !== null)
                        ->dehydrated(),

                    Forms\Components\TextInput::make("damage_fee")
                        ->label("Damage Fee")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(fn(?Invoice $record) => $record !== null)
                        ->dehydrated(),

                    Forms\Components\TextInput::make("total_amount")
                        ->label("Total Amount")
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->disabled(),
                ])
                ->columns(4),

            Forms\Components\Section::make("Payment Information")
                ->schema([
                    Forms\Components\TextInput::make("amount_paid")
                        ->label("Amount Paid")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(),

                    Forms\Components\TextInput::make("amount_due")
                        ->label("Amount Due")
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make("status")
                        ->label("Status")
                        ->options([
                            "unpaid" => "Unpaid",
                            "partially_paid" => "Partially Paid",
                            "paid" => "Paid",
                            "waived" => "Waived",
                        ])
                        ->required()
                        ->disabled(),
                ])
                ->columns(3),

            Forms\Components\Section::make("Notes")->schema([
                Forms\Components\Textarea::make("notes")
                    ->label("Notes")
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("invoice_number")
                    ->label("Invoice #")
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight("bold"),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereHas('transaction', fn (Builder $query) => $query->where('reference_no', 'like', "%{$search}%"))
                            ->orWhereHas('membershipType', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                    })
                    ->sortable()
                    ->url(fn (Invoice $record): ?string => $record->transaction ? route('filament.staff.resources.transactions.view', ['record' => $record->transaction_id]) : null)
                    ->color(fn (Invoice $record): string => $record->transaction ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Borrower")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("formatted_total_amount")
                    ->label("Total Amount")
                    ->sortable(
                        query: function (
                            Builder $query,
                            string $direction,
                        ): Builder {
                            return $query->orderBy("total_amount", $direction);
                        },
                    )
                    ->weight("bold")
                    ->color("danger"),

                Tables\Columns\TextColumn::make("formatted_amount_paid")
                    ->label("Amount Paid")
                    ->sortable(
                        query: function (
                            Builder $query,
                            string $direction,
                        ): Builder {
                            return $query->orderBy("amount_paid", $direction);
                        },
                    )
                    ->color("success"),

                Tables\Columns\TextColumn::make("formatted_amount_due")
                    ->label("Amount Due")
                    ->sortable(
                        query: function (
                            Builder $query,
                            string $direction,
                        ): Builder {
                            return $query->orderBy("amount_due", $direction);
                        },
                    )
                    ->weight("bold")
                    ->color(
                        fn(Invoice $record) => $record->isOverdue()
                            ? "danger"
                            : "warning",
                    ),

                Tables\Columns\BadgeColumn::make("status")
                    ->label("Status")
                    ->colors([
                        "danger" => "unpaid",
                        "warning" => "partially_paid",
                        "success" => "paid",
                        "secondary" => "waived",
                    ])
                    ->formatStateUsing(
                        fn(string $state): string => ucfirst(
                            str_replace("_", " ", $state),
                        ),
                    ),

                Tables\Columns\TextColumn::make("invoice_date")
                    ->label("Invoice Date")
                    ->date("M d, Y")
                    ->sortable(),

                Tables\Columns\TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date("M d, Y")
                    ->sortable()
                    ->color(
                        fn(Invoice $record) => $record->isOverdue()
                            ? "danger"
                            : null,
                    )
                    ->formatStateUsing(
                        fn(Invoice $record, $state) => $record->isOverdue()
                            ? $state->format("M d, Y") .
                                " (" .
                                $record->getDaysOverdue() .
                                " days overdue)"
                            : $state->format("M d, Y"),
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("status")
                    ->options([
                        "unpaid" => "Unpaid",
                        "partially_paid" => "Partially Paid",
                        "paid" => "Paid",
                        "waived" => "Waived",
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make("overdue")
                    ->label("Overdue")
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->whereIn("status", ["unpaid", "partially_paid"])
                            ->where("due_date", "<", now()),
                    )
                    ->toggle(),

                Tables\Filters\Filter::make("unpaid")
                    ->label("Unpaid Only")
                    ->query(
                        fn(Builder $query): Builder => $query->whereIn(
                            "status",
                            ["unpaid", "partially_paid"],
                        ),
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make("record_payment")
                    ->label("Record Payment")
                    ->icon("heroicon-o-currency-dollar")
                    ->color("success")
                    ->visible(
                        fn(Invoice $record) => !$record->isPaid() &&
                            $record->status !== "waived",
                    )
                    ->form([
                        Forms\Components\TextInput::make("amount")
                            ->label("Payment Amount")
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(
                                fn(Invoice $record) => $record->amount_due,
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

                        Forms\Components\Textarea::make("notes")
                            ->label("Notes")
                            ->rows(2),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        try {
                            $invoiceService = app(InvoiceService::class);
                            $invoiceService->recordPayment(
                                $record,
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
                                        " has been recorded.",
                                )
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title("Error")
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make("download_pdf")
                    ->label("Download PDF")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("success")
                    ->action(function (Invoice $record) {
                        $invoiceService = app(InvoiceService::class);
                        $data = $invoiceService->getInvoiceData($record);

                        return response()->streamDownload(function () use (
                            $data,
                        ) {
                            $pdf = Pdf::loadView("pdf.invoice", [
                                "data" => $data,
                            ]);
                            echo $pdf->output();
                        }, "invoice-{$data["invoice_number"]}.pdf");
                    }),

                Tables\Actions\Action::make("view_pdf")
                    ->label("Preview PDF")
                    ->icon("heroicon-o-eye")
                    ->color("info")
                    ->url(
                        fn(Invoice $record): string => route(
                            "invoices.pdf.preview",
                            $record,
                        ),
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions for staff
                ]),
            ])
            ->defaultSort("invoice_date", "desc");
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
            "index" => Pages\ListInvoices::route("/"),
            "create" => Pages\CreateInvoice::route("/create"),
            "edit" => Pages\EditInvoice::route("/{record}/edit"),
            "view" => Pages\ViewInvoice::route("/{record}"),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()
            ::whereIn("status", ["unpaid", "partially_paid"])
            ->where("due_date", "<", now())
            ->count() ?:
            null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return "danger";
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
