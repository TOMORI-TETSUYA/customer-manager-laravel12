{{--
    DASH-01 ダッシュボード本体
    ルートの index.blade.php (§28) から @include される部分テンプレート。
    変数 $stats / $upcomingActions / $recentContacts は
    DashboardController から親ビュー経由で受け取る。
--}}

@push('styles')
    <link
        rel="stylesheet"
        href="{{ $phAsset('/css/dashboard.css') }}"
    >
@endpush

<div class="ph-page-head">
    <h1 class="ph-page-head__title">ダッシュボード</h1>
    <p class="ph-page-head__sub">{{ now()->isoFormat('YYYY年M月D日(ddd)') }}</p>
    @can('create', App\Models\Customer::class)
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--primary" href="{{ route('customers.create') }}">
                顧客を登録する
            </a>
        </div>
    @endcan
</div>

<div class="dash-stats">
    <div class="dash-stat">
        <span class="dash-stat__label">登録顧客数</span>
        <span class="dash-stat__value">{{ number_format($stats['total_customers']) }}</span>
    </div>
    <div class="dash-stat dash-stat--accent">
        <span class="dash-stat__label">取引中の顧客</span>
        <span class="dash-stat__value">{{ number_format($stats['active_customers']) }}</span>
    </div>
    <div class="dash-stat">
        <span class="dash-stat__label">今月の新規登録</span>
        <span class="dash-stat__value">{{ number_format($stats['monthly_new']) }}</span>
    </div>
    <div class="dash-stat dash-stat--warn">
        <span class="dash-stat__label">未入金の請求</span>
        <span class="dash-stat__value">{{ number_format($stats['unpaid_invoices']) }}</span>
    </div>
</div>

<div class="dash-columns">

    <section class="ph-card">
        <div class="ph-card__body">
            <h2 class="ph-card__title">今後7日間の次回対応</h2>

            @if ($upcomingActions->isEmpty())
                <p class="dash-empty">予定されている対応はありません。</p>
            @else
                <ul class="dash-list">
                    @foreach ($upcomingActions as $customer)
                        <li class="dash-list__item">
                            <div class="dash-list__main">
                                <span class="dash-list__title">
                                    <a href="{{ route('customers.show', $customer) }}">
                                        {{ $customer->display_name }}
                                    </a>
                                </span>
                                <span class="dash-list__meta">
                                    担当: {{ $customer->assignedUser?->name ?? '未設定' }}
                                </span>
                            </div>
                            <span class="ph-badge ph-badge--info ph-num">
                                {{ $customer->next_action_at?->isoFormat('M/D(ddd)') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="ph-card">
        <div class="ph-card__body">
            <h2 class="ph-card__title">最近の対応履歴</h2>

            @if ($recentContacts->isEmpty())
                <p class="dash-empty">対応履歴はまだありません。</p>
            @else
                <ul class="dash-list">
                    @foreach ($recentContacts as $contact)
                        <li class="dash-list__item">
                            <div class="dash-list__main">
                                <span class="dash-list__title">{{ $contact->subject }}</span>
                                <span class="dash-list__meta">
                                    {{ $contact->customer?->display_name }}
                                    / {{ App\Models\CustomerContact::METHODS[$contact->contact_method] ?? $contact->contact_method }}
                                    / {{ $contact->creator?->name }}
                                </span>
                            </div>
                            <span class="ph-muted ph-text-sm ph-num ph-nowrap">
                                {{ $contact->contacted_at?->isoFormat('M/D HH:mm') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

</div>
