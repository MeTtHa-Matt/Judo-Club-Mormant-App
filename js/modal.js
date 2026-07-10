document.addEventListener("DOMContentLoaded", function () {
  const modalJourEl = document.getElementById("modalJourCompetitions");
  const modalJourBody = document.getElementById("modalJourCompetitionsBody");
  const modalJourTitle = document.getElementById("modalJourCompetitionsTitle");

  modalJourEl.addEventListener("show.bs.modal", function (event) {
    const dayCell = event.relatedTarget;
    if (!dayCell) return;
    modalJourTitle.textContent =
      dayCell.getAttribute("data-jour-date") || "Compétitions";
    modalJourBody.innerHTML = dayCell.getAttribute("data-jour-content") || "";
  });

  modalJourEl.addEventListener("hidden.bs.modal", function () {
    modalJourTitle.textContent = "Compétitions";
    modalJourBody.innerHTML = "";
  });

  modalJourBody.addEventListener("click", function (event) {
    const item = event.target.closest(".upcoming-item");
    if (!item || event.target.closest("[data-bs-dismiss='modal']")) return;

    const activeElement = document.activeElement;
    if (
      activeElement instanceof HTMLElement &&
      modalJourEl.contains(activeElement)
    ) {
      activeElement.blur();
    }

    const compModalEl = document.getElementById("modalCompetition");
    const compModal = bootstrap.Modal.getOrCreateInstance(compModalEl);

    modalJourEl.addEventListener("hidden.bs.modal", function handler() {
      modalJourEl.removeEventListener("hidden.bs.modal", handler);
      compModal.show(item);
    });

    bootstrap.Modal.getOrCreateInstance(modalJourEl).hide();
  });
});

// Accessibility: blur focused element inside a modal before it is hidden
// This prevents a focused element inside a now-hidden container (aria-hidden)
// which would cause accessibility warnings in some browsers / screen readers.
document.addEventListener("hide.bs.modal", function (e) {
  try {
    if (document.activeElement && e.target.contains(document.activeElement)) {
      document.activeElement.blur();
    }
  } catch (err) {
    // defensive: if e.target or document.activeElement access fails, ignore
    console.error("Error handling hide.bs.modal blur:", err);
  }
});
