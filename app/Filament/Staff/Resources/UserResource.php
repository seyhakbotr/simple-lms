<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\UserResource\Pages;
use App\Filament\Staff\Resources\UserResource\RelationManagers\MembershipInvoicesRelationManager;
use App\Http\Traits\BorrowerCount;
use App\Models\Role;
use App\Models\User;
use Filament\AvatarProviders\UiAvatarsProvider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use App\Models\MembershipType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserResource extends Resource
{
    use BorrowerCount;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true),
                                        TextInput::make('password')
                                            ->password()
                                            ->same('passwordConfirmation')
                                            ->revealable()
                                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->required(fn (string $operation): bool => $operation === 'create'),
                                        TextInput::make('passwordConfirmation')
                                            ->revealable()
                                            ->password()
                                            ->dehydrated(false)
                                            ->required(fn (string $operation): bool => $operation === 'create'),
                                        TextInput::make('address'),
                                        TextInput::make('phone')
                                            ->tel(),
                                    ])->columns(2),
                            ])->columnSpan(['sm' => 2, 'md' => 2, 'xxl' => 5]),
                        Group::make()
                            ->schema([
                                Section::make('User Avatar')
                                    ->schema([
                                        FileUpload::make('avatar_url')
                                            ->label('')
                                            ->image()
                                            ->imageEditor()
                                            ->avatar()
                                            ->directory('users')
                                            ->deleteUploadedFileUsing(function ($file) {
                                                Storage::disk('public')->delete($file);
                                            })
                                            ->extraAttributes([
                                                'class' => 'justify-center',
                                            ]),
                                        Toggle::make('status'),
                                        Hidden::make('role_id')
                                            ->default(Role::whereName('borrower')
                                                ->value('id')),
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
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $membershipType = \App\Models\MembershipType::find($state);
                                                if ($membershipType && $get('membership_started_at')) {
                                                    $set('membership_expires_at', Carbon::parse($get('membership_started_at'))->addMonths($membershipType->membership_duration_months));
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
                                            ->dehydrated(true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $membershipType = \App\Models\MembershipType::find($get('membership_type_id'));
                                                if ($membershipType && $state) {
                                                    $set('membership_expires_at', Carbon::parse($state)->addMonths($membershipType->membership_duration_months));
                                                }
                                            }),

                                        DatePicker::make("membership_expires_at")
                                            ->label("Expires")
                                            ->native(false)
                                            ->readOnly()
                                            ->dehydrated(true),
                                    ])
                                    ->visible(fn($get) => (int) $get("role_id") === 3)
                                    ->description("Assign membership to this borrower"),
                            ])->columnSpan(['sm' => 2, 'md' => 1, 'xxl' => 1]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->defaultImageUrl(fn ($record) => $record->avatar_url
                        ?: (new UiAvatarsProvider())->get($record))
                    ->circular(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email'),
                TextColumn::make('role.name')
                    ->badge(),
                ToggleColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->before(function ($record) {
                            if (! is_null($record->avatar_url)) {
                                Storage::disk('public')->delete($record->avatar_url);
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($records) {
                            $records->each(function ($record) {
                                if (! is_null($record->avatar_url)) {
                                    Storage::disk('public')->delete($record->avatar_url);
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MembershipInvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('role')->whereRelation('role', 'name', 'borrower');
    }
}
