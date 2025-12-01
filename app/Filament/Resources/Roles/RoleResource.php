<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
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

    // public static function form(Schema $schema): Schema
    // {
    //     return RoleForm::configure($schema);
    // }

    // public static function table(Table $table): Table
    // {
    //     return RolesTable::configure($table);
    // }

    // public static function getRelations(): array
    // {
    //     return [
    //         //
    //     ];
    // }

    // public static function getPages(): array
    // {
    //     return [
    //         'index' => ListRoles::route('/'),
    //         'create' => CreateRole::route('/create'),
    //         'edit' => EditRole::route('/{record}/edit'),
    //     ];
    // }
}
