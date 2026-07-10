const modalCompetition = document.getElementById("modalCompetition");
const modalInscriptionEl = document.getElementById("modalInscription");
const modalInscritsEl = document.getElementById("modalInscrits");
const modalRegistrationChoiceEl = document.getElementById(
  "modalRegistrationChoice",
);
const modalChildProfilesEl = document.getElementById("modalChildProfiles");
const modalChildProfilesContent = document.getElementById(
  "modalChildProfilesContent",
);
const registerBtn = document.getElementById("modalCompetitionRegisterBtn");
const choiceManualBtn = document.getElementById("choiceManualInscriptionBtn");
const choiceChildBtn = document.getElementById("choiceChildInscriptionBtn");
const viewAllBtn = document.getElementById(
  "modalCompetitionViewInscritsAllBtn",
);
const viewMineBtn = document.getElementById(
  "modalCompetitionViewInscritsMineBtn",
);
let currentCompetition = {
  id: "",
  name: "",
};
let activeChildCompetitionId = "";

function hideModalAndThen(currentModalEl, showModalEl) {
  const currentModal = bootstrap.Modal.getInstance(currentModalEl);
  if (!currentModal) {
    if (showModalEl) {
      new bootstrap.Modal(showModalEl).show();
    }
    return;
  }

  const onHidden = () => {
    currentModalEl.removeEventListener("hidden.bs.modal", onHidden);
    if (showModalEl) {
      new bootstrap.Modal(showModalEl).show();
    }
  };

  currentModalEl.addEventListener("hidden.bs.modal", onHidden);
  currentModal.hide();
}

async function fetchAndShowInscrits(id, onlyMe = false, btn = null) {
  if (!id) return;

  try {
    if (btn) {
      btn.disabled = true;
      btn.dataset.orig = btn.textContent;
      btn.textContent = "Chargement...";
    }

    const body =
      "id_competition=" + encodeURIComponent(id) + (onlyMe ? "&only_me=1" : "");
    const resp = await fetch("includes/competitions/get_inscrits.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body,
    });
    const data = await resp.json();

    if (data.success) {
      if (modalInscritsEl) {
        modalInscritsEl.querySelector(".modal-body").innerHTML = data.html;
        const competitionModal = bootstrap.Modal.getInstance(modalCompetition);
        if (competitionModal && modalCompetition.classList.contains("show")) {
          competitionModal.hide();
        }
        const inscritsModal =
          bootstrap.Modal.getOrCreateInstance(modalInscritsEl);
        if (!modalInscritsEl.classList.contains("show")) {
          inscritsModal.show();
        }
      }
    } else {
      alert(data.message || "Erreur");
    }
  } catch (err) {
    console.error(err);
    alert("Erreur réseau");
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = btn.dataset.orig || btn.textContent;
    }
  }
}

document.addEventListener("click", async function (event) {
  const button = event.target.closest(".unsubscribe-inscription-btn");
  if (!button) return;

  event.preventDefault();
  const inscriptionId = button.dataset.inscriptionId;
  const competitionId = button.dataset.competitionId;
  const onlyMe = button.dataset.onlyMe === "1";

  if (!inscriptionId || !competitionId) return;

  const originalText = button.textContent;
  button.disabled = true;
  button.textContent = "Suppression...";

  try {
    const body =
      "id_inscrit=" +
      encodeURIComponent(inscriptionId) +
      "&id_competition=" +
      encodeURIComponent(competitionId) +
      "&only_me=" +
      encodeURIComponent(onlyMe ? "1" : "0");

    const resp = await fetch("includes/competitions/unsubscribe_process.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
    });
    const data = await resp.json();

    if (data.success) {
      await fetchAndShowInscrits(competitionId, onlyMe);
    } else if (data.deadline_passed) {
      const deadlineModal = document.getElementById(
        "modalUnsubscribeDeadlinePassed",
      );
      if (deadlineModal) {
        const currentModal = bootstrap.Modal.getInstance(modalInscritsEl);
        if (currentModal && modalInscritsEl.classList.contains("show")) {
          currentModal.hide();
        }
        new bootstrap.Modal(deadlineModal).show();
      } else {
        alert(
          data.message ||
            "Veuillez prévenir le coach que la date butoir de désinscription est dépassée.",
        );
      }
    } else {
      alert(data.message || "Erreur lors de la désinscription.");
    }
  } catch (err) {
    console.error(err);
    alert("Erreur réseau.");
  } finally {
    button.disabled = false;
    button.textContent = originalText;
  }
});

