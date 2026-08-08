<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * USR-01 ユーザー管理 (§16)
 *
 * 一覧はどのロールでも開けるが、表示される範囲がロールごとに異なる。
 *   管理者   : 全ロール
 *   職員     : 職員とメンバー
 *   メンバー : メンバーのみ (閲覧のみ。作成や有効無効の切り替えは不可)
 */
class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(): View
    {
        Gate::authorize('access-users');

        /** @var User $viewer */
        $viewer = Auth::user();

        $users = User::query()
            // 見えてよいロールだけに絞る (§16)
            ->whereIn('role', $viewer->visibleUserRoles())
            ->orderByDesc('is_active')
            ->orderBy('login_id')
            ->paginate(50);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        /** @var User $actor */
        $actor = Auth::user();

        // 管理者は全ロール、職員はメンバーのみ作成できる (§16)
        return view('users.create', ['roles' => $actor->creatableUserRoles()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validated();

        // 平文はここでしか保持しない。
        // User モデルの casts に 'password' => 'hashed' があるため、
        // create() の時点で Argon2id ハッシュへ変換されて保存される。
        $plainPassword = (string) $validated['password'];

        $user = User::query()->create([
            ...$validated,
            'is_active'            => true,
            'must_change_password' => true, // 初回ログイン時に変更させる (§15.1)
        ]);

        $this->auditLog->record('user_create', 'user', $user->id);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "ユーザー「{$user->name}」を作成しました。初回ログイン時にパスワード変更が必要です。")
            // 発行直後の1回だけ一覧へ平文を渡す (§15.1)。
            // フラッシュデータなので次のリクエストで自動的に破棄され、
            // 再読み込み・別画面からの復帰では ●●●●● 表示に戻る。
            ->with('issued_credential', [
                'user_id'  => $user->id,
                'login_id' => $user->login_id,
                'name'     => $user->name,
                'password' => $plainPassword,
            ]);
    }

    /**
     * パスワードの再発行 (§15.1)
     *
     * 保存されているのはハッシュ値で元に戻せないため、
     * 新しいパスワードを生成して置き換える。
     * 平文は一覧へ1回だけ渡し、DBには保存しない。
     */
    public function regeneratePassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $this->assertOperable($user);

        $isSelf = $user->id === Auth::id();

        // 平文はこのリクエストの間だけ保持する。
        // casts の 'password' => 'hashed' により保存時に Argon2id へ変換される。
        $plainPassword = Str::password(16);

        $user->forceFill([
            'password' => $plainPassword,
            // 他人へ発行した場合は、本人が最初のログインで変更する。
            // 自分で発行した場合は本人がこの画面で控えるため強制しない。
            'must_change_password' => ! $isSelf,
        ])->save();

        if ($isSelf) {
            // 自分のセッションまで切ると、発行された平文を確認できないまま
            // ログイン画面へ飛んでしまう。セッションIDだけ入れ替える。
            $request->session()->regenerate();
        } else {
            // 古いパスワードで開かれたままのセッションを閉じる。
            // 再発行した以上、そのまま使い続けられては意味がない。
            $this->revokeSessions($user);
        }

        $this->auditLog->record('user_password_reset', 'user', $user->id);

        return back()
            ->with('status', $isSelf
                ? 'あなたのパスワードを再発行しました。次回のログインから新しいパスワードを使います。必ず控えてください。'
                : "ユーザー「{$user->name}」のパスワードを再発行しました。")
            // 発行直後の1回だけ一覧へ平文を渡す。
            // フラッシュデータなので再読み込みすると ●●●●● 表示に戻る。
            ->with('issued_credential', [
                'user_id'  => $user->id,
                'login_id' => $user->login_id,
                'name'     => $user->name,
                'password' => $plainPassword,
            ]);
    }

    /**
     * ユーザーの削除 (§2.3: 論理削除)
     *
     * 顧客の担当者や操作履歴から参照されているため、行そのものは残す。
     * deleted_at が入ると認証を通過できなくなり、一覧にも出なくなる。
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $this->assertOperable($user);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => '自分自身を削除することはできません。']);
        }

        $name = $user->name;

        $this->auditLog->record('user_delete', 'user', $user->id);

        // 削除後にログイン状態が残らないようにする
        $this->revokeSessions($user);

        $user->delete();

        return back()->with('status', "ユーザー「{$name}」を削除しました。ログインできなくなります。");
    }

    /** 有効・無効の切り替え (§5.1) */
    public function toggle(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $this->assertOperable($user);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => '自分自身を無効化することはできません。']);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        // 無効化した場合は、そのユーザーの既存セッションと remember-me を
        // その場で捨てる (§15)。これが無いと、ログイン中の相手は
        // 無効化しても操作を続けられてしまう。
        if (! $user->is_active) {
            $this->revokeSessions($user);
        }

        $this->auditLog->record(
            $user->is_active ? 'user_enable' : 'user_disable',
            'user',
            $user->id,
        );

        return back()->with('status', 'ユーザーの状態を変更しました。');
    }

    /**
     * 操作対象が自分の権限で扱えるユーザーかを確認する (§16)。
     *
     * 一覧に出ないロール(職員から見た管理者など)は、URLを直接叩かれても
     * 操作させない。該当しなければ 403 を返す。
     */
    private function assertOperable(User $user): void
    {
        /** @var User $actor */
        $actor = Auth::user();

        if (! in_array($user->role->value, $actor->visibleUserRoles(), true)) {
            abort(403);
        }
    }

    /**
     * 対象ユーザーのログイン状態を破棄する (§15)
     *
     * ・remember-me トークンを消す
     *     消さないと、クッキーを持っている端末が自動的にログインし直せてしまう。
     * ・DBに残っているセッション行を消す
     *     消さないと、開いたままのブラウザがそのまま操作を続けられる。
     */
    private function revokeSessions(User $user): void
    {
        $user->forceFill(['remember_token' => null])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
