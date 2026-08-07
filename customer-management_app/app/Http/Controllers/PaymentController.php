<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * PAY-01 入金登録 (分割入金対応)
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function create(Invoice $invoice): View
    {
        Gate::authorize('write-data');

        $invoice->load(['customer', 'payments']);

        return view('payments.create', compact('invoice'));
    }

    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('write-data');

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $invoice): void {
            $payment = new Payment($validated);
            $payment->invoice_id      = $invoice->id;
            $payment->notes_encrypted = $validated['notes'] ?? null;
            $payment->created_by      = Auth::id();
            $payment->save();

            // 入金合計から請求ステータスを再計算する
            $paidTotal = (int) $invoice->payments()->sum('amount');

            $invoice->forceFill([
                'status' => $paidTotal >= (int) $invoice->amount
                    ? 'paid'
                    : ($paidTotal > 0 ? 'partial' : 'unpaid'),
            ])->save();

            $this->auditLog->record('payment_create', 'payment', $payment->id);
        });

        return redirect()
            ->route('customers.show', $invoice->customer_id)
            ->with('status', '入金を登録しました。');
    }
}
