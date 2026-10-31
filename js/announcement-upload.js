document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(
    '#createAnnouncementModal form[enctype="multipart/form-data"]',
  );
  const input = document.getElementById("announcementImages");
  const status = document.getElementById("announcementImagesStatus");
  const fileList = document.getElementById("announcementImagesList");
  if (!form || !input || !status || !fileList) return;

  const maxFiles = 5;
  const maxFileSize = 1024 * 1024;
  const maxTotalSize = maxFiles * maxFileSize;
  let selectedFiles = Array.from(input.files || []);

  const updateSelection = (message = "") => {
    const totalSize = selectedFiles.reduce((total, file) => total + file.size, 0);
    let error = "";

    if (selectedFiles.some((file) => file.size > maxFileSize)) {
      error = "Chaque image doit peser 1 Mo maximum.";
    } else if (totalSize > maxTotalSize) {
      error = "La taille totale des images ne doit pas dépasser 5 Mo.";
    }

    input.setCustomValidity(error);
    status.textContent = error || message || (selectedFiles.length
      ? `${selectedFiles.length} image${selectedFiles.length > 1 ? "s" : ""} sélectionnée${selectedFiles.length > 1 ? "s" : ""}.`
      : "");
    status.classList.toggle("text-danger", Boolean(error));

    fileList.replaceChildren();
    selectedFiles.forEach((file, index) => {
      const item = document.createElement("div");
      item.className = "announcement-upload-file";

      const name = document.createElement("span");
      name.className = "announcement-upload-file-name";
      name.textContent = file.name;

      const removeButton = document.createElement("button");
      removeButton.type = "button";
      removeButton.className = "announcement-upload-file-remove";
      removeButton.setAttribute("aria-label", `Retirer ${file.name}`);
      removeButton.textContent = "×";
      removeButton.addEventListener("click", () => {
        selectedFiles = selectedFiles.filter((_, fileIndex) => fileIndex !== index);
        syncInputFiles();
        updateSelection();
      });

      item.append(name, removeButton);
      fileList.append(item);
    });

    return error === "";
  };

  const syncInputFiles = () => {
    const transfer = new DataTransfer();
    selectedFiles.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
  };

  input.addEventListener("change", () => {
    const newlySelectedFiles = Array.from(input.files || []);
    const existingKeys = new Set(
      selectedFiles.map((file) => `${file.name}:${file.size}:${file.lastModified}`),
    );
    const addedFiles = newlySelectedFiles.filter((file) => {
      const key = `${file.name}:${file.size}:${file.lastModified}`;
      if (existingKeys.has(key)) return false;
      existingKeys.add(key);
      return true;
    });
    const availableSlots = Math.max(0, maxFiles - selectedFiles.length);
    const filesToAdd = addedFiles.slice(0, availableSlots);
    const skippedCount = addedFiles.length - filesToAdd.length;
    selectedFiles = [...selectedFiles, ...filesToAdd];
    syncInputFiles();
    updateSelection(
      skippedCount > 0
        ? `Maximum ${maxFiles} images : ${skippedCount} image${skippedCount > 1 ? "s" : ""} non ajoutée${skippedCount > 1 ? "s" : ""}.`
        : "",
    );
  });

  form.addEventListener("submit", (event) => {
    if (!updateSelection()) {
      event.preventDefault();
      input.reportValidity();
    }
  });

  updateSelection();
});
