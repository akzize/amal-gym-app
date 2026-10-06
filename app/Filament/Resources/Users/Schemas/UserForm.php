<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.user.account_info'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('resources.user.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label(__('resources.user.name_ar'))
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('resources.user.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            // Also counts deleted users, whose email stays reserved until restored
                            ->unique(ignoreRecord: true),
                        // A user has a single role; spatie stores it in a many-to-many table
                        Select::make('role')
                            ->label(__('resources.user.role'))
                            // Arabic role name (set on the roles page), technical name as fallback
                            ->options(fn(): array => Role::query()->orderBy('name')->get()
                                ->mapWithKeys(fn(Role $role): array => [$role->name => RoleResource::label($role)])
                                ->all())
                            ->required()
                            ->native(false)
                            ->dehydrated(false)
                            ->loadStateFromRelationshipsUsing(fn(Select $component, ?User $record) => $component->state($record?->roles->first()?->name))
                            ->saveRelationshipsUsing(fn(User $record, ?string $state) => $record->syncRoles($state ? [$state] : []))
                            // Changing your own role could lock you out of the panel
                            ->disabled(fn(?User $record): bool => $record?->is(auth()->user()) ?? false)
                            ->helperText(fn(?User $record): ?string => $record?->is(auth()->user()) ? __('resources.user.own_role_hint') : null),
                    ]),

                Section::make(__('resources.user.password'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->label(fn(string $operation): string => $operation === 'create' ? __('resources.user.password') : __('resources.user.new_password'))
                            ->password()
                            ->revealable()
                            // Copy it on creation to hand it over to the new user
                            ->copyable(fn(string $operation): bool => $operation === 'create')
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            // Left blank on edit = keep the current password (the model casts it to a hash)
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->helperText(fn(string $operation): ?string => $operation === 'edit' ? __('resources.user.keep_password_hint') : null)
                            ->live(onBlur: true),
                        TextInput::make('password_confirmation')
                            ->label(__('resources.user.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required(fn(Get $get): bool => filled($get('password')))
                            ->same('password')
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
