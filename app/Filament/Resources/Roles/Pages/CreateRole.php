<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole as ShieldCreateRole;

class CreateRole extends ShieldCreateRole
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Shield treats every key except name/guard_name as a permission (and keeps only those two),
        // so take name_ar out before it runs and put it back afterwards.
        $nameAr = $data['name_ar'] ?? null;
        unset($data['name_ar']);

        return [...parent::mutateFormDataBeforeCreate($data), 'name_ar' => $nameAr];
    }
}
