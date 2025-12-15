<?php

namespace App\Filament\Admin\Resources\StockTransactionResource\Pages;

use App\Enums\StockAdjustmentType;
use App\Filament\Admin\Resources\StockTransactionResource;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransaction extends ViewRecord
{
    protected static string $resource = StockTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("back")
                ->label("Back to List")
                ->url($this->getResource()::getUrl("index"))
                ->color("gray"),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make("Adjustment Information")
                ->schema([
                    TextEntry::make("reference_number")
                        ->label("Reference Number")
                        ->copyable()
                        ->weight("bold"),

                    TextEntry::make("type")->label("Adjustment Type")->badge(),

                    TextEntry::make("user.name")->label("Adjusted By"),

                    TextEntry::make("created_at")
                        ->label("Date & Time")
                        ->dateTime("M d, Y h:i A"),

                    TextEntry::make("donator_name")
                        ->label("Donator Name")
                        ->visible(
                            fn($record) => $record &&
                                $record->type === StockAdjustmentType::Donation,
                        )
                        ->placeholder("Not specified"),

                    TextEntry::make("notes")
                        ->label("Notes")
                        ->columnSpanFull()
                        ->placeholder("No notes provided")
                        ->default("No notes provided"),
                ])
                ->columns(2),

            InfolistSection::make("Books Adjusted")->schema([
                RepeatableEntry::make("items")
                    ->label("")
                    ->schema([
                        TextEntry::make("book.title")
                            ->label("Book Title")
                            ->weight("semibold"),

                        TextEntry::make("book.isbn")
                            ->label("ISBN")
                            ->copyable()
                            ->icon("heroicon-o-qr-code"),

                        TextEntry::make("book.author.name")->label("Author"),

                        TextEntry::make("quantity")
                            ->label("Quantity")
                            ->formatStateUsing(function ($record, $state) {
                                $type = $record->stockTransaction->type;
                                $sign = in_array($type, [
                                    StockAdjustmentType::Purchase,
                                    StockAdjustmentType::Donation,
                                ])
                                    ? "+"
                                    : "-";
                                if ($type === StockAdjustmentType::Correction) {
                                    $sign = "";
                                }
                                return $sign . $state;
                            })
                            ->badge()
                            ->color(
                                fn($record) => in_array(
                                    $record->stockTransaction->type,
                                    [
                                        StockAdjustmentType::Purchase,
                                        StockAdjustmentType::Donation,
                                    ],
                                )
                                    ? "success"
                                    : "danger",
                            ),

                        TextEntry::make("old_stock")->label("Old Stock"),

                        TextEntry::make("new_stock")
                            ->label("New Stock")
                            ->weight("bold")
                            ->color("primary"),
                    ])
                    ->columns(6)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
