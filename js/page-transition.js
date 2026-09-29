(function () {
  const loader = document.getElementById("pageLoader");
  if (!loader) return;

  let hideTimer;

  function showLoader() {
    window.clearTimeout(hideTimer);
    loader.classList.add("is-visible");
    loader.setAttribute("aria-hidden", "false");
    hideTimer = window.setTimeout(hideLoader, 15000);
  }

  function hideLoader() {
    window.clearTimeout(hideTimer);
    loader.classList.remove("is-visible");
    loader.setAttribute("aria-hidden", "true");
  }

  document.addEventListener("click", function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target.closest("a[href]");
    if (!link || link.hasAttribute("download") || link.hasAttribute("data-no-page-loader")) return;
    if (link.target && link.target.toLowerCase() !== "_self") return;

    let destination;
    try {
      destination = new URL(link.href, window.location.href);
    } catch {
      return;
    }

    if (destination.origin !== window.location.origin) return;
    if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;

    showLoader();
  });

  document.addEventListener("submit", function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || (form.target && form.target.toLowerCase() !== "_self")) return;
    if (form.hasAttribute("data-no-page-loader")) return;

    let destination;
    try {
      destination = new URL(form.action || window.location.href, window.location.href);
    } catch {
      return;
    }

    if (destination.origin === window.location.origin) showLoader();
  });

  window.addEventListener("pageshow", hideLoader);
  window.addEventListener("pagehide", function () {
    window.clearTimeout(hideTimer);
  });
})();