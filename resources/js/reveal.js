// Scroll reveal for elements marked [data-reveal]: only when motion is allowed.
export function startReveal() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
    document.documentElement.classList.add('js-motion');
    const io = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        }
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    const watch = (root) => root.querySelectorAll?.('[data-reveal]:not(.is-visible)').forEach((el) => io.observe(el));
    watch(document);
    new MutationObserver((mutations) => {
        for (const m of mutations) m.addedNodes.forEach((node) => { if (node.nodeType === 1) { if (node.matches('[data-reveal]')) io.observe(node); watch(node); } });
    }).observe(document.body, { childList: true, subtree: true });
}
