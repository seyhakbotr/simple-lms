<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = "heroicon-o-shield-check";

    protected static ?string $navigationGroup = "User Management";

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = "Roles";

    protected static ?string $modelLabel = "Role";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Role Information")->schema([
                Forms\Components\TextInput::make("name")
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder("e.g., admin, staff, borrower"),

                Forms\Components\Textarea::make("description")
                    ->rows(3)
                    ->placeholder("Describe this role...")
                    ->columnSpanFull(),
            ]),
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
                    ->icon("heroicon-m-shield-check")
                    ->iconColor("primary"),

                Tables\Columns\TextColumn::make("description")
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make("users_count")
                    ->counts("users")
                    ->label("Users")
                    ->badge()
                    ->color("success")
                    ->sortable(),

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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription(
                        "Are you sure you want to delete this role? This may affect users assigned to this role.",
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->defaultSort("id");
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
            "index" => Pages\ListRoles::route("/"),
            "create" => Pages\CreateRole::route("/create"),
            "edit" => Pages\EditRole::route("/{record}/edit"),
        ];
    }
}
