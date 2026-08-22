document.addEventListener("DOMContentLoaded", function () {
  let currentPage = window.location.pathname.split("/").pop();

  if (currentPage === "") {
    currentPage = "index.php";
  }

  const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
  const activeClass = "active-judo";
  const toggler = document.querySelector(".navbar-toggler");
  const collapse = document.querySelector(".navbar-collapse");
  const profileToggle = document.getElementById("profileDropdownToggle");
  const profileMenu = document.querySelector(".profile-menu-judo");

  navLinks.forEach((link) => {
    const linkPage = link.getAttribute("href");

    link.classList.remove(activeClass);
    link.removeAttribute("aria-current");

    if (linkPage === currentPage) {
      link.classList.add(activeClass);
      link.setAttribute("aria-current", "page");
    }
  });

  function setupCollapseBehavior() {
    if (!toggler || !collapse) return;

    // If Bootstrap's Collapse API is available, use it to avoid conflicts
    let collapseInstance = null;
    if (window.bootstrap && window.bootstrap.Collapse) {
      collapseInstance = window.bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false });

      toggler.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        collapseInstance.toggle();
      });

      // Progressive open/close animation for menu content only
      // (exclude brand/toggler so header controls remain visible)
      const headerElements = Array.from(
        collapse.querySelectorAll('.nav-item, .navbar-buttons-custom, .profile-dropdown-judo')
      ).filter(Boolean);
      const navbarRoot = document.querySelector('.custom-navbar');

      let _animTimeouts = [];

      function clearAnimTimeouts() {
        _animTimeouts.forEach(t => clearTimeout(t));
        _animTimeouts = [];
      }

      function progressiveOpen() {
        clearAnimTimeouts();
        headerElements.forEach((el, i) => {
          el.classList.add('animated-header-item');
          const t = setTimeout(() => el.classList.add('visible'), i * 70);
          _animTimeouts.push(t);
        });
      }

      function progressiveClose() {
        clearAnimTimeouts();
        // reverse order
        headerElements.slice().reverse().forEach((el, i) => {
          const t = setTimeout(() => el.classList.remove('visible'), i * 50);
          _animTimeouts.push(t);
        });
      }

      // Run progressive animations after collapse is fully shown/hidden
      collapse.addEventListener('shown.bs.collapse', function () {
        toggler.setAttribute('aria-expanded', 'true');
        // add a class to the navbar to animate its background smoothly
        if (navbarRoot) navbarRoot.classList.add('menu-open');
        progressiveOpen();
      });

      collapse.addEventListener('hidden.bs.collapse', function () {
        toggler.setAttribute('aria-expanded', 'false');
        if (navbarRoot) navbarRoot.classList.remove('menu-open');
        progressiveClose();
      });

      collapse.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", function () {
          collapseInstance.hide();
        });
      });

      document.addEventListener("click", function (event) {
        if (!collapse.contains(event.target) && !toggler.contains(event.target)) {
          collapseInstance.hide();
        }
      });
    } else {
      // Fallback if Bootstrap isn't loaded yet: use previous manual handling
      toggler.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        const isExpanded = toggler.getAttribute("aria-expanded") === "true";
        toggler.setAttribute("aria-expanded", String(!isExpanded));
        collapse.classList.toggle("show", !isExpanded);
      });


      // Fallback progressive open/close (only menu content)
      const headerElementsFallback = Array.from(
        collapse.querySelectorAll('.nav-item, .navbar-buttons-custom, .profile-dropdown-judo')
      ).filter(Boolean);
      const navbarRootFallback = document.querySelector('.custom-navbar');

      let _fbTimeouts = [];
      function clearFB() { _fbTimeouts.forEach(t => clearTimeout(t)); _fbTimeouts = []; }
      function fbOpen() {
        clearFB();
        headerElementsFallback.forEach((el, i) => {
          el.classList.add('animated-header-item');
          const t = setTimeout(() => el.classList.add('visible'), i * 70);
          _fbTimeouts.push(t);
        });
      }
      function fbClose() {
        clearFB();
        headerElementsFallback.slice().reverse().forEach((el, i) => {
          const t = setTimeout(() => el.classList.remove('visible'), i * 50);
          _fbTimeouts.push(t);
        });
      }

      // observe show class for fallback and toggle navbar menu class
      const observer = new MutationObserver(() => {
        if (collapse.classList.contains('show')) {
          if (navbarRootFallback) navbarRootFallback.classList.add('menu-open');
          fbOpen();
        } else {
          if (navbarRootFallback) navbarRootFallback.classList.remove('menu-open');
          fbClose();
        }
      });
      observer.observe(collapse, { attributes: true, attributeFilter: ['class'] });

      collapse.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", function () {
          collapse.classList.remove("show");
          toggler.setAttribute("aria-expanded", "false");
        });
      });

      document.addEventListener("click", function (event) {
        if (!collapse.contains(event.target) && !toggler.contains(event.target)) {
          collapse.classList.remove("show");
          toggler.setAttribute("aria-expanded", "false");
        }
      });
    }
  }

  // If bootstrap is already loaded, set up immediately, otherwise wait for load
  if (window.bootstrap && window.bootstrap.Collapse) {
    setupCollapseBehavior();
  } else {
    window.addEventListener('load', function () {
      setupCollapseBehavior();
    });
  }
  

  if (profileToggle && profileMenu) {
    function positionProfileMenu() {
      // desktop only
      if (window.innerWidth <= 991.98) return;
      const rect = profileToggle.getBoundingClientRect();
      // ensure menu is visible to measure its width
      profileMenu.style.visibility = 'hidden';
      profileMenu.classList.add('show');
      // force layout and measure
      const menuWidth = profileMenu.offsetWidth || profileMenu.getBoundingClientRect().width || 260;
      // compute viewport-based coordinates (no scroll offset for fixed)
      let leftPx = Math.round(rect.left + rect.width / 2 - menuWidth / 2);
      const topPx = Math.round(rect.bottom + 6);
      // clamp to viewport with small margin
      leftPx = Math.max(8, Math.min(leftPx, Math.round(window.innerWidth - menuWidth - 8)));
      // apply inline fixed positioning (important to override other rules)
      profileMenu.style.setProperty('position', 'fixed', 'important');
      profileMenu.style.setProperty('left', leftPx + 'px', 'important');
      profileMenu.style.setProperty('top', topPx + 'px', 'important');
      profileMenu.style.setProperty('right', 'auto', 'important');
      profileMenu.style.setProperty('transform', 'none', 'important');
      profileMenu.style.visibility = '';
    }
    profileToggle.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();

      const isOpen = profileMenu.classList.contains("show");
      document.querySelectorAll(".profile-menu-judo.show").forEach((menu) => {
        menu.classList.remove("show");
      });

      if (!isOpen) {
        profileMenu.classList.add("show");
        profileToggle.setAttribute("aria-expanded", "true");
        positionProfileMenu();
      } else {
        profileMenu.classList.remove("show");
        profileToggle.setAttribute("aria-expanded", "false");
        // remove inline positioning properties (including important)
        profileMenu.style.removeProperty('position');
        profileMenu.style.removeProperty('left');
        profileMenu.style.removeProperty('top');
        profileMenu.style.removeProperty('transform');
      }

    // Observe class changes to the menu (Bootstrap/Popper may modify styles after our click handler)
    const profileObserver = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.attributeName === 'class') {
          const isShown = profileMenu.classList.contains('show');
          if (isShown && window.innerWidth > 991.98) {
            // apply position after current microtask and after potential Popper adjustments
            setTimeout(() => {
              positionProfileMenu();
              // reapply on next frame as well to be safe
              requestAnimationFrame(positionProfileMenu);
            }, 0);
          }
        }
      }
    });
    profileObserver.observe(profileMenu, { attributes: true, attributeFilter: ['class', 'style'] });
    });

    profileMenu.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", function () {
        profileMenu.classList.remove("show");
        profileToggle.setAttribute("aria-expanded", "false");
      });
    });

    const closeProfileButton = profileMenu.querySelector(".profile-menu-close");
    if (closeProfileButton) {
      closeProfileButton.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        profileMenu.classList.remove("show");
        profileToggle.setAttribute("aria-expanded", "false");
      });
    }

    document.addEventListener("click", function (event) {
      if (
        !profileMenu.contains(event.target) &&
        !profileToggle.contains(event.target)
      ) {
        profileMenu.classList.remove("show");
        profileToggle.setAttribute("aria-expanded", "false");
      }
    });
  }
});
