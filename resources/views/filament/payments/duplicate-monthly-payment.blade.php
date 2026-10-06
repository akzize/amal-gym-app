{{-- Shown when creating a monthly payment for a month that is already recorded.
     Inline styles: the panel has no custom theme, so arbitrary Tailwind utilities aren't compiled. --}}
@php
    use App\Filament\Resources\Payments\PaymentResource;
    use App\Models\Payment;

    $money = fn($amount) => number_format((float) $amount, 2) . ' MAD';
@endphp

@if ($payment)
    @php
        $traineeName = $payment->trainee?->full_arabic_name ?: ($payment->trainee?->full_name ?? '—');
        $remaining = max(0, (float) $payment->amount_due - (float) $payment->amount_paid);
        $statusColor = match ($payment->status) {
            Payment::STATUS_PAID, Payment::STATUS_FREE => 'success',
            Payment::STATUS_PARTIAL => 'warning',
            default => 'danger',
        };
        $row = 'display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;';
    @endphp

    <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
        <p>
            {{ __('resources.payment.duplicate_monthly_body', [
                'trainee' => $traineeName,
                'month' => \Carbon\Carbon::parse($payment->month_key)->format('Y-m'),
            ]) }}
        </p>

        <div style="border: 1px solid rgb(128 128 128 / 0.25); border-radius: 0.5rem; padding: 0.5rem 0.75rem;">
            <div style="{{ $row }}">
                <span>{{ __('resources.trainee.label') }}</span>
                <span style="font-weight: 600;">{{ $traineeName }}</span>
            </div>
            <div style="{{ $row }}">
                <span>{{ __('resources.group.label') }}</span>
                <span>{{ $payment->group?->name ?? '—' }}</span>
            </div>
            <div style="{{ $row }} align-items: center;">
                <span>{{ __('resources.payment.status.label') }}</span>
                <x-filament::badge :color="$statusColor">
                    {{ __('resources.payment.status.' . $payment->status) }}
                </x-filament::badge>
            </div>
            <div style="{{ $row }}">
                <span>{{ __('resources.payment.amount_due') }}</span>
                <span>{{ $money($payment->amount_due) }}</span>
            </div>
            <div style="{{ $row }}">
                <span>{{ __('resources.payment.amount_paid') }}</span>
                <span>{{ $money($payment->amount_paid) }}</span>
            </div>
            <div style="{{ $row }} font-weight: 600;">
                <span>{{ __('resources.payment.remaining_amount') }}</span>
                <span>{{ $money($remaining) }}</span>
            </div>
        </div>

        @if ($remaining > 0)
            <p style="opacity: 0.75;">{{ __('resources.payment.duplicate_monthly_hint') }}</p>
        @endif

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <x-filament::button tag="a" :href="PaymentResource::getUrl('edit', ['record' => $payment])" icon="heroicon-o-eye">
                {{ __('resources.payment.view_existing') }}
            </x-filament::button>
            <x-filament::button tag="a" color="gray" target="_blank" icon="heroicon-o-printer"
                :href="route('filament.admin.payments.receipt', $payment)">
                {{ __('resources.trainer.print_receipt') }}
            </x-filament::button>
        </div>
    </div>
@endif
