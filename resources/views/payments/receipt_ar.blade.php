<!doctype html>
<html lang="ar">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>إيصال {{ $receiptNumber ?? '' }}</title>

        <!-- Print rules: set paper width via $printerWidth (58mm or 80mm) -->
        <style>
            :root {
                --printer-width: {{ $printerWidth ?? '58mm' }};
            }

            @page {
                size: var(--printer-width) auto;
                margin: 4mm;
            }

            @media print {

                html,
                body {
                    width: var(--printer-width);
                }

                /* reduce default margins when printing */
                body {
                    margin: 0;
                    -webkit-print-color-adjust: exact;
                }
            }

            /* make the receipt compact and monospaced for thermal printers */
            .receipt {
                width: calc(var(--printer-width) - 8mm);
                max-width: 100%;
                font-family: Arial, "Segoe UI", Tahoma, Geneva, Verdana, sans-serif, Helvetica, "Courier New", monospace;
                font-size: 10px;
                color: #111827;
                line-height: 1.1;
                word-break: break-word;
            }

            .hr {
                border-bottom: 1px dashed #222;
                margin: 6px 0;
                opacity: 0.6;
            }

            /* columns: left label + right value */
            .row {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                align-items: baseline;
            }

            .muted {
                color: #6b7280;
                font-size: 9px;
            }

            .center {
                text-align: center;
            }

            /* compact small text for footer */
            .small {
                font-size: 9px;
                color: #374151;
            }

            /* ensure long text wraps under label (for trainee/group names) */
            .label {
                flex: 1 1 auto;
                min-width: 0;
            }

            .value {
                flex: 0 0 auto;
                text-align: left;
                /* RTL: values on left */
                white-space: nowrap;
            }

            /* highlight amounts */
            .amount {
                font-weight: 500;
            }

            /* THANK YOU centered and spaced */
            .thankyou {
                margin-top: 8px;
                /* letter-spacing: 0.06em; */
                font-weight: 600;
            }
        </style>

        @vite('resources/css/app.css')
    </head>

    @php
        $receiptNumber = now()->format('ym') . '-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT);
        $cashier = auth()->user()?->name ?? '—';
        $traineeName = $payment->trainee?->full_arabic_name ?? '—';
        $groupName = $payment->group?->name ?? '—';
        $subscriptionLabel = $payment->paymentType->name_ar ?? '—';
        if ($payment->paymentType->id == App\Models\Payment::TYPE_CUSTOM) {
            $subscriptionLabel = $payment->subscription->duration_months . ' شهر';
        }
        $amount_due = $payment->amount_due ?? 0;
        $amountPaid = $payment->amount_paid ?? 0;
        $remainingBalance = $payment->amount_due - $payment->amount_paid ?? 0;
    @endphp

    <body class="flex items-center justify-center bg-white p-2" dir="rtl">
        <div class="receipt bg-white p-1">
            <!-- Header -->
            <div class="center">
                <div class="text-xs font-bold">{{ $centerName ?? 'مركز أمل للياقة البدنية' }}</div>
                <div class="muted">{{ $addressLine ?? 'ورزازات' }}</div>
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
            @if ($payment->paymentType->id == App\Models\Payment::TYPE_CUSTOM)
                <div class="row muted">
                    <div class="label">الدفعة لشهر</div>
                    <div class="value">{{ $payment->applies_to_date }}</div>
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
            @if (
                $payment->paymentType->id == App\Models\Payment::TYPE_CUSTOM ||
                    $payment->paymentType->id == App\Models\Payment::TYPE_INSURANCE)
                <div class="row">
                    <div class="label">بداية الاشتراك</div>
                    <div class="value">{{ $payment->subscription->start_date }}</div>
                </div>
                <div class="row">
                    <div class="label">نهاية الاشتراك</div>
                    <div class="value">{{ $payment->subscription->end_date }}</div>
                </div>
            @endif

            <div class="hr"></div>

            <!-- Financials -->
            @php
                function fmt($n)
                {
                    return number_format((float) $n, 2, '.', ' ');
                }
                $mf = fmt($amount_due ?? 0);
                $pd = fmt($proratedDiscount ?? 0);
                $ap = fmt($amountPaid ?? 0);
                $prev = fmt($previousBalance ?? 0);
                $rem = fmt($remainingBalance ?? 0);

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
            {{-- <div class="row">
            <div class="label">خصم تناسبي</div>
            <div class="value">- {{ $pd }} درهم</div>
        </div> --}}
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

        <script>
            function printReceipt() {
                window.print();
            }
        </script>
    </body>

</html>
