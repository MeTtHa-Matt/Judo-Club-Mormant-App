document.addEventListener("DOMContentLoaded", function () {
  let currentPage = window.location.pathname.split("/").pop();

  if (currentPage === "") {
    currentPage = "index.php";
  }

  const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
  const activeClass = "active-judo"; 

  navLinks.forEach((link) => {
    const linkPage = link.getAttribute("href");

    link.classList.remove(activeClass);
    link.removeAttribute("aria-current");

    if (linkPage === currentPage) {
      link.classList.add(activeClass);
      link.setAttribute("aria-current", "page");
    }
  });
});
