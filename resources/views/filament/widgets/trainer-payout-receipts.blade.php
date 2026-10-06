{{-- Installments of a trainer, newest first, each with a link to its printable receipt.
     Inline styles: the panel has no custom theme, so arbitrary Tailwind utilities aren't compiled. --}}
<div style="display: flex; flex-direction: column; gap: 1rem;">
    @forelse ($payouts as $payout)
        <div>
            <div style="display: flex; justify-content: space-between; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">
                <span>{{ $payout->month_key->format('Y-m') }}</span>
                <span style="opacity: 0.7;">
                    {{ number_format($payout->paidAmount(), 2) }} / {{ number_format((float) $payout->expected_amount, 2) }} MAD
                </span>
            </div>

            @foreach ($payout->installments as $installment)
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid rgb(128 128 128 / 0.25); border-radius: 0.5rem; margin-bottom: 0.25rem;">
                    <span>{{ $installment->paid_at->format('Y-m-d H:i') }}</span>
                    <span style="font-weight: 500;">{{ number_format((float) $installment->amount, 2) }} MAD</span>
                    <x-filament::link :href="route('filament.admin.trainer-payouts.receipt', $installment)" target="_blank"
                        icon="heroicon-o-printer">
                        {{ __('resources.trainer.print_receipt') }}
                    </x-filament::link>
                </div>
            @endforeach
        </div>
    @empty
        <p style="font-size: 0.875rem; opacity: 0.7;">{{ __('resources.trainer.no_payout_receipts') }}</p>
    @endforelse
</div>
