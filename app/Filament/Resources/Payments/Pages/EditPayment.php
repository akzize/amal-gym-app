<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    #[On('refreshPaymentComponents')]
    public function refresh(): void {
        $this->refreshForm();
    }
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function refreshForm(): void
    {
        $this->fillForm(); // Reload Fill form data from DB
        $this->dispatch('refresh'); // Refresh child components like widgets, stats, tables
    }
}
