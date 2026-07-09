document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("usersSearchInput");
  const searchForm = document.getElementById("usersSearchForm");
  const resultsContainer = document.getElementById("usersResults");
  let timeoutId;

  const fetchUsers = (query) => {
    const params = new URLSearchParams({ ajax: "1", q: query });
    fetch("users.php?" + params.toString(), {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success && typeof data.html === "string") {
          resultsContainer.innerHTML = data.html;
        }
      })
      .catch((error) => {
        console.error("Recherche utilisateur en direct échouée :", error);
      });
  };

  const handleSearch = () => {
    const query = searchInput.value.trim();
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fetchUsers(query), 250);
  };

  if (searchInput) {
    searchInput.addEventListener("input", handleSearch);
  }

  if (searchForm) {
    searchForm.addEventListener("submit", function (event) {
      event.preventDefault();
      handleSearch();
    });
  }
});
