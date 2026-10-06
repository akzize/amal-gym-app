<?php

namespace App\Http\Controllers;

use App\Models\TrainerPayoutInstallment;
use Illuminate\Support\Facades\Gate;

class TrainerPayoutReceiptController extends Controller
{
    public function __invoke(TrainerPayoutInstallment $installment)
    {
        $payout = $installment->payout;
        $trainer = $payout->trainer;

        Gate::authorize('view', $trainer);

        // Totals as they stood right after this installment, so reprints of older ones stay accurate
        $paidToDate = (float) $payout->installments()->where('id', '<=', $installment->id)->sum('amount');

        return view('trainer-payouts.receipt_ar', [
            'installment' => $installment,
            'payout' => $payout,
            'trainer' => $trainer,
            'paidToDate' => $paidToDate,
            'remaining' => max(0, round((float) $payout->expected_amount - $paidToDate, 2)),
            'receiptNumber' => 'T' . $installment->paid_at->format('ym') . '-' . str_pad($installment->id, 5, '0', STR_PAD_LEFT),
            'printerWidth' => config('receipt.paper_width'),
        ]);
    }
}
