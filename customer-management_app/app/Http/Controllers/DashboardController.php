<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DASH-01 ダッシュボード
 * 集計はすべてDB側で行い、全件をメモリーへ読み込まない (§6.2)。
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $stats = [
            'total_customers'  => Customer::query()->count(),
            'active_customers' => Customer::query()
                ->where('status', CustomerStatus::Active->value)
                ->count(),
            'monthly_new'      => Customer::query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'unpaid_invoices'  => Invoice::query()
                ->whereIn('status', ['unpaid', 'partial'])
                ->count(),
        ];

        $upcomingActions = Customer::query()
            ->with('assignedUser')
            ->whereNotNull('next_action_at')
            ->whereBetween('next_action_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('next_action_at')
            ->limit(8)
            ->get();

        $recentContacts = CustomerContact::query()
            ->with(['customer', 'creator'])
            ->latest('contacted_at')
            ->limit(8)
            ->get();

        // ルート画面 index.blade.php (§28) が dashboard.index を @include する
        return view('index', compact('stats', 'upcomingActions', 'recentContacts'));
    }
}
