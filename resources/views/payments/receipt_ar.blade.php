<!doctype html>
<html lang="ar">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>إيصال {{ $receiptNumber ?? '' }}</title>

        <!-- Print rules: no fixed @page size, so the receipt follows the paper picked in the
             print dialog (e.g. POS-80C). $printerWidth (config receipt.paper_width) only sets
             the on-screen preview width. -->
        @include('payments.partials.receipt-styles')

        @vite('resources/css/app.css')
    </head>

    @php
        $receiptNumber = now()->format('ym') . '-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT);
        $cashier = auth()->user()?->display_name ?? '—';
        $traineeName = $payment->trainee?->full_arabic_name ?? '—';
        $groupName = $payment->group?->name ?? '—';
        $association = $payment->group?->association;
        $associationLogo = $association?->logoDataUri();
        $centerName = $association?->name_arabic ?: 'مركز أمل للياقة البدنية';
        $subscriptionLabel = $payment->paymentType->name_ar ?? '—';
        // A month of a multi-month subscription prints the whole subscription
        $isSubscription = $payment->isSubscriptionMonth();
        if ($isSubscription) {
            $subscriptionLabel = $payment->subscription->duration_months . ' شهر';
        }
        $covered = $payment->coveredPayments();
        $amountGross = $covered->sum('amount_gross');
        $discountTotal = $covered->sum('discount_total');
        $amount_due = $covered->sum('amount_due');
        $amountPaid = $covered->sum('amount_paid');
        $remainingBalance = $amount_due - $amountPaid;
    @endphp

    <body class="flex items-center justify-center bg-white p-2" dir="rtl">
        <div class="receipt bg-white p-1">
            <!-- Header -->
            <div class="center">
                @if ($associationLogo)
                    <img class="logo" src="{{ $associationLogo }}" alt="" />
                @endif
                <div class="title">{{ $centerName }}</div>
                <div class="subtitle">{{ $addressLine ?? 'ورزازات' }}</div>
            </div>

            <div class="hr"></div>

            <div class="row" style="margin-top:4px;">
                <div class="label">رقم الإيصال</div>
                <div class="value">#{{ $receiptNumber ?? '—' }}</div>
            </div>
            <div class="row muted">
                <div class="label">تاريخ الدفع</div>
                <div class="value">{{ $date ?? now()->format('Y-m-d H:i') }}</div>
            </div>
            @if ($payment->payment_type_id == App\Models\Payment::TYPE_MONTHLY && !$isSubscription && $payment->month_key)
                <div class="row muted">
                    <div class="label">الدفعة لشهر</div>
                    <div class="value">{{ \Carbon\Carbon::parse($payment->month_key)->format('Y-m') }}</div>
                </div>
            @endif
            <div class="row muted">
                <div class="label">الكاشير</div>
                <div class="value">{{ $cashier ?? '—' }}</div>
            </div>

            <div class="hr"></div>

            <!-- Customer / Subscription -->
            <div class="row">
                <div class="label">المتدرب</div>
                <div class="value">{{ $traineeName ?? '—' }}</div>
            </div>
            <div class="row">
                <div class="label">المجموعة</div>
                <div class="value">{{ $groupName ?? '—' }}</div>
            </div>
            <div class="row">
                <div class="label">الاشتراك</div>
                <div class="value">{{ $subscriptionLabel ?? '—' }}</div>
            </div>
            @if ($payment->subscription)
                <div class="row">
                    <div class="label">بداية الاشتراك</div>
                    <div class="value">{{ $payment->subscription->start_date->format('Y-m-d') }}</div>
                </div>
                <div class="row">
                    <div class="label">نهاية الاشتراك</div>
                    <div class="value">{{ $payment->subscription->end_date->format('Y-m-d') }}</div>
                </div>
            @endif

            <div class="hr"></div>

            <!-- Financials -->
            @php
                $fmt = fn($n) => number_format((float) $n, 2, '.', ' ');
                // The fee line shows the list price; reductions and the net amount follow it
                $mf = $fmt($discountTotal > 0 ? $amountGross : $amount_due);
                $ap = $fmt($amountPaid ?? 0);
                $prev = $fmt($previousBalance ?? 0);
                $rem = $fmt($remainingBalance ?? 0);

                $paymentAmountLabel = match ($payment->paymentType->id) {
                    App\Models\Payment::TYPE_INSCRIPTION => 'مبلغ التسجيل',
                    App\Models\Payment::TYPE_INSURANCE => 'مبلغ التأمين',
                     default => 'الرسوم الشهرية'
                }
            @endphp

            <div class="row">
                <div class="label">{{ $paymentAmountLabel }}</div>
                <div class="value amount">{{ $mf }} درهم</div>
            </div>
            @if ($discountTotal > 0)
                @foreach ($payment->reductions() as $discount)
                    <div class="row">
                        <div class="label">{{ $discount->label }}</div>
                        <div class="value">- {{ $fmt($discount->amount) }} درهم</div>
                    </div>
                @endforeach
                <div class="row">
                    <div class="label">المبلغ المستحق</div>
                    <div class="value amount">{{ $fmt($amount_due) }} درهم</div>
                </div>
            @endif
            <div class="row">
                <div class="label">المبلغ المدفوع</div>
                <div class="value amount">{{ $ap }} درهم</div>
            </div>

            <div class="hr"></div>

            <div class="row">
                <div class="label">المبلغ المتبقي</div>
                <div class="value">{{ $rem }} درهم</div>
            </div>

            <div class="hr"></div>

            <div class="center thankyou" dir="ltr">شكرًا لك على دفعك</div>
            <div class="center small" style="margin-top:6px;">{{ $footer ?? '' }}</div>
        </div>

        @include('payments.partials.receipt-print-script')
    </body>

</html>
