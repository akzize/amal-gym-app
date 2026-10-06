<!doctype html>
<html lang="ar">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>إيصال {{ $receiptNumber ?? '' }}</title>

        <!-- Same layout and print rules as payments/receipt_ar.blade.php -->
        @include('payments.partials.receipt-styles')

        @vite('resources/css/app.css')
    </head>

    @php
        // The user who recorded the payment, not whoever is (re)printing it
        $cashier = $installment->recorder?->display_name ?? '—';
        // Arabic name, French name as fallback
        $trainerName = $trainer->display_name ?? '—';
        $association = $trainer->groups->first()?->association;
        $associationLogo = $association?->logoDataUri();
        $centerName = $association?->name_arabic ?: $association?->name ?: 'مركز أمل للياقة البدنية';
        $salaryTypeLabel = __('resources.trainer.' . $trainer->salary_type);
        $amount_due = $payout->expected_amount ?? 0;
        // Paid so far this month (up to this installment), so due - paid = remaining
        $amountPaid = $paidToDate ?? 0;
        $remainingBalance = $remaining ?? 0;
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
                <div class="value">{{ $installment->paid_at->format('Y-m-d H:i') }}</div>
            </div>
            <div class="row muted">
                <div class="label">الدفعة لشهر</div>
                <div class="value">{{ $payout->month_key->format('Y-m') }}</div>
            </div>
            <div class="row muted">
                <div class="label">الكاشير</div>
                <div class="value">{{ $cashier ?? '—' }}</div>
            </div>

            <div class="hr"></div>

            <!-- Trainer -->
            <div class="row">
                <div class="label">المدرب</div>
                <div class="value">{{ $trainerName ?? '—' }}</div>
            </div>
            <div class="row">
                <div class="label">نوع الراتب</div>
                <div class="value">{{ $salaryTypeLabel ?? '—' }}</div>
            </div>

            <div class="hr"></div>

            <!-- Financials -->
            @php
                $fmt = fn($n) => number_format((float) $n, 2, '.', ' ');
                $mf = $fmt($amount_due ?? 0);
                $ap = $fmt($amountPaid ?? 0);
                $rem = $fmt($remainingBalance ?? 0);
            @endphp

            <div class="row">
                <div class="label">الأجرة الشهرية</div>
                <div class="value amount">{{ $mf }} درهم</div>
            </div>
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

            <div class="center thankyou" dir="ltr">شكرًا لك</div>
            <div class="center small" style="margin-top:6px;">{{ $footer ?? '' }}</div>
        </div>

        @include('payments.partials.receipt-print-script')
    </body>

</html>
