document.addEventListener("DOMContentLoaded", function () {
  const usersResults = document.getElementById("usersResults");

  const refreshUsers = (query) => {
    const params = new URLSearchParams({ ajax: "1", q: query || "" });
    return fetch("users.php?" + params.toString(), {
      method: "GET",
      headers: { Accept: "application/json" },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success && typeof data.html === "string") {
          usersResults.innerHTML = data.html;
          attachCardListeners();
        }
      });
  };

  const sendUserAction = (userId, action) => {
    return fetch("users.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({ action, user_id: userId, ajax: "1", csrf_token: window.JCM?.csrfToken || "" }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          throw new Error(data.message || "Action échouée");
        }
        return data;
      });
  };

  const updateCardState = (card, userId) => {
    const header = card.querySelector(".user-card-header");
    if (!header) return;
    const isOpen = card.classList.contains("open");
    header.setAttribute("aria-expanded", isOpen ? "true" : "false");
  };

  const attachCardListeners = () => {
    const cards = document.querySelectorAll(".user-card");

    cards.forEach((card) => {
      const header = card.querySelector(".user-card-header");
      const body = card.querySelector(".user-card-body");
      if (!header || !body) return;

      const toggleCard = () => {
        card.classList.toggle("open");
        updateCardState(card);
      };

      header.addEventListener("click", toggleCard);
      header.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          toggleCard();
        }
      });

      const actionButtons = card.querySelectorAll(".btn-user-action");
      actionButtons.forEach((button) => {
        const userId = button.dataset.userId;
        const action = button.dataset.userAction;
        if (!userId || !action) return;

        button.addEventListener("click", (event) => {
          event.stopPropagation();
          if (button.disabled) return;

          if (action === "delete_account") {
            const userName = button.closest(".user-card")?.querySelector(".fw-bold")?.textContent?.trim() || "ce compte";
            const confirmed = window.confirm(`Voulez-vous vraiment supprimer ${userName} ?`);
            if (!confirmed) return;
          }

          button.disabled = true;
          button.classList.add("opacity-75");

          sendUserAction(userId, action)
            .then(() =>
              refreshUsers(
                document.getElementById("usersSearchInput")?.value || "",
              ),
            )
            .catch((error) => {
              console.error(error);
              alert(error.message);
              button.disabled = false;
              button.classList.remove("opacity-75");
            });
        });
      });
    });
  };

  window.attachUserActions = attachCardListeners;
  attachCardListeners();
});
