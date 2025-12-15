<?php

namespace App\Filament\Admin\Resources\BookResource\RelationManagers;

use App\Enums\StockAdjustmentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = "stockTransactionItems";

    protected static ?string $title = "Stock History";

    protected static ?string $recordTitleAttribute = "id";

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make("stockTransaction.type")
                ->label("Adjustment Type")
                ->disabled(),

            Forms\Components\TextInput::make("quantity")->numeric()->disabled(),

            Forms\Components\TextInput::make("old_stock")
                ->label("Old Stock")
                ->numeric()
                ->disabled(),

            Forms\Components\TextInput::make("new_stock")
                ->label("New Stock")
                ->numeric()
                ->disabled(),

            Forms\Components\Textarea::make("stockTransaction.notes")
                ->label("Notes")
                ->disabled()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute("id")
            ->columns([
                Tables\Columns\TextColumn::make(
                    "stockTransaction.reference_number",
                )
                    ->label("Reference #")
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight("semibold"),

                Tables\Columns\TextColumn::make("stockTransaction.type")
                    ->label("Type")
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make("quantity")
                    ->label("Quantity")
                    ->alignCenter()
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->stockTransaction) {
                            return $record->quantity ?? 0;
                        }
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
                        return $sign . $record->quantity;
                    })
                    ->color(
                        fn($record) => !$record || !$record->stockTransaction
                            ? "gray"
                            : (in_array($record->stockTransaction->type, [
                                StockAdjustmentType::Purchase,
                                StockAdjustmentType::Donation,
                            ])
                                ? "success"
                                : "danger"),
                    ),

                Tables\Columns\TextColumn::make("old_stock")
                    ->label("Old Stock")
                    ->alignCenter(),

                Tables\Columns\TextColumn::make("new_stock")
                    ->label("New Stock")
                    ->alignCenter()
                    ->weight("bold"),

                Tables\Columns\TextColumn::make("stockTransaction.user.name")
                    ->label("By")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Date")
                    ->dateTime("M d, Y h:i A")
                    ->sortable(),
            ])
            ->defaultSort("created_at", "desc")
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([
                //
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
