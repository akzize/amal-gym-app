<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\InsuranceExport;
use App\Models\Payment;
use App\Support\InsuranceRoster;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->insuranceExportAction(),
            CreateAction::make(),
        ];
    }

    /**
     * Send the insured trainees to the insurer: a new export takes every paid insurance not exported yet,
     * a previous export downloads the same list again without changing it.
     */
    private function insuranceExportAction(): Action
    {
        return Action::make('insuranceExport')
            ->label(__('resources.payment.insurance_export.action'))
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            // a new export marks payments, so it needs the right to record payments
            ->visible(fn() => auth()->user()?->can('create', Payment::class))
            ->modalHeading(__('resources.payment.insurance_export.heading'))
            ->modalWidth('lg')
            ->schema([
                Select::make('export')
                    ->label(__('resources.payment.insurance_export.export'))
                    ->options(fn() => $this->insuranceExportOptions())
                    ->default('new')
                    ->selectablePlaceholder(false)
                    ->required(),
                Radio::make('format')
                    ->label(__('resources.payment.insurance_export.format'))
                    ->options(InsuranceRoster::FORMATS)
                    ->default('xlsx')
                    ->inline()
                    ->required(),
            ])
            ->modalSubmitActionLabel(__('resources.payment.insurance_export.download'))
            ->action(function (array $data) {
                $export = $data['export'] === 'new'
                    ? InsuranceExport::createFromPending(auth()->id())
                    : InsuranceExport::find($data['export']);

                if (! $export) {
                    Notification::make()
                        ->warning()
                        ->title(__('resources.payment.insurance_export.nothing'))
                        ->body(__('resources.payment.insurance_export.nothing_body'))
                        ->send();

                    return null;
                }

                return InsuranceRoster::download($export, $data['format']);
            });
    }

    private function insuranceExportOptions(): array
    {
        $options = ['new' => __('resources.payment.insurance_export.new', [
            'count' => Payment::query()->awaitingInsuranceExport()->count(),
        ])];

        InsuranceExport::query()->withCount('payments')->latest('id')->limit(50)->get()
            ->each(function (InsuranceExport $export) use (&$options) {
                $options[$export->id] = __('resources.payment.insurance_export.previous', [
                    'id' => $export->id,
                    'date' => $export->created_at->format('d/m/Y'),
                    'count' => $export->payments_count,
                ]);
            });

        return $options;
    }
}
