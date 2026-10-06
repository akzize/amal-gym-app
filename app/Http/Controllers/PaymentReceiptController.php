<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Gate;

class PaymentReceiptController extends Controller
{
    public function __invoke(Payment $payment)
    {
        Gate::authorize('view', $payment);

        return view('payments.receipt_ar', [
            'payment' => $payment,
            'printerWidth' => config('receipt.paper_width'),
        ]);
    }
}
