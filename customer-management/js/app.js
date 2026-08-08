/* =====================================================================
   Patron Hub - app.js
   全画面共通: 二重送信防止 / 確認ダイアログ / フラッシュ自動消去
   ===================================================================== */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        preventDoubleSubmit();
        bindConfirmDialogs();
        autoDismissFlash();
        hideLinkUrlOnHover();
    });

    /**
     * ホバー中だけリンクのURLを隠す (§11.1)
     *
     * リンクにマウスを重ねると、ブラウザは画面の左下へ遷移先のURLを表示する。
     * これはブラウザ自体の機能で、CSSやJavaScriptから直接止める方法はない。
     * 唯一の手立ては「その瞬間だけ href を外す」ことなので、それを行う。
     *
     * 単純に href を消したままにすると、右クリックメニューの
     * 「新しいタブで開く」「リンクのアドレスをコピー」や、中クリック、
     * Ctrl(⌘)+クリックといったブラウザ本来の操作がすべて使えなくなる。
     * そこで次のように出し入れしている。
     *
     *   マウスが乗った / 動いた   → href を外す (URLが出ない)
     *   マウスのボタンを押した     → href を戻す (ブラウザ本来の操作が働く)
     *   マウスが離れた             → href を戻す (次に乗ったらまた外れる)
     *
     * ボタンを押した時点で href が戻るため、その直後に発生する
     * クリック・中クリック・右クリックメニューはすべて通常どおり動く。
     *
     * HTML側は普通の <a href="..."> のままでよい。この処理が動かなくても
     * リンクとしては正しく機能する(JSの読み込みに失敗しても遷移できる)。
     */
    function hideLinkUrlOnHover() {
        document.querySelectorAll("a[href]").forEach(function (element) {
            /* 外部サイトへのリンクは対象にしない。
               どこへ飛ぶのかを確認できることが、利用者の安全につながるため。 */
            if (element.origin !== window.location.origin) {
                return;
            }

            // ページ内リンク(#...)やメール等はそのままにする
            var url = element.getAttribute("href");
            if (!url || url.charAt(0) === "#") {
                return;
            }

            function hide() {
                if (element.hasAttribute("href")) {
                    element.removeAttribute("href");
                }
            }

            function show() {
                if (!element.hasAttribute("href")) {
                    element.setAttribute("href", url);
                }
            }

            element.addEventListener("mouseenter", hide);

            /* 右クリックメニューを閉じた直後など、いったん href を戻したまま
               マウスが乗り続けている状態から、動かした時点で再び隠す。 */
            element.addEventListener("mousemove", hide);

            /* ボタンを押した瞬間に戻す。mousedown は click・auxclick・
               contextmenu のいずれよりも先に発生するので、
               どの操作でもブラウザ本来の動作に間に合う。 */
            element.addEventListener("mousedown", show);

            // キーボード操作(Tabで移動してEnter)のときも戻しておく
            element.addEventListener("focus", show);

            element.addEventListener("mouseleave", show);
        });
    }

    /**
     * 全フォームの二重送信防止 (§13.3)
     * 送信後は submit ボタンを無効化する。
     */
    function preventDoubleSubmit() {
        document.querySelectorAll("form").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (form.dataset.submitted === "1") {
                    event.preventDefault();
                    return;
                }
                form.dataset.submitted = "1";

                form.querySelectorAll('button[type="submit"], input[type="submit"]')
                    .forEach(function (button) {
                        button.disabled = true;
                        button.setAttribute("aria-busy", "true");
                    });
            });
        });
    }

    /** data-confirm 属性を持つフォームへ確認ダイアログを付ける */
    function bindConfirmDialogs() {
        document.querySelectorAll("form[data-confirm]").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                var message = form.getAttribute("data-confirm") || "実行しますか？";
                if (!window.confirm(message)) {
                    event.preventDefault();
                    form.dataset.submitted = "";
                    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                        button.disabled = false;
                        button.removeAttribute("aria-busy");
                    });
                }
            });
        });
    }

    /** 成功フラッシュを6秒後にフェードアウトする */
    function autoDismissFlash() {
        document.querySelectorAll(".ph-alert--success[data-autodismiss]").forEach(function (el) {
            window.setTimeout(function () {
                el.style.transition = "opacity 0.4s ease";
                el.style.opacity = "0";
                window.setTimeout(function () {
                    el.remove();
                }, 450);
            }, 6000);
        });
    }
})();
