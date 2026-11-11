<?php

namespace App\Filament\Resources\Trainees\Tables;

use App\Filament\Actions\Lifecycle;
use App\Models\Trainee;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Torgodly\Html2Media\Actions\Html2MediaAction;

use function Livewire\after;

class TraineesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('groups.sport')->withInsuranceEnd())
            ->columns([
                ImageColumn::make('photo_url')
                    ->label(__('resources.trainee.avatar'))
                    ->circular()
                    ->defaultImageUrl(url('storage/trainees/default.png')),
                TextColumn::make('full_name')
                    ->label(__('resources.trainee.full_name'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('full_arabic_name')
                    ->label(__('resources.trainee.full_arabic_name'))
                    ->searchable(),
                TextColumn::make('groups.name')
                    ->label(__('resources.group.pluralModelLabel'))
                    ->badge()
                    ->placeholder('—'),
                // the sports of the trainee's groups, each listed once
                TextColumn::make('sports')
                    ->label(__('resources.sport.name_plural'))
                    ->state(fn(Trainee $record): array => $record->groups
                        ->map(fn($group) => App::getLocale() == 'ar' ? ($group->sport?->name_ar ?: $group->sport?->name) : $group->sport?->name)
                        ->filter()->unique()->values()->all())
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                // end of the latest insurance: red once it has passed, orange when it ends soon
                TextColumn::make('insurance_ends_at')
                    ->label(__('resources.trainee.insurance.until'))
                    ->formatStateUsing(fn(string $state, Trainee $record): string => Carbon::parse($state)->format('d/m/Y')
                        . (in_array($record->insuranceStatus(), [Trainee::INSURANCE_EXPIRED, Trainee::INSURANCE_EXPIRING])
                            ? ' · ' . __('resources.trainee.insurance.' . $record->insuranceStatus())
                            : ''))
                    ->badge()
                    ->color(fn(Trainee $record): string => match ($record->insuranceStatus()) {
                        Trainee::INSURANCE_EXPIRED => 'danger',
                        Trainee::INSURANCE_EXPIRING => 'warning',
                        default => 'success',
                    })
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('dob')
                    ->label(__('resources.trainee.dob'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('resources.trainee.phone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('resources.lifecycle.status'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Deactivated trainees are hidden by default, the filter shows them again
                TernaryFilter::make('is_active')
                    ->label(__('resources.lifecycle.status'))
                    ->trueLabel(__('resources.lifecycle.active'))
                    ->falseLabel(__('resources.lifecycle.inactive'))
                    ->placeholder(__('resources.lifecycle.all'))
                    ->default(true),
                SelectFilter::make('groups')
                    ->label(__('resources.group.label'))
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload(),
                // Same rules as the dashboard's insurance and subscription cards, which link here
                SelectFilter::make('insurance')
                    ->label(__('resources.trainee.insurance.label'))
                    ->multiple()
                    ->options([
                        Trainee::INSURANCE_EXPIRED => __('resources.trainee.insurance.expired'),
                        Trainee::INSURANCE_EXPIRING => __('resources.trainee.insurance.expiring'),
                        Trainee::INSURANCE_NONE => __('resources.trainee.insurance.none'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        filled($data['values'] ?? null),
                        fn(Builder $query) => $query->insuranceIn($data['values']),
                    )),
                Filter::make('subscription_ending_soon')
                    ->label(__('resources.dashboard.expiring_subscriptions'))
                    ->query(fn(Builder $query): Builder => $query->subscriptionEndingSoon()),
                // Same rule as the dashboard's "unpaid this month" card, which links here
                Filter::make('unpaid_this_month')
                    ->label(__('resources.dashboard.unpaid_this_month'))
                    ->query(fn(Builder $query): Builder => $query->unpaidForMonth(now())),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->iconSize(IconSize::Medium),
                Lifecycle::deactivateTrainee()
                    ->iconButton()
                    ->iconSize(IconSize::Medium),
                Lifecycle::activateTrainee()
                    ->iconButton()
                    ->iconSize(IconSize::Medium),
                Lifecycle::deleteIfUnused()
                    ->label('')
                    ->iconSize(IconSize::Medium),
                Html2MediaAction::make('print')
                    ->content(fn($record) => view('cards.trainee', ['trainees' => collect([$record])]))
                    ->savePdf()
                    // ->print()
                    ->format('a4', 'mm') // A4 format with mm units
                    ->enableLinks() // Enable links in PDF
                    ->filename(filename: 'my-custom-document')
                    ->icon('heroicon-o-identification')
                    ->label('')
                    ->iconSize(IconSize::Medium),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Lifecycle::deactivateTrainees(),
                    Html2MediaAction::make('export')
                        ->label(__('resources.trainee.actions.export_selected_trainees'))
                        ->accessSelectedRecords()
                        ->content(fn(Collection $records) => view('cards.trainee', ['trainees' => $records]))
                        ->savePdf()
                        ->format('a4', 'mm') // A4 format with mm units
                        ->margins(0, 0, 0, 0)
                        ->filename(filename: 'trainees-export')
                        ->icon('heroicon-o-identification')
                        ->iconSize(IconSize::Medium),
                ]),
            ]);
    }
}
