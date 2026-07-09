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
