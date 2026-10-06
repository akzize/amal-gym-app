<?php

namespace App\Filament\Resources\Trainers\Pages;

use App\Filament\Resources\Trainers\TrainerResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainer extends CreateRecord
{
    protected static string $resource = TrainerResource::class;

    // The login user is created in mutateFormDataBeforeCreate(), so wrap the whole
    // create flow in one transaction to avoid orphan users if the trainer fails to save.
    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);
        // create user first
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['user']['email'],
            'password' => bcrypt($data['user']['password']),
            // 'role' => 'trainer',
        ]);

        // set trainer's user_id
        $data['user_id'] = $user->id;

        // unset unneeded fields
        unset($data['user']);

        return parent::mutateFormDataBeforeCreate($data);
    }
}
