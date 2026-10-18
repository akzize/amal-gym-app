<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Filament\Actions\Lifecycle;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Lifecycle::closeGroup(),
            Lifecycle::reopenGroup(),
            Lifecycle::deleteIfUnused(),
        ];
    }
}