modalCompetition.addEventListener("show.bs.modal", (event) => {
  const trigger = event.relatedTarget;
  if (!trigger) return;

  const nom = trigger.getAttribute("data-nom") || "";
  const lieu = trigger.getAttribute("data-lieu") || "";
  const date = trigger.getAttribute("data-date") || "";
  const cible = trigger.getAttribute("data-cible") || "";
  const infos = trigger.getAttribute("data-informations") || "";
  const image = trigger.getAttribute("data-image") || "";
  const registrationOpen =
    trigger.getAttribute("data-registration-open") === "1";

  currentCompetition.id = trigger.getAttribute("data-id") || "";
  currentCompetition.name = nom;

  document.getElementById("modalCompetitionNom").textContent = nom;
  document.getElementById("modalCompetitionDate").textContent = date;

  const lieuWrapper = document.getElementById("modalCompetitionLieuWrapper");
  if (lieu) {
    document.getElementById("modalCompetitionLieu").textContent = lieu;
    lieuWrapper.classList.remove("d-none");
  } else {
    lieuWrapper.classList.add("d-none");
  }

  const cibleWrapper = document.getElementById("modalCompetitionCibleWrapper");
  if (cible) {
    document.getElementById("modalCompetitionCible").textContent = cible;
    cibleWrapper.classList.remove("d-none");
  } else {
    cibleWrapper.classList.add("d-none");
  }

  const infosWrapper = document.getElementById("modalCompetitionInfosWrapper");
  if (infos) {
    document.getElementById("modalCompetitionInfos").textContent = infos;
    infosWrapper.classList.remove("d-none");
  } else {
    infosWrapper.classList.add("d-none");
  }

  const imageEl = document.getElementById("modalCompetitionImage");
  if (image) {
    imageEl.src = image;
    imageEl.alt = nom;
    imageEl.classList.remove("d-none");
  } else {
    imageEl.classList.add("d-none");
  }

  const modalCompIdInput = document.getElementById("modalCompetitionId");
  if (modalCompIdInput) modalCompIdInput.value = currentCompetition.id;

  const bodyIsAdmin = document.body.dataset.isAdmin === "1";
  const isClosed = !registrationOpen && !bodyIsAdmin;
  const hasMyInscription =
    trigger.getAttribute("data-has-my-inscription") === "1";

  const registerLink = document.getElementById("modalCompetitionRegisterLink");

  if (registerLink) {
    if (isClosed) {
      registerLink.textContent = "Inscriptions fermées";
      registerLink.classList.remove("btn-judo-outline-white");
      registerLink.classList.add("btn-secondary", "disabled");
      registerLink.removeAttribute("href");
      registerLink.setAttribute("aria-disabled", "true");
      registerLink.style.pointerEvents = "none";
    } else {
      registerLink.textContent = "S'inscrire";
      registerLink.classList.remove("btn-secondary", "disabled");
      registerLink.classList.add("btn-judo-outline-white");
      registerLink.setAttribute("href", "register.php");
      registerLink.removeAttribute("aria-disabled");
      registerLink.style.pointerEvents = "";
    }
  }

  if (viewMineBtn) {
    viewMineBtn.classList.toggle("d-none", !hasMyInscription);
  }

  if (registerBtn) {
    if (isClosed) {
      registerBtn.textContent = "Inscriptions fermées";
      registerBtn.disabled = true;
      registerBtn.classList.remove("btn-judo-red");
      registerBtn.classList.add("btn-secondary", "disabled");
      registerBtn.setAttribute("aria-disabled", "true");
      registerBtn.style.pointerEvents = "none";
      registerBtn.onclick = null;
    } else {
      registerBtn.textContent = "S'inscrire";
      registerBtn.disabled = false;
      registerBtn.classList.remove("btn-secondary", "disabled");
      registerBtn.classList.add("btn-judo-red");
      registerBtn.removeAttribute("aria-disabled");
      registerBtn.style.pointerEvents = "";
      registerBtn.onclick = function (e) {
        e.preventDefault();
        if (!modalRegistrationChoiceEl) return;
        document.getElementById(
          "modalRegistrationChoiceCompetitionName",
        ).textContent = currentCompetition.name;
        document.getElementById("registrationChoiceCompetitionId").value =
          currentCompetition.id;
        hideModalAndThen(modalCompetition, modalRegistrationChoiceEl);
      };
    }
  }
});

if (viewAllBtn) {
  viewAllBtn.addEventListener("click", function (e) {
    e.preventDefault();
    fetchAndShowInscrits(currentCompetition.id, false, viewAllBtn);
  });
}

if (viewMineBtn) {
  viewMineBtn.addEventListener("click", function (e) {
    e.preventDefault();
    fetchAndShowInscrits(currentCompetition.id, true, viewMineBtn);
  });
}

async function fetchChildProfiles(competitionId) {
  try {
    const body = "competition_id=" + encodeURIComponent(competitionId);
    const resp = await fetch("includes/account/get_children.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
    });
    return await resp.json();
  } catch (error) {
    console.error(error);
    return { success: false, message: "Erreur réseau." };
  }
}

function buildChildProfileRow(child) {
  const registered = child.registered ? true : false;
  const buttonLabel = registered ? "Désinscrire" : "Inscrire";
  const buttonClass = registered
    ? "btn btn-outline-danger btn-sm"
    : "btn btn-judo-red btn-sm";

  return `
    <div class="d-flex align-items-center justify-content-between gap-3 py-3 border-bottom">
      <div>
        <div class="fw-semibold">${child.firstname} ${child.lastname}</div>
        <div class="small text-muted">Né en ${child.annee_naissance} · ${child.ceinture}${child.Poids ? " · " + child.Poids + " kg" : ""}</div>
      </div>
      <button type="button" class="${buttonClass} child-profile-action-btn" data-child-id="${child.id}" data-registered="${registered ? 1 : 0}">
        ${buttonLabel}
      </button>
    </div>
  `;
}

