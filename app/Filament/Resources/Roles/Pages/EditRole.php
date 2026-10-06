<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as ShieldEditRole;

class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Shield treats every key except name/guard_name as a permission (and keeps only those two),
        // so take name_ar out before it runs and put it back afterwards.
        $nameAr = $data['name_ar'] ?? null;
        unset($data['name_ar']);

        return [...parent::mutateFormDataBeforeSave($data), 'name_ar' => $nameAr];
    }
}
