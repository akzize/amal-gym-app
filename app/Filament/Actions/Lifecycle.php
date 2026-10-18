<?php

namespace App\Filament\Actions;

use App\Models\Group;
use App\Models\Trainee;
use App\Models\Trainer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trainees, trainers and groups are deactivated (or closed) instead of deleted, so their payments,
 * payouts and memberships stay. Delete is only offered for records with no history.
 * Shared by the list tables and the edit pages.
 */
class Lifecycle
{
    /**
     * Delete, shown only when the record has no history (the model's canBeDeleted()).
     */
    public static function deleteIfUnused(): DeleteAction
    {
        return DeleteAction::make()
            ->visible(fn(Model $record) => $record->canBeDeleted());
    }

    public static function deactivateTrainee(): Action
    {
        return Action::make('deactivate')
            ->label(__('resources.lifecycle.deactivate'))
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('resources.lifecycle.deactivate_trainee_help'))
            ->visible(fn(Trainee $record) => $record->is_active)
            ->action(fn(Trainee $record) => $record->update(['is_active' => false]))
            ->successNotificationTitle(__('resources.lifecycle.deactivated'));
    }

    public static function activateTrainee(): Action
    {
        return Action::make('activate')
            ->label(__('resources.lifecycle.activate'))
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->visible(fn(Trainee $record) => ! $record->is_active)
            ->action(fn(Trainee $record) => $record->update(['is_active' => true]))
            ->successNotificationTitle(__('resources.lifecycle.activated'));
    }

    public static function deactivateTrainees(): BulkAction
    {
        return BulkAction::make('deactivate')
            ->label(__('resources.lifecycle.deactivate'))
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('resources.lifecycle.deactivate_trainee_help'))
            ->action(fn(Collection $records) => Trainee::whereKey($records->modelKeys())->update(['is_active' => false]))
            ->deselectRecordsAfterCompletion()
            ->successNotificationTitle(__('resources.lifecycle.deactivated'));
    }

    public static function deactivateTrainer(): Action
    {
        return Action::make('deactivate')
            ->label(__('resources.lifecycle.deactivate'))
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('resources.lifecycle.deactivate_trainer_help'))
            ->visible(fn(Trainer $record) => $record->isActive())
            ->action(fn(Trainer $record) => $record->update(['status' => Trainer::STATUS_INACTIVE]))
            ->successNotificationTitle(__('resources.lifecycle.deactivated'));
    }

    public static function activateTrainer(): Action
    {
        return Action::make('activate')
            ->label(__('resources.lifecycle.activate'))
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->visible(fn(Trainer $record) => ! $record->isActive())
            ->action(fn(Trainer $record) => $record->update(['status' => Trainer::STATUS_ACTIVE]))
            ->successNotificationTitle(__('resources.lifecycle.activated'));
    }

    public static function closeGroup(): Action
    {
        return Action::make('close')
            ->label(__('resources.lifecycle.close_group'))
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->modalDescription(__('resources.lifecycle.close_group_help'))
            ->schema([
                DatePicker::make('closed_at')
                    ->label(__('resources.lifecycle.closed_at'))
                    ->default(now())
                    ->required(),
            ])
            ->visible(fn(Group $record) => ! $record->isClosed())
            ->action(fn(Group $record, array $data) => $record->update(['closed_at' => $data['closed_at']]))
            ->successNotificationTitle(__('resources.lifecycle.group_closed'));
    }

    public static function reopenGroup(): Action
    {
        return Action::make('reopen')
            ->label(__('resources.lifecycle.reopen_group'))
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible(fn(Group $record) => $record->isClosed())
            ->action(fn(Group $record) => $record->update(['closed_at' => null]))
            ->successNotificationTitle(__('resources.lifecycle.group_reopened'));
    }
}
