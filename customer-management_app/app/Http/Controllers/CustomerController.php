<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Tag;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CustomerService;
use App\Services\SearchHashService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CUS-01 〜 CUS-04 顧客管理
 */
class CustomerController extends Controller
{
    /** 1ページの表示件数 (§6.2) */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly SearchHashService $searchHash,
        private readonly AuditLogService $auditLog,
    ) {
    }

    /** CUS-01 顧客一覧 (§18 検索・フィルター / §20 フローチャート) */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()->with(['assignedUser', 'tags']);

        // 削除済み表示(管理者のみ)
        $showDeleted = $request->boolean('show_deleted')
            && $request->user()->isAdmin();

        if ($showDeleted) {
            $query->onlyTrashed();
        }

        $this->applyKeyword($query, $request);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        // ページ単位で取得し、全件は読み込まない (§6.2 / §20)
        $customers = $query->paginate($perPage)->withQueryString();

        return view('customers.index', [
            'customers'      => $customers,
            'users'          => $this->assigneeOptions(),
            'tags'           => Tag::query()->orderBy('name')->get(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'showDeleted'    => $showDeleted,
        ]);
    }

    /** CUS-02 顧客登録画面 */
    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'customer' => new Customer(),
            'users'    => $this->assigneeOptions(),
            'tags'     => Tag::query()->orderBy('name')->get(),
        ]);
    }

    /** 顧客登録 (§19 フローチャート: 重複候補の警告つき) */
    public function store(StoreCustomerRequest $request): RedirectResponse|View
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validated();

        // 重複候補検索 → 警告 → 続行確認 (§19)
        if (! $request->boolean('force')) {
            $duplicates = $this->customerService->findDuplicates(
                (string) $validated['phone'],
                $validated['email'] ?? null,
            );

            if ($duplicates->isNotEmpty()) {
                return view('customers.create', [
                    'customer'   => new Customer(),
                    'users'      => $this->assigneeOptions(),
                    'tags'       => Tag::query()->orderBy('name')->get(),
                    'duplicates' => $duplicates,
                ]);
            }
        }

        $customer = $this->customerService->create($validated);
        $customer->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "顧客「{$customer->display_name}」を登録しました。");
    }

    /** CUS-03 顧客詳細 */
    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'assignedUser',
            'tags',
            'contacts' => fn ($q) => $q->with('creator')->limit(10),
            'contracts',
            'invoices.payments',
        ]);

        return view('customers.show', compact('customer'));
    }

    /** CUS-04 顧客編集画面 */
    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer,
            'users'    => $this->assigneeOptions(),
            'tags'     => Tag::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validated();

        $this->customerService->update($customer, $validated);
        $customer->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', '顧客情報を更新しました。');
    }

    /** 論理削除 (§5.2)。物理削除は行わない (§2.3)。 */
    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        $this->auditLog->record('customer_delete', 'customer', $customer->id);

        return redirect()
            ->route('customers.index')
            ->with('status', "顧客「{$customer->display_name}」を削除しました。削除済み一覧から復元できます。");
    }

    /** 削除済み顧客の復元 (§5.2) */
    public function restore(int $id): RedirectResponse
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $customer);

        $customer->restore();

        $this->auditLog->record('customer_restore', 'customer', $customer->id);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "顧客「{$customer->display_name}」を復元しました。");
    }

    // ------------------------------------------------------------------
    // 検索条件の組み立て
    // ------------------------------------------------------------------

    /**
     * キーワード検索 (§18.1)
     *   顧客ID: 完全一致・前方一致 / 氏名・社名系: 前方一致
     *   電話番号・メールアドレス: 正規化後のHMAC完全一致 (§18.2 / §20)
     */
    private function applyKeyword(Builder $query, Request $request): void
    {
        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword === '') {
            return;
        }

        // 電話番号らしい入力 → 正規化してハッシュ完全一致
        $digitsOnly = $this->searchHash->normalizePhone($keyword);
        if ($digitsOnly !== '' && mb_strlen($digitsOnly) >= 10
            && preg_match('/^[0-9\-\+\(\) ]+$/', $keyword) === 1) {
            $query->where('phone_hash', $this->searchHash->hash($digitsOnly));

            return;
        }

        // メールアドレスらしい入力 → 正規化してハッシュ完全一致
        if (str_contains($keyword, '@')) {
            $normalizedEmail = $this->searchHash->normalizeEmail($keyword);
            $query->where('email_hash', $this->searchHash->hash($normalizedEmail));

            return;
        }

        // 顧客ID・氏名・社名(前方一致。LIKEワイルドカードはエスケープ)
        $escaped = addcslashes($keyword, '%_\\');
        $query->where(function (Builder $q) use ($escaped): void {
            $q->where('customer_code', 'like', "{$escaped}%")
                ->orWhere('customer_name', 'like', "{$escaped}%")
                ->orWhere('customer_name_kana', 'like', "{$escaped}%")
                ->orWhere('company_name', 'like', "{$escaped}%")
                ->orWhere('company_name_kana', 'like', "{$escaped}%");
        });
    }

    /** 詳細フィルター (§18.3) */
    private function applyFilters(Builder $query, Request $request): void
    {
        if ($type = $request->input('customer_type')) {
            if (CustomerType::tryFrom((string) $type) !== null) {
                $query->where('customer_type', $type);
            }
        }

        if ($status = $request->input('status')) {
            if (CustomerStatus::tryFrom((string) $status) !== null) {
                $query->where('status', $status);
            }
        }

        if ($assigned = $request->input('assigned_user_id')) {
            $query->where('assigned_user_id', (int) $assigned);
        }

        // 登録期間
        if ($from = $request->date('created_from')) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($to = $request->date('created_to')) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        // 最終対応期間
        if ($from = $request->date('contacted_from')) {
            $query->where('last_contacted_at', '>=', $from->startOfDay());
        }
        if ($to = $request->date('contacted_to')) {
            $query->where('last_contacted_at', '<=', $to->endOfDay());
        }

        // 次回対応期間
        if ($from = $request->date('next_action_from')) {
            $query->where('next_action_at', '>=', $from->startOfDay());
        }
        if ($to = $request->date('next_action_to')) {
            $query->where('next_action_at', '<=', $to->endOfDay());
        }

        // 契約状況
        match ($request->input('contract_state')) {
            'active' => $query->whereHas('contracts', fn ($q) => $q->where('status', 'active')),
            'none'   => $query->whereDoesntHave('contracts'),
            default  => null,
        };

        // 入金状況
        match ($request->input('payment_state')) {
            'unpaid' => $query->whereHas('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'partial'])),
            'clear'  => $query->whereDoesntHave('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'partial'])),
            default  => null,
        };

        // タグ
        if ($tagId = $request->input('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', (int) $tagId));
        }
    }

    /** 並び替え (§18.4) */
    private function applySort(Builder $query, Request $request): void
    {
        match ($request->input('sort', 'created_desc')) {
            'created_asc'    => $query->orderBy('created_at'),
            'code'           => $query->orderBy('customer_code'),
            'name'           => $query->orderByRaw('customer_name IS NULL, customer_name'),
            'company'        => $query->orderByRaw('company_name IS NULL, company_name'),
            'contacted_desc' => $query->orderByRaw('last_contacted_at IS NULL, last_contacted_at DESC'),
            'next_action'    => $query->orderByRaw('next_action_at IS NULL, next_action_at'),
            default          => $query->latest('created_at'),
        };

        $query->orderBy('id'); // ページ間で順序を安定させる
    }

    /** 担当者の選択肢(有効ユーザーのみ) */
    private function assigneeOptions()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
