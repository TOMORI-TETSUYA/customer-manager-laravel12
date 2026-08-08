/* =====================================================================
   Patron Hub - user-form.js (USR-01)
   ユーザー管理: パスワード自動生成 / 表示切替 / 3項目の一括コピー

   ・生成した平文はサーバーへ送信されるまでブラウザ内にのみ存在する。
   ・保存時は Argon2id でハッシュ化されるため、一覧で平文を表示できるのは
     作成直後の1回だけ(サーバー側のフラッシュデータ)。
   ・コピーはボタン1つで「ログインID・パスワード・表示名」をまとめて写す。
   ===================================================================== */
(function () {
    "use strict";

    /* 生成に使う文字種。
       紛らわしい文字は除外している(大文字I/O・小文字l/o・数字0/1)。
       記号は CSV やメール本文で壊れにくいものだけを採用。 */
    var CHARSET_UPPER  = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    var CHARSET_LOWER  = "abcdefghijkmnpqrstuvwxyz";
    var CHARSET_DIGIT  = "23456789";
    var CHARSET_SYMBOL = "!#%+-=?@_";

    /* サーバー側の検証は 12文字以上・英字と数字を含むこと。
       余裕を持たせて16文字で生成する。 */
    var PASSWORD_LENGTH = 16;

    document.addEventListener("DOMContentLoaded", function () {
        bindBundleCopy();
        bindPasswordToggle();
        bindPasswordGenerate();
    });

    /* -----------------------------------------------------------------
       一括コピー
       ----------------------------------------------------------------- */

    /**
     * data-copy-bundle を持つボタンにコピー動作を付ける。
     *   "form" : 入力欄(#login_id / #password / #name)から集める
     *   それ以外: ボタンの data-copy-* 属性から集める(一覧で使用)
     */
    function bindBundleCopy() {
        document.querySelectorAll("[data-copy-bundle]").forEach(function (button) {
            button.addEventListener("click", function () {
                var text = buildBundleText(button);

                if (text === null) {
                    flashButton(button, "未入力です");
                    return;
                }

                copyText(text).then(function () {
                    flashButton(button, "コピーしました");
                }, function () {
                    flashButton(button, "失敗しました");
                });
            });
        });
    }

    /**
     * 貼り付けたときにそのまま読める形へ組み立てる。
     * 3項目すべてが空なら null を返す。
     */
    function buildBundleText(button) {
        var loginId;
        var password;
        var name;

        if (button.getAttribute("data-copy-bundle") === "form") {
            loginId  = inputValue("login_id");
            password = inputValue("password");
            name     = inputValue("name");
        } else {
            loginId  = button.getAttribute("data-copy-login") || "";
            password = button.getAttribute("data-copy-password") || "";
            name     = button.getAttribute("data-copy-name") || "";
        }

        if (loginId === "" && password === "" && name === "") {
            return null;
        }

        return "ログインID: " + loginId + "\n"
            + "パスワード: " + password + "\n"
            + "表示名: " + name;
    }

    function inputValue(id) {
        var element = document.getElementById(id);

        return element && typeof element.value === "string" ? element.value : "";
    }

    /**
     * クリップボードへ書き込む。
     * navigator.clipboard は https か localhost でしか使えないため、
     * 使えない場合は一時的な textarea + execCommand で代替する。
     */
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var area = document.createElement("textarea");
            area.value = text;
            area.setAttribute("readonly", "readonly");
            area.style.position = "fixed";
            area.style.top = "-1000px";
            area.style.opacity = "0";

            document.body.appendChild(area);
            area.select();

            var succeeded = false;
            try {
                succeeded = document.execCommand("copy");
            } catch (error) {
                succeeded = false;
            }

            document.body.removeChild(area);

            if (succeeded) {
                resolve();
            } else {
                reject(new Error("copy failed"));
            }
        });
    }

    /** ボタンのラベルを一時的に差し替えて結果を知らせる */
    function flashButton(button, message) {
        if (button.dataset.originalLabel === undefined) {
            button.dataset.originalLabel = button.textContent;
        }

        button.textContent = message;
        button.classList.add("is-done");

        window.clearTimeout(Number(button.dataset.resetTimer));
        button.dataset.resetTimer = String(window.setTimeout(function () {
            button.textContent = button.dataset.originalLabel;
            button.classList.remove("is-done");
        }, 1800));
    }

    /* -----------------------------------------------------------------
       表示切替
       ----------------------------------------------------------------- */

    /** パスワード欄の伏字表示と平文表示を切り替える */
    function bindPasswordToggle() {
        var toggle = document.querySelector("[data-password-toggle]");
        var input = document.querySelector("[data-password-input]");

        if (!toggle || !input) {
            return;
        }

        toggle.addEventListener("click", function () {
            var willShow = input.type === "password";

            input.type = willShow ? "text" : "password";
            toggle.textContent = willShow ? "隠す" : "表示";
            toggle.setAttribute("aria-pressed", willShow ? "true" : "false");
            toggle.setAttribute(
                "aria-label",
                willShow ? "パスワードを隠す" : "パスワードを表示"
            );
        });
    }

    /* -----------------------------------------------------------------
       自動生成
       ----------------------------------------------------------------- */

    function bindPasswordGenerate() {
        var button = document.querySelector("[data-password-generate]");
        var input = document.querySelector("[data-password-input]");
        var status = document.querySelector("[data-password-status]");

        if (!button || !input) {
            return;
        }

        button.addEventListener("click", function () {
            if (!hasSecureRandom()) {
                setStatus(status, "このブラウザでは自動生成を利用できません。手入力してください。", true);
                return;
            }

            input.value = generatePassword();

            // 生成した値は確認できないと控えられないため、表示状態にする。
            input.type = "text";

            var toggle = document.querySelector("[data-password-toggle]");
            if (toggle) {
                toggle.textContent = "隠す";
                toggle.setAttribute("aria-pressed", "true");
                toggle.setAttribute("aria-label", "パスワードを隠す");
            }

            setStatus(status, PASSWORD_LENGTH + "文字のパスワードを生成しました。", false);
        });
    }

    function setStatus(element, message, isError) {
        if (!element) {
            return;
        }

        element.textContent = message;
        element.classList.toggle("is-error", isError === true);
    }

    function hasSecureRandom() {
        return typeof window.crypto !== "undefined"
            && typeof window.crypto.getRandomValues === "function";
    }

    /**
     * パスワードを生成する。
     * 4種すべてから最低1文字ずつ採用し、残りを全体から選んでシャッフルする
     * (英字と数字を必ず含めるサーバー側の検証を確実に満たすため)。
     */
    function generatePassword() {
        var all = CHARSET_UPPER + CHARSET_LOWER + CHARSET_DIGIT + CHARSET_SYMBOL;
        var chars = [
            pickChar(CHARSET_UPPER),
            pickChar(CHARSET_LOWER),
            pickChar(CHARSET_DIGIT),
            pickChar(CHARSET_SYMBOL)
        ];

        while (chars.length < PASSWORD_LENGTH) {
            chars.push(pickChar(all));
        }

        return shuffle(chars).join("");
    }

    function pickChar(charset) {
        return charset.charAt(randomInt(charset.length));
    }

    /**
     * 0以上 maxExclusive 未満の整数を偏りなく返す。
     * Math.random は暗号用途に使えないため crypto を用い、
     * 剰余による偏りが出る範囲の値は捨てて引き直す。
     */
    function randomInt(maxExclusive) {
        var range = 4294967296; // 2^32
        var limit = Math.floor(range / maxExclusive) * maxExclusive;
        var buffer = new Uint32Array(1);
        var value;

        do {
            window.crypto.getRandomValues(buffer);
            value = buffer[0];
        } while (value >= limit);

        return value % maxExclusive;
    }

    /** Fisher-Yates シャッフル */
    function shuffle(items) {
        for (var i = items.length - 1; i > 0; i--) {
            var j = randomInt(i + 1);
            var temp = items[i];
            items[i] = items[j];
            items[j] = temp;
        }

        return items;
    }
})();
