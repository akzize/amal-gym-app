<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use \BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as DefaultRoleResource;
class RoleResource extends DefaultRoleResource implements HasShieldPermissions
{
    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('resources.navigationGroups.users');
    }
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    // Shield's form plus an Arabic name (tenancy/teams are not used in this app)
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(__('resources.role.name_ar'))
                                    ->maxLength(255),
                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),
                                static::getSelectAllFormComponent(),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 4,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    // Shield's table, showing the Arabic name (technical name as fallback)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn(string $state, Role $record): string => static::label($record))
                    ->description(fn(Role $record): ?string => $record->name_ar ? $record->name : null)
                    ->searchable(['name', 'name_ar']),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            // Our pages keep name_ar out of Shield's permission sync
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    /**
     * Arabic role name, or the technical name made readable (super_admin → Super Admin).
     */
    public static function label(Role $role): string
    {
        return $role->name_ar ?: Str::headline($role->name);
    }

    /**
     * Same as label(), by role name; loads all roles once per request.
     */
    public static function labelFor(string $name): string
    {
        $roles = once(fn() => Role::all()->keyBy('name'));

        return isset($roles[$name]) ? static::label($roles[$name]) : Str::headline($name);
    }
}