async function toggleChildRegistration(childId, competitionId, button) {
  if (!childId || !competitionId) return;
  button.disabled = true;
  const originalLabel = button.textContent;
  button.textContent = "...";

  try {
    const body =
      "competition_id=" +
      encodeURIComponent(competitionId) +
      "&child_id=" +
      encodeURIComponent(childId);
    const resp = await fetch(
      "includes/competitions/toggle_child_inscription.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      },
    );
    const data = await resp.json();
    if (data.success) {
      const registered = data.registered ? true : false;
      button.dataset.registered = registered ? "1" : "0";
      button.textContent = registered ? "Désinscrire" : "Inscrire";
      button.className = registered
        ? "btn btn-outline-danger btn-sm child-profile-action-btn"
        : "btn btn-judo-red btn-sm child-profile-action-btn";
    } else {
      button.textContent = originalLabel;
      alert(data.message || "Erreur lors de la mise à jour de l'inscription.");
    }
  } catch (error) {
    console.error(error);
    button.textContent = originalLabel;
    alert("Erreur réseau.");
  } finally {
    button.disabled = false;
  }
}

async function loadChildProfiles(competitionId) {
  if (!modalChildProfilesContent) return;
  modalChildProfilesContent.innerHTML = `
    <div class="text-center py-4">
      <div class="spinner-border text-judo-red" role="status"><span class="visually-hidden">Chargement...</span></div>
    </div>
  `;

  const data = await fetchChildProfiles(competitionId);
  if (!data.success) {
    modalChildProfilesContent.innerHTML = `<div class="text-center text-danger">${data.message || "Impossible de récupérer les profils."}</div>`;
    return;
  }

  if (!Array.isArray(data.children) || data.children.length === 0) {
    modalChildProfilesContent.innerHTML = `
      <div class="text-center py-4 text-muted">
        <i class="bi bi-people-fill display-4 text-judo-red"></i>
        <p class="mt-3 mb-0">Aucun profil enfant enregistré. Allez dans « Mes enfants » pour en créer un.</p>
      </div>
    `;
    return;
  }

  modalChildProfilesContent.innerHTML = data.children
    .map(buildChildProfileRow)
    .join("");
}

if (choiceManualBtn) {
  choiceManualBtn.addEventListener("click", () => {
    if (!modalInscriptionEl || !modalRegistrationChoiceEl) return;
    document.getElementById("modalInscriptionCompetitionName").textContent =
      currentCompetition.name;
    document.getElementById("inscription_competition_id").value =
      currentCompetition.id;
    hideModalAndThen(modalRegistrationChoiceEl, modalInscriptionEl);
  });
}

if (choiceChildBtn) {
  choiceChildBtn.addEventListener("click", async () => {
    if (!modalChildProfilesEl || !modalRegistrationChoiceEl) return;
    activeChildCompetitionId = currentCompetition.id;
    await loadChildProfiles(currentCompetition.id);
    hideModalAndThen(modalRegistrationChoiceEl, modalChildProfilesEl);
  });
}

if (modalChildProfilesContent) {
  modalChildProfilesContent.addEventListener("click", async (event) => {
    const button = event.target.closest(".child-profile-action-btn");
    if (!button) return;
    const childId = button.dataset.childId;
    await toggleChildRegistration(childId, activeChildCompetitionId, button);
  });
}

function bindUpcomingItems() {
  document.querySelectorAll(".upcoming-item").forEach((item) => {
    item.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        item.click();
      }
    });
  });
}

async function loadCalendarMonth(url, updateHistory = true) {
  const section = document.getElementById("calendrier");
  if (!section) return;

  try {
    const response = await fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    const html = await response.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    const newSection = doc.getElementById("calendrier");

    if (!newSection) {
      section.innerHTML =
        '<div class="alert alert-danger">Impossible de charger le mois demandé.</div>';
      return;
    }

    const importedSection = document.importNode(newSection, true);
    section.replaceWith(importedSection);

    if (updateHistory) {
      window.history.pushState({ calendarMonth: true, url }, "", url);
    }

    bindCalendarNavigation();
    bindUpcomingItems();
  } catch (error) {
    console.error(error);
    section.innerHTML =
      '<div class="alert alert-danger">Impossible de charger le mois demandé.</div>';
  }
}

function bindCalendarNavigation() {
  document.querySelectorAll(".calendar-nav-btn").forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      const url = new URL(link.getAttribute("href"), window.location.href);
      url.hash = "";
      loadCalendarMonth(url.toString());
    });
  });
}

window.addEventListener("popstate", (event) => {
  if (event.state?.calendarMonth && event.state.url) {
    loadCalendarMonth(event.state.url, false);
  }
});

bindCalendarNavigation();
bindUpcomingItems();
