/* =====================================================================
   Patron Hub - form-validation.js (CUS-02 / CUS-04)
   顧客区分による必須切り替え表示 / 電話番号の正規化プレビュー
   ※最終的な入力チェックはサーバー側 Form Request が行う (§2.2)
   ===================================================================== */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var form = document.querySelector("[data-customer-form]");
        if (!form) {
            return;
        }

        setupTypeSwitch(form);
        setupPhonePreview(form);
    });

    /**
     * 顧客区分の切り替え (§17.1 / §17.2)
     *   個人: 顧客名が必須 / 法人: 会社名が必須・法人担当者名を表示
     */
    function setupTypeSwitch(form) {
        var radios = form.querySelectorAll('input[name="customer_type"]');
        if (radios.length === 0) {
            return;
        }

        function apply() {
            var checked = form.querySelector('input[name="customer_type"]:checked');
            var type = checked ? checked.value : "individual";
            var isCorporate = type === "corporate";

            toggleRequired(form, "customer_name", !isCorporate);
            toggleRequired(form, "company_name", isCorporate);

            form.querySelectorAll("[data-corporate-only]").forEach(function (el) {
                el.hidden = !isCorporate;
            });
        }

        radios.forEach(function (radio) {
            radio.addEventListener("change", apply);
        });

        apply();
    }

    function toggleRequired(form, name, required) {
        var input = form.querySelector('[name="' + name + '"]');
        if (!input) {
            return;
        }

        input.required = required;

        var field = input.closest(".ph-field");
        if (field) {
            var mark = field.querySelector(".is-required");
            if (mark) {
                mark.hidden = !required;
            }
        }
    }

    /** 電話番号入力の正規化プレビュー (§18.2: 保存時は数字のみへ正規化) */
    function setupPhonePreview(form) {
        var phone = form.querySelector('input[name="phone"]');
        var preview = form.querySelector("[data-phone-preview]");

        if (!phone || !preview) {
            return;
        }

        function render() {
            var normalized = phone.value
                .replace(/[０-９]/g, function (ch) {
                    return String.fromCharCode(ch.charCodeAt(0) - 0xfee0);
                })
                .replace(/[^0-9]/g, "");

            preview.textContent = normalized === ""
                ? ""
                : "保存される番号: " + normalized;
        }

        phone.addEventListener("input", render);
        render();
    }
})();
