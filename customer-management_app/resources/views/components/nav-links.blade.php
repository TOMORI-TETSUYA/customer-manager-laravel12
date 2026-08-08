{{-- サイドメニュー / Offcanvas 共通のナビゲーション (§11.1)

     表示されるメニューはロールで変わる (§16)。
     メンバーは顧客管理・契約管理・ユーザー管理のみ。

     【ホバー時のURL表示について】
     リンクにマウスを重ねると、ブラウザは画面の左下に遷移先のURLを出す。
     これを表示させないため、js/app.js の hideLinkUrlOnHover() が
     「マウスが乗っている間だけ href を一時的に外す」処理を行っている。

     ここでは普通の <a href="..."> のまま書いてよい。
     マウスのボタンを押した瞬間に href が戻るので、右クリックメニューの
     「新しいタブで開く」「リンクのアドレスをコピー」、中クリック、
     Ctrl(⌘)+クリックはブラウザ本来の動作がそのまま使える。 --}}

@can('access-dashboard')
    <a
        class="ph-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
        @if (request()->routeIs('dashboard')) aria-current="page" @endif
        href="{{ route('dashboard') }}"
    >
        <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 12 12 4l8 8"/><path d="M6 10v10h12V10"/>
        </svg>
        ダッシュボード
    </a>
@endcan

<a
    class="ph-nav-link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}"
    @if (request()->routeIs('customers.*')) aria-current="page" @endif
    href="{{ route('customers.index') }}"
>
    <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="9" cy="8" r="3.2"/>
        <path d="M3.5 19c.8-3 3-4.5 5.5-4.5s4.7 1.5 5.5 4.5"/>
        <path d="M16 8.5a2.8 2.8 0 1 0 0-.01M16.5 14.7c2 .4 3.4 1.7 4 4.3"/>
    </svg>
    顧客管理
</a>

@can('access-dialog')
    <a
        class="ph-nav-link {{ request()->routeIs('dialog.index', 'contacts.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('dialog.index', 'contacts.*')) aria-current="page" @endif
        href="{{ route('dialog.index') }}"
    >
        <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20 12a8 8 0 1 0-3 6.2L21 20l-1-4a8 8 0 0 0 0-4Z"/>
        </svg>
        対応履歴
    </a>
@endcan

<a
    class="ph-nav-link {{ request()->routeIs('contract.index', 'contracts.*') ? 'is-active' : '' }}"
    @if (request()->routeIs('contract.index', 'contracts.*')) aria-current="page" @endif
    href="{{ route('contract.index') }}"
>
    <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>
    </svg>
    契約管理
</a>

@can('access-payment')
    <a
        class="ph-nav-link {{ request()->routeIs('payment.index', 'invoices.*', 'payments.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('payment.index', 'invoices.*', 'payments.*')) aria-current="page" @endif
        href="{{ route('payment.index') }}"
    >
        <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="6" width="18" height="13" rx="2"/>
            <path d="M3 10h18M7 15h4"/>
        </svg>
        請求・入金
    </a>
@endcan

@canany(['access-users', 'view-audit-logs'])
    <p class="ph-nav-section">管理</p>
@endcanany

@can('access-users')
    <a
        class="ph-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.users.*')) aria-current="page" @endif
        href="{{ route('admin.users.index') }}"
    >
        <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="3.2"/>
            <path d="M5.5 20c.9-3.4 3.5-5 6.5-5s5.6 1.6 6.5 5"/>
        </svg>
        ユーザー管理
    </a>
@endcan

@can('view-audit-logs')
    <a
        class="ph-nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.audit-logs.*')) aria-current="page" @endif
        href="{{ route('admin.audit-logs.index') }}"
    >
        <svg class="ph-nav-link__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/>
        </svg>
        操作履歴
    </a>
@endcan
