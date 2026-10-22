// Public site behaviour. Kept tiny on purpose (slow connections first).
(function () {
  "use strict";
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Scroll reveal, only when motion is allowed and IntersectionObserver exists.
  if (!reduce && "IntersectionObserver" in window) {
    document.documentElement.classList.add("js-motion");
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
    document.querySelectorAll("[data-reveal]").forEach(function (el) { observer.observe(el); });
  }

  // Header shadow once the page is scrolled.
  var header = document.querySelector("[data-header]");
  if (header) {
    var onScroll = function () { header.toggleAttribute("data-scrolled", window.scrollY > 8); };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  // Copy-link buttons (article sharing).
  document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-copy]");
    if (!button || !navigator.clipboard) return;
    navigator.clipboard.writeText(button.getAttribute("data-copy")).then(function () {
      var label = button.querySelector("[data-copy-label]");
      if (label) { var old = label.textContent; label.textContent = "Lien copié"; setTimeout(function () { label.textContent = old; }, 1800); }
    });
  });

  // Send the CSRF token with every htmx request.
  document.addEventListener("htmx:configRequest", function (event) {
    var token = document.querySelector("meta[name=csrf-token]");
    if (token) event.detail.headers["X-CSRFToken"] = token.content;
  });
})();
