<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * INV-01 請求登録
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function create(Customer $customer): View
    {
        Gate::authorize('write-data');

        $contracts = $customer->contracts()->get();

        return view('invoices.create', compact('customer', 'contracts'));
    }

    public function store(StoreInvoiceRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('write-data');

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $customer): void {
            $invoice = new Invoice($validated);
            $invoice->customer_id     = $customer->id;
            $invoice->contract_id     = $validated['contract_id'] ?? null;
            $invoice->invoice_number  = $this->issueInvoiceNumber();
            $invoice->status          = 'unpaid';
            $invoice->notes_encrypted = $validated['notes'] ?? null;
            $invoice->created_by      = Auth::id();
            $invoice->save();

            $this->auditLog->record('invoice_create', 'invoice', $invoice->id);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', '請求情報を登録しました。');
    }

    /** 請求番号を自動発行する (例: INV-202608-0001) */
    private function issueInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';

        $last = Invoice::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last !== null
            ? (int) substr($last, -4) + 1
            : 1;

        return $prefix . sprintf('%04d', $seq);
    }
}
