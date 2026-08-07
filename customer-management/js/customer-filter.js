/* =====================================================================
   Patron Hub - customer-filter.js (CUS-01)
   フィルターの開閉 / 表示件数・並び替え変更時の自動送信
   ===================================================================== */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        autoSubmitSelects();
        rememberFilterState();
    });

    /** 表示件数・並び替えのセレクトは変更時に即検索する */
    function autoSubmitSelects() {
        document.querySelectorAll("[data-autosubmit]").forEach(function (select) {
            select.addEventListener("change", function () {
                var form = select.form;
                if (form && form.dataset.submitted !== "1") {
                    form.requestSubmit();
                }
            });
        });
    }

    /**
     * 詳細フィルターの開閉状態を同一セッション内で維持する。
     * いずれかのフィルターに値がある場合は最初から開く。
     */
    function rememberFilterState() {
        var collapse = document.getElementById("customerFilter");
        if (!collapse || typeof bootstrap === "undefined") {
            return;
        }

        var hasValue = Array.prototype.some.call(
            collapse.querySelectorAll("select, input"),
            function (el) {
                return el.value !== "" && el.type !== "checkbox";
            }
        );

        var stored = window.sessionStorage.getItem("ph-filter-open");

        if (hasValue || stored === "1") {
            bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
        }

        collapse.addEventListener("shown.bs.collapse", function () {
            window.sessionStorage.setItem("ph-filter-open", "1");
        });

        collapse.addEventListener("hidden.bs.collapse", function () {
            window.sessionStorage.setItem("ph-filter-open", "0");
        });
    }
})();
