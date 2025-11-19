<?php

namespace App\Filament\Resources\Trainers\Pages;

use App\Filament\Resources\Trainers\TrainerResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainer extends CreateRecord
{
    protected static string $resource = TrainerResource::class;

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
