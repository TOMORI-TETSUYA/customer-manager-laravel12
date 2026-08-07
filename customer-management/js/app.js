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
    });

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
