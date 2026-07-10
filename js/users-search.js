document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("usersSearchInput");
  const searchForm = document.getElementById("usersSearchForm");
  const resultsContainer = document.getElementById("usersResults");
  let debounceTimer = null;

  async function updateUserResults(query) {
    const url = new URL(searchForm.action, window.location.origin);
    url.searchParams.set("ajax", "1");
    url.searchParams.set("q", query);

    try {
      const response = await fetch(url.toString(), {
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error("Erreur réseau");
      }

      const text = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (jsonError) {
        console.error(
          "Recherche utilisateur AJAX échouée: réponse non JSON",
          text,
        );
        return;
      }

      if (data.success && data.html) {
        resultsContainer.innerHTML = data.html;
        if (typeof window.attachUserActions === "function") {
          window.attachUserActions();
        }
      }
    } catch (error) {
      console.error("Recherche utilisateur AJAX échouée:", error);
    }
  }

  function scheduleSearch() {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(() => {
      updateUserResults(searchInput.value.trim());
    }, 250);
  }

  if (searchInput && searchForm && resultsContainer) {
    searchForm.addEventListener("submit", function (event) {
      event.preventDefault();
      updateUserResults(searchInput.value.trim());
    });

    searchInput.addEventListener("input", scheduleSearch);
  }
});
