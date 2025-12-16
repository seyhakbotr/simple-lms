<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn(?Invoice $record) => $record !== null),

                    Forms\Components\Select::make("user_id")
                        ->label("Borrower")
                        ->relationship("user", "name")
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn(?Invoice $record) => $record !== null),
                ])
                ->columns(3),

            Forms\Components\Section::make("Fee Breakdown")
                ->schema([
                    Forms\Components\TextInput::make("overdue_fee")
                        ->label("Overdue Fee")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(fn(?Invoice $record) => $record !== null),

                    Forms\Components\TextInput::make("lost_fee")
                        ->label("Lost Book Fee")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(fn(?Invoice $record) => $record !== null),

                    Forms\Components\TextInput::make("damage_fee")
                        ->label("Damage Fee")
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->disabled(fn(?Invoice $record) => $record !== null),

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

            Forms\Components\Section::make("Dates")
                ->schema([
                    Forms\Components\DatePicker::make("invoice_date")
                        ->label("Invoice Date")
                        ->required()
                        ->default(now())
                        ->disabled(fn(?Invoice $record) => $record !== null),

                    Forms\Components\DatePicker::make("due_date")
                        ->label("Due Date")
                        ->required()
                        ->default(now()->addDays(30))
                        ->disabled(fn(?Invoice $record) => $record !== null),

                    Forms\Components\DateTimePicker::make("paid_at")
                        ->label("Paid At")
                        ->disabled(),
                ])
                ->columns(3),

            Forms\Components\Section::make("Notes")->schema([
                Forms\Components\Textarea::make("notes")
                    ->label("Additional Notes")
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

                Tables\Columns\TextColumn::make("transaction.reference_no")
                    ->label("Transaction")
                    ->searchable()
                    ->sortable()
                    ->url(
                        fn(Invoice $record) => route(
                            "filament.admin.resources.transactions.view",
                            ["record" => $record->transaction_id],
                        ),
                    )
                    ->color("primary"),

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

                Tables\Columns\TextColumn::make("paid_at")
                    ->label("Paid At")
                    ->dateTime("M d, Y H:i")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make("updated_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\Filter::make("invoice_date")
                    ->form([
                        Forms\Components\DatePicker::make("from")->label(
                            "From Date",
                        ),
                        Forms\Components\DatePicker::make("until")->label(
                            "Until Date",
                        ),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data["from"],
                                fn(
                                    Builder $query,
                                    $date,
                                ): Builder => $query->whereDate(
                                    "invoice_date",
                                    ">=",
                                    $date,
                                ),
                            )
                            ->when(
                                $data["until"],
                                fn(
                                    Builder $query,
                                    $date,
                                ): Builder => $query->whereDate(
                                    "invoice_date",
                                    "<=",
                                    $date,
                                ),
                            );
                    }),
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

                Tables\Actions\Action::make("waive")
                    ->label("Waive Invoice")
                    ->icon("heroicon-o-x-circle")
                    ->color("warning")
                    ->visible(
                        fn(Invoice $record) => !$record->isPaid() &&
                            $record->status !== "waived",
                    )
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make("reason")
                            ->label("Reason for Waiving")
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        try {
                            $invoiceService = app(InvoiceService::class);
                            $invoiceService->waiveInvoice(
                                $record,
                                $data["reason"],
                            );

                            Notification::make()
                                ->success()
                                ->title("Invoice Waived")
                                ->body("The invoice has been waived.")
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
                    ->color("primary")
                    ->action(function (Invoice $record) {
                        Notification::make()
                            ->info()
                            ->title("Coming Soon")
                            ->body("PDF generation will be available soon.")
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(
                        fn() => auth()->user()?->role?->name === "Admin",
                    ),
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
            "view" => Pages\ViewInvoice::route("/{record}"),
            "edit" => Pages\EditInvoice::route("/{record}/edit"),
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
}
