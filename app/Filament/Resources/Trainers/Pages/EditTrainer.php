<?php

namespace App\Filament\Resources\Trainers\Pages;

use App\Filament\Resources\Trainers\TrainerResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrainer extends EditRecord
{
    protected static string $resource = TrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Trainer has a user relationship
        if ($this->record && $this->record->user) {
            $data['user']['email'] = $this->record->user->email;
            $data['user']['password'] = ''; // leave empty
        }
        return parent::mutateFormDataBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If login being edited, edit the user 
        if ($data['edit_login']) {
            $user = $data['user'];
            $this->record->user->update([
                'name' => $data['name'],
                'email' => $user['email'],
                'password' => bcrypt($user['password']),
            ]);
        }
        
        // Always remove the toggle and user from data before saving
        unset($data['user']);
        unset($data['edit_login']);

        return $data;
    }
}
