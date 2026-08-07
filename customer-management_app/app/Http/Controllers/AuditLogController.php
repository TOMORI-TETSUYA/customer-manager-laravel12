<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * LOG-01 操作履歴 (管理者のみ)
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-audit-logs');

        $query = AuditLog::query()->with('user')->latest('created_at');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', (string) $action);
        }

        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('audit_logs.index', compact('logs', 'users'));
    }
}
