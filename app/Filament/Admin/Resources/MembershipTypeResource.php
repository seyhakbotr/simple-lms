<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MembershipTypeResource\Pages;
use App\Models\MembershipType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MembershipTypeResource extends Resource
{
    protected static ?string $model = MembershipType::class;

    protected static ?string $navigationIcon = "heroicon-o-identification";

    protected static ?string $navigationGroup = "User Management";

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = "Membership Types";

    protected static ?string $modelLabel = "Membership Type";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Basic Information")
                ->schema([
                    Forms\Components\TextInput::make("name")
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->placeholder("e.g., Premium, Student, Faculty"),

                    Forms\Components\Textarea::make("description")
                        ->rows(3)
                        ->placeholder("Describe this membership type...")
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make("is_active")
                        ->label("Active")
                        ->default(true)
                        ->helperText(
                            "Inactive types cannot be assigned to new members",
                        ),
                ])
                ->columns(2),

            Forms\Components\Section::make("Borrowing Privileges")
                ->schema([
                    Forms\Components\TextInput::make("max_books_allowed")
                        ->label("Max Books Allowed")
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(3)
                        ->suffix("books")
                        ->helperText(
                            "Maximum books a member can borrow at once",
                        ),

                    Forms\Components\TextInput::make("max_borrow_days")
                        ->label("Loan Period")
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(14)
                        ->suffix("days")
                        ->helperText("Default number of days for borrowing"),

                    Forms\Components\TextInput::make("renewal_limit")
                        ->label("Renewal Limit")
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(20)
                        ->default(2)
                        ->suffix("times")
                        ->helperText(
                            "How many times a transaction can be renewed",
                        ),
                ])
                ->columns(3),

            Forms\Components\Section::make("Financial")
                ->schema([
                    Forms\Components\TextInput::make("fine_rate")
                        ->label("Fine Rate")
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(10.0)
                        ->prefix('$')
                        ->suffix("per day")
                        ->helperText("Fine charged per day for overdue books"),

                    Forms\Components\TextInput::make("membership_fee")
                        ->label("Membership Fee")
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(0.0)
                        ->prefix('$')
                        ->helperText("Registration or annual fee"),

                    Forms\Components\TextInput::make(
                        "membership_duration_months",
                    )
                        ->label("Membership Duration")
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(1200)
                        ->default(12)
                        ->suffix("months")
                        ->helperText("How long the membership is valid"),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable()
                    ->weight("bold")
                    ->icon("heroicon-m-identification")
                    ->iconColor("primary"),

                Tables\Columns\TextColumn::make("max_books_allowed")
                    ->label("Max Books")
                    ->badge()
                    ->color("info")
                    ->suffix(" books")
                    ->sortable(),

                Tables\Columns\TextColumn::make("max_borrow_days")
                    ->label("Loan Period")
                    ->badge()
                    ->color("success")
                    ->suffix(" days")
                    ->sortable(),

                Tables\Columns\TextColumn::make("renewal_limit")
                    ->label("Renewals")
                    ->badge()
                    ->color("warning")
                    ->suffix(" times")
                    ->sortable(),

                Tables\Columns\TextColumn::make("fine_rate")
                    ->label("Fine Rate")
                    ->money("usd")
                    ->suffix("/day")
                    ->sortable(),

                Tables\Columns\TextColumn::make("membership_fee")
                    ->label("Fee")
                    ->money("usd")
                    ->sortable(),

                Tables\Columns\TextColumn::make("membership_duration_months")
                    ->label("Duration")
                    ->suffix(" months")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make("is_active")
                    ->label("Active")
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make("users_count")
                    ->counts("users")
                    ->label("Members")
                    ->badge()
                    ->color("primary")
                    ->sortable(),

                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make("is_active")
                    ->label("Active Status")
                    ->placeholder("All types")
                    ->trueLabel("Active only")
                    ->falseLabel("Inactive only"),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort("name");
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
            "index" => Pages\ListMembershipTypes::route("/"),
            "create" => Pages\CreateMembershipType::route("/create"),
            "edit" => Pages\EditMembershipType::route("/{record}/edit"),
        ];
    }
}
