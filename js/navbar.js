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

  if (toggler && collapse) {
    toggler.addEventListener("click", function (event) {
      event.preventDefault();
      const isExpanded = toggler.getAttribute("aria-expanded") === "true";
      toggler.setAttribute("aria-expanded", String(!isExpanded));
      collapse.classList.toggle("show", !isExpanded);
    });

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

  if (profileToggle && profileMenu) {
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
      } else {
        profileMenu.classList.remove("show");
        profileToggle.setAttribute("aria-expanded", "false");
      }
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
