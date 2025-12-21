<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Http\Traits\NavigationCount;
use App\Models\User;
use App\Models\MembershipType;
use Filament\AvatarProviders\UiAvatarsProvider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UserResource extends Resource
{
    use NavigationCount;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = "heroicon-o-users";

    protected static ?string $navigationGroup = "User Management";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make("name")->required(),
                                TextInput::make("email")
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                TextInput::make("password")
                                    ->password()
                                    ->same("passwordConfirmation")
                                    ->revealable()
                                    ->dehydrateStateUsing(
                                        fn(string $state): string => Hash::make(
                                            $state,
                                        ),
                                    )
                                    ->dehydrated(
                                        fn(?string $state): bool => filled(
                                            $state,
                                        ),
                                    )
                                    ->required(
                                        fn(
                                            string $operation,
                                        ): bool => $operation === "create",
                                    ),
                                TextInput::make("passwordConfirmation")
                                    ->revealable()
                                    ->password()
                                    ->dehydrated(false)
                                    ->required(
                                        fn(
                                            string $operation,
                                        ): bool => $operation === "create",
                                    ),
                                TextInput::make("address"),
                                TextInput::make("phone")->tel(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(["sm" => 2, "md" => 2, "lg" => 2]),

                Group::make()
                    ->schema([
                        Section::make("User Avatar")->schema([
                            FileUpload::make("avatar_url")
                                ->label("")
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->directory("users")
                                ->deleteUploadedFileUsing(function ($file) {
                                    Storage::disk("public")->delete($file);
                                })
                                ->extraAttributes([
                                    "class" => "justify-center",
                                ]),
                            Toggle::make("status"),
                            Select::make("role_id")
                                ->relationship("role", "name")
                                ->native(false)
                                ->required()
                                ->live(),
                        ]),

                        Section::make("Membership")
                            ->schema([
                                Select::make("membership_type_id")
                                    ->relationship(
                                        "membershipType",
                                        "name",
                                        fn($query) => $query->where(
                                            "is_active",
                                            true,
                                        ),
                                    )
                                    ->label("Membership Type")
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        callable $set,
                                        callable $get,
                                    ) {
                                        if ($state) {
                                            $type = MembershipType::find(
                                                $state,
                                            );
                                            if (
                                                $type &&
                                                $type->membership_duration_months
                                            ) {
                                                $start =
                                                    $get(
                                                        "membership_started_at",
                                                    ) ?? now();
                                                $set(
                                                    "membership_expires_at",
                                                    Carbon::parse(
                                                        $start,
                                                    )->addMonths(
                                                        $type->membership_duration_months,
                                                    ),
                                                );
                                            }
                                        }
                                    })
                                    ->helperText(
                                        "Select membership type for this borrower",
                                    ),

                                DatePicker::make("membership_started_at")
                                    ->label("Started")
                                    ->native(false)
                                    ->live()
                                    ->default(now())
                                    ->afterStateUpdated(function (
                                        $state,
                                        callable $set,
                                        callable $get,
                                    ) {
                                        $typeId = $get("membership_type_id");
                                        if ($state && $typeId) {
                                            $type = MembershipType::find(
                                                $typeId,
                                            );
                                            if (
                                                $type &&
                                                $type->membership_duration_months
                                            ) {
                                                $set(
                                                    "membership_expires_at",
                                                    Carbon::parse(
                                                        $state,
                                                    )->addMonths(
                                                        $type->membership_duration_months,
                                                    ),
                                                );
                                            }
                                        }
                                    }),

                                DatePicker::make("membership_expires_at")
                                    ->label("Expires")
                                    ->native(false)
                                    ->after("membership_started_at")
                                    ->helperText(
                                        fn($record) => $record &&
                                        method_exists(
                                            $record,
                                            "membershipExpired",
                                        ) &&
                                        $record->membershipExpired()
                                            ? "⚠️ Expired!"
                                            : null,
                                    ),
                            ])
                            ->visible(fn($get) => (int) $get("role_id") === 3)
                            ->description("Assign membership to this borrower"),
                    ])
                    ->columnSpan(["sm" => 2, "md" => 1, "lg" => 1]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make("avatar_url")->label("Avatar")->circular(),
                TextColumn::make("name")->searchable()->sortable(),
                TextColumn::make("email")->searchable()->copyable(),
                TextColumn::make("role.name")->badge()->sortable(),
                TextColumn::make("membershipType.name")
                    ->label("Membership")
                    ->badge()
                    ->color("success")
                    ->placeholder("No membership")
                    ->sortable()
                    ->toggleable(),
                TextColumn::make("membership_expires_at")
                    ->label("Membership Expires")
                    ->date()
                    ->sortable()
                    ->color(
                        fn($record) => $record &&
                        method_exists($record, "membershipExpired") &&
                        $record->membershipExpired()
                            ? "danger"
                            : "success",
                    )
                    ->placeholder("-")
                    ->toggleable(),
                ToggleColumn::make("status")->label("Active"),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()->before(function ($record) {
                        if ($record->avatar_url) {
                            Storage::disk("public")->delete(
                                $record->avatar_url,
                            );
                        }
                    }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->before(function ($records) {
                        $records->each(function ($record) {
                            if ($record->avatar_url) {
                                Storage::disk("public")->delete(
                                    $record->avatar_url,
                                );
                            }
                        });
                    }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListUsers::route("/"),
            "create" => Pages\CreateUser::route("/create"),
            "edit" => Pages\EditUser::route("/{record}/edit"),
        ];
    }
}
