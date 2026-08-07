/* =====================================================================
   Patron Hub - login-animation.js (AUTH-01)

   起動演出 (§13.3):
     - 読み込み完了後にカードを下からフェードイン
     - ブランドマーク → 見出し → 入力欄 → ボタン の順で段階表示
     - ログイン処理中はボタンを無効化しスピナー表示(二重送信防止)
     - prefers-reduced-motion 時は演出を行わない
   ===================================================================== */
(function () {
    "use strict";

    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    document.addEventListener("DOMContentLoaded", function () {
        var scene = document.querySelector(".login-scene");
        if (!scene) {
            return;
        }

        setupEntrance(scene);
        setupStagger(scene);
        setupSubmitState(scene);
        setupPasswordToggle(scene);
        setupErrorShake(scene);
    });

    /** 起動アニメーションの開始 */
    function setupEntrance(scene) {
        if (reduceMotion) {
            return; // 演出なし。CSSは即時表示のまま。
        }

        scene.classList.add("js-anim");

        // 2フレーム待ってから開始し、初期状態を確実に描画させる
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                scene.classList.add("is-ready");
            });
        });
    }

    /** 段階表示の順序を CSS変数 --stagger-i で指定する */
    function setupStagger(scene) {
        scene.querySelectorAll(".login-stagger").forEach(function (el, index) {
            el.style.setProperty("--stagger-i", String(index));
        });
    }

    /** 送信中のボタン無効化 + スピナー (§13.3) */
    function setupSubmitState(scene) {
        var form = scene.querySelector(".login-form");
        var submit = scene.querySelector(".login-submit");

        if (!form || !submit) {
            return;
        }

        form.addEventListener("submit", function () {
            // app.js 側の二重送信防止と併用。表示だけここで整える。
            submit.classList.add("is-loading");
            submit.setAttribute("aria-busy", "true");

            var label = submit.querySelector(".login-submit__label");
            if (label) {
                label.textContent = "ログイン中…";
            }
        });
    }

    /** パスワード表示切り替え */
    function setupPasswordToggle(scene) {
        var toggle = scene.querySelector(".login-password__toggle");
        var input = scene.querySelector('input[name="password"]');

        if (!toggle || !input) {
            return;
        }

        toggle.addEventListener("click", function () {
            var show = input.type === "password";
            input.type = show ? "text" : "password";
            toggle.setAttribute(
                "aria-label",
                show ? "パスワードを隠す" : "パスワードを表示する"
            );
            toggle.querySelectorAll("svg").forEach(function (icon) {
                icon.classList.toggle("d-none");
            });
            input.focus({ preventScroll: true });
        });
    }

    /** サーバー側エラーがある場合、カードを1回だけ揺らす */
    function setupErrorShake(scene) {
        if (reduceMotion) {
            return;
        }

        var card = scene.querySelector(".login-card");
        if (card && card.querySelector(".ph-field__error")) {
            card.classList.add("is-error");
            card.addEventListener("animationend", function () {
                card.classList.remove("is-error");
            }, { once: true });
        }
    }
})();
