// Back-office helpers (Alpine components + htmx glue).
document.addEventListener("htmx:configRequest", function (event) {
  var token = document.querySelector("meta[name=csrf-token]");
  if (token) event.detail.headers["X-CSRFToken"] = token.content;
});

document.addEventListener("alpine:init", function () {
  // Dynamic inline formsets: clones the empty form and bumps TOTAL_FORMS.
  window.Alpine.data("formset", function (prefix) {
    return {
      prefix: prefix,
      add: function () {
        var total = this.$root.querySelector("#id_" + prefix + "-TOTAL_FORMS");
        var tpl = this.$root.querySelector("template[data-empty-form]");
        var index = parseInt(total.value, 10);
        var html = tpl.innerHTML.replace(/__prefix__/g, index);
        var holder = document.createElement("div");
        holder.innerHTML = html.trim();
        var node = holder.firstElementChild;
        this.$root.querySelector("[data-forms]").appendChild(node);
        total.value = index + 1;
        var first = node.querySelector("input:not([type=hidden]),textarea,select");
        if (first) first.focus();
      },
    };
  });

  // Slug auto-fill from the title while the slug was not edited by hand.
  window.Alpine.data("slugify", function () {
    return {
      touched: false,
      init: function () {
        var form = this.$root;
        var source = form.querySelector("[name=title],[name=name]");
        var slug = form.querySelector("[name=slug]");
        if (!source || !slug) return;
        var self = this;
        self.touched = slug.value !== "";
        slug.addEventListener("input", function () { self.touched = true; });
        source.addEventListener("input", function () {
          if (self.touched) return;
          slug.value = source.value.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase()
            .replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").slice(0, 120);
        });
      },
    };
  });

  // Live character counters for SEO fields.
  window.Alpine.data("counter", function (max) {
    return { count: 0, max: max, init: function () { var el = this.$root.querySelector("input,textarea"); var self = this; if (!el) return; self.count = el.value.length; el.addEventListener("input", function () { self.count = el.value.length; }); } };
  });
});

// Trix: refuse file attachments (images go through dedicated, validated upload fields).
document.addEventListener("trix-file-accept", function (event) { event.preventDefault(); });

// Warn before leaving a modified form.
(function () {
  var dirty = false;
  document.addEventListener("input", function (e) { if (e.target.closest("form[data-guard]")) dirty = true; });
  document.addEventListener("trix-change", function (e) { if (e.target.closest("form[data-guard]")) dirty = true; });
  document.addEventListener("submit", function () { dirty = false; });
  window.addEventListener("beforeunload", function (e) { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
})();
