<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerContactRequest;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CON-01 対応履歴登録
 */
class CustomerContactController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function create(Customer $customer): View
    {
        Gate::authorize('write-data');

        return view('contacts.create', compact('customer'));
    }

    public function store(StoreCustomerContactRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('write-data');

        $validated = $request->validated();

        DB::transaction(function () use ($customer, $validated): void {
            $contact = new CustomerContact($validated);
            $contact->customer_id        = $customer->id;
            $contact->response_encrypted = $validated['response'] ?? null;
            $contact->created_by         = Auth::id();
            $contact->save();

            // 最終対応日・次回対応日を顧客側へ反映 (§4)
            $customer->forceFill([
                'last_contacted_at' => max(
                    $customer->last_contacted_at,
                    $contact->contacted_at,
                ),
                'next_action_at'    => $contact->next_action_at
                    ?? $customer->next_action_at,
            ])->save();

            $this->auditLog->record('contact_create', 'customer_contact', $contact->id);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', '対応履歴を登録しました。');
    }
}
