<?php

namespace App\Filament\Resources\Trainees\Pages;

use App\Filament\Resources\Trainees\TraineeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrainee extends EditRecord
{
    protected static string $resource = TraineeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
