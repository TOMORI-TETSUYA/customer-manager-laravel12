<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Models\Contract;
use App\Models\Customer;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CTR-01 契約登録
 */
class ContractController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function create(Customer $customer): View
    {
        Gate::authorize('write-data');

        return view('contracts.create', compact('customer'));
    }

    public function store(StoreContractRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('write-data');

        DB::transaction(function () use ($request, $customer): void {
            $contract = new Contract($request->validated());
            $contract->customer_id     = $customer->id;
            $contract->contract_number = $this->issueContractNumber();
            $contract->created_by      = Auth::id();
            $contract->save();

            $this->auditLog->record('contract_create', 'contract', $contract->id);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', '契約情報を登録しました。');
    }

    /** 契約番号を自動発行する (例: CT-202608-0001) */
    private function issueContractNumber(): string
    {
        $prefix = 'CT-' . now()->format('Ym') . '-';

        $last = Contract::withTrashed()
            ->where('contract_number', 'like', $prefix . '%')
            ->orderByDesc('contract_number')
            ->value('contract_number');

        $seq = $last !== null
            ? (int) substr($last, -4) + 1
            : 1;

        return $prefix . sprintf('%04d', $seq);
    }
}
