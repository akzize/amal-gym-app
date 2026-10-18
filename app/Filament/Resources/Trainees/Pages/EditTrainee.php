<?php

namespace App\Filament\Resources\Trainees\Pages;

use App\Filament\Resources\Trainees\TraineeResource;
use App\Filament\Actions\Lifecycle;
use Filament\Resources\Pages\EditRecord;

class EditTrainee extends EditRecord
{
    protected static string $resource = TraineeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Lifecycle::deactivateTrainee(),
            Lifecycle::activateTrainee(),
            Lifecycle::deleteIfUnused(),
        ];
    }
}
