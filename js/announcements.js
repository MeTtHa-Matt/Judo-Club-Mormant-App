document.addEventListener("DOMContentLoaded", function () {
  const modalElement = document.getElementById("announcementsModal");
  if (!modalElement) return;

  const board = document.getElementById("announcementModalBoard");
  const detail = document.getElementById("announcementModalDetail");
  const detailContent = document.getElementById("announcementModalDetailContent");
  const modal = window.bootstrap
    ? bootstrap.Modal.getOrCreateInstance(modalElement)
    : null;
  const csrfToken = modalElement.dataset.csrfToken || "";
  const announcementIds = JSON.parse(modalElement.dataset.announcementIds || "[]");
  let markSeenRequested = false;
  let announcementLayoutPlan = null;

  const layoutAnnouncementWall = (wall) => {
    if (!wall) return;
    const notes = Array.from(wall.querySelectorAll(".announcement-note"));
    const styles = getComputedStyle(wall);
    const paddingX = parseFloat(styles.paddingLeft) + parseFloat(styles.paddingRight);
    const paddingY = parseFloat(styles.paddingTop) + parseFloat(styles.paddingBottom);
    const borderY = parseFloat(styles.borderTopWidth) + parseFloat(styles.borderBottomWidth);
    const availableWidth = Math.max(1, wall.clientWidth - paddingX);
    if (availableWidth <= 1) return;

    if (notes.length === 0) {
      wall.classList.add("announcement-wall--scattered");
      wall.classList.remove("announcement-wall--pending");
      return;
    }

    if (!announcementLayoutPlan) {
      const baseHeight = Math.max(1, wall.clientHeight - paddingY);
      const logicalWidth = 1000;
      const noteSizes = [
        { width: 360, height: 160 },
        { width: 480, height: 178 },
        { width: 390, height: 220 },
        { width: 440, height: 190 },
        { width: 520, height: 160 },
        { width: 400, height: 205 },
      ];
      const maxNoteHeight = Math.max(...noteSizes.map(({ height }) => height));
      const wallHeight = Math.max(baseHeight, maxNoteHeight + notes.length * 52);
      const placed = [];
      const positions = new Map();
      const overlapPadding = 12;

      const makeRandom = (note, index) => {
        let seed = 2166136261;
        for (const character of String(note.dataset.announcementOpen)) {
          seed = Math.imul(seed ^ character.charCodeAt(0), 16777619);
        }
        seed = Math.imul(seed ^ (index + 1), 2246822519);
        const random = () => {
          seed = (seed + 0x6d2b79f5) | 0;
          let value = seed;
          value = Math.imul(value ^ (value >>> 15), value | 1);
          value ^= value + Math.imul(value ^ (value >>> 7), value | 61);
          return ((value ^ (value >>> 14)) >>> 0) / 4294967296;
        };
        return random;
      };

      const intersects = (first, second, padding = 0) =>
        first.left < second.left + second.width - padding &&
        first.left + first.width - padding > second.left &&
        first.top < second.top + second.height - padding &&
        first.top + first.height - padding > second.top;

      const getOverlapRatio = (first, second) => {
        const overlapWidth = Math.max(
          0,
          Math.min(first.left + first.width, second.left + second.width) -
            Math.max(first.left, second.left),
        );
        const overlapHeight = Math.max(
          0,
          Math.min(first.top + first.height, second.top + second.height) -
            Math.max(first.top, second.top),
        );
        return (overlapWidth * overlapHeight) / (first.width * first.height);
      };

      const addCandidate = (candidates, candidateKeys, left, top, width, height) => {
        const candidate = {
          left: Math.max(0, Math.min(logicalWidth - width, left)),
          top: Math.max(0, Math.min(wallHeight - height, top)),
          width,
          height,
        };
        const key = `${Math.round(candidate.left / 8)}:${Math.round(candidate.top / 8)}`;
        if (!candidateKeys.has(key)) {
          candidateKeys.add(key);
          candidates.push(candidate);
        }
      };

      const findFreePosition = (random, width, height) => {
        const candidates = [];
        const candidateKeys = new Set();
        for (let attempt = 0; attempt < 700; attempt++) {
          addCandidate(
            candidates,
            candidateKeys,
            random() * Math.max(0, logicalWidth - width),
            random() * Math.max(0, wallHeight - height),
            width,
            height,
          );
        }

        for (const note of placed) {
          addCandidate(
            candidates,
            candidateKeys,
            note.left - width - overlapPadding,
            note.top + (random() - 0.5) * 36,
            width,
            height,
          );
          addCandidate(
            candidates,
            candidateKeys,
            note.left + note.width + overlapPadding,
            note.top + (random() - 0.5) * 36,
            width,
            height,
          );
          addCandidate(
            candidates,
            candidateKeys,
            note.left + (random() - 0.5) * 36,
            note.top - height - overlapPadding,
            width,
            height,
          );
          addCandidate(
            candidates,
            candidateKeys,
            note.left + (random() - 0.5) * 36,
            note.top + note.height + overlapPadding,
            width,
            height,
          );
        }

        const availableCandidates = candidates.filter(
          (candidate) =>
            placed.every((note) => !intersects(candidate, note, overlapPadding)),
        );
        if (availableCandidates.length === 0) return null;
        return availableCandidates[Math.floor(random() * availableCandidates.length)];
      };

      const findLayerPosition = (random, width, height) => {
        const candidates = [];
        const candidateKeys = new Set();
        for (let attempt = 0; attempt < 1400; attempt++) {
          addCandidate(
            candidates,
            candidateKeys,
            random() * Math.max(0, logicalWidth - width),
            random() * Math.max(0, wallHeight - height),
            width,
            height,
          );
        }

        for (const note of placed) {
          for (let attempt = 0; attempt < 12; attempt++) {
            addCandidate(
              candidates,
              candidateKeys,
              note.left + (random() - 0.5) * note.width * 0.8,
              note.top + (random() - 0.5) * note.height * 0.8,
              width,
              height,
            );
          }
        }

        const allScoredCandidates = candidates.map((candidate) => {
          const overlaps = placed.filter((note) => intersects(candidate, note));
          const ratios = overlaps.map((note) => getOverlapRatio(candidate, note));
          const minTargetCover = ratios.length > 0 ? Math.max(...ratios) : 1;
          const coverScore = ratios.reduce((total, ratio) => total + ratio, 0);
          return { candidate, overlapCount: overlaps.length, minTargetCover, coverScore };
        });
        const scoredCandidates = allScoredCandidates.filter(
          (entry) => entry.overlapCount >= 3 && entry.overlapCount <= 4,
        );

        const preferredCount = random() < 0.5 ? 3 : 4;
        const chooseBest = (entries) => {
          if (entries.length === 0) return null;
          const getScore = (entry) =>
            Math.abs(entry.overlapCount - preferredCount) * 0.4 +
            entry.coverScore * 0.05 +
            entry.minTargetCover * 0.12;
          const bestScore = Math.min(...entries.map(getScore));
          const nearBest = entries.filter((entry) => getScore(entry) <= bestScore + 0.12);
          return nearBest[Math.floor(random() * nearBest.length)].candidate;
        };

        const lightOverlapCandidates = scoredCandidates.filter(
          (entry) => entry.minTargetCover <= 0.66,
        );
        const balancedPosition = chooseBest(lightOverlapCandidates);
        if (balancedPosition) return balancedPosition;

        const visibleOverlapCandidates = scoredCandidates.filter(
          (entry) => entry.minTargetCover <= 0.86,
        );
        const partialPosition = chooseBest(visibleOverlapCandidates);
        if (partialPosition) return partialPosition;

        const conservativePositions = allScoredCandidates.filter(
          (entry) =>
            entry.overlapCount > 0 &&
            entry.overlapCount <= 4 &&
            entry.minTargetCover <= 0.94,
        );
        const conservativePosition = chooseBest(conservativePositions);
        if (conservativePosition) return conservativePosition;

        const leastObscuring = allScoredCandidates
          .filter((entry) => entry.overlapCount > 0)
          .sort(
            (first, second) =>
              first.minTargetCover - second.minTargetCover ||
              first.overlapCount - second.overlapCount,
          )
          .slice(0, 12);
        return chooseBest(leastObscuring);
      };

      notes
        .slice()
        .reverse()
        .forEach((note, index) => {
          const random = makeRandom(note, index);
          const style = 1 + Math.floor(random() * noteSizes.length);
          const { width, height } = noteSizes[style - 1];
          const position =
            findFreePosition(random, width, height) ||
            findLayerPosition(random, width, height) || {
              left: random() * Math.max(0, logicalWidth - width),
              top: random() * Math.max(0, wallHeight - height),
              width,
              height,
            };
          positions.set(String(note.dataset.announcementOpen), {
            ...position,
            tilt: (random() * 8 - 4).toFixed(2),
            style,
          });
          placed.push(position);
        });

      announcementLayoutPlan = {
        width: logicalWidth,
        height: wallHeight,
        positions,
      };
    }

    const minHeight = parseFloat(styles.minHeight) || 0;
    const baseHeight = Math.max(1, wall.clientHeight - paddingY);
    const contentHeight = Math.max(baseHeight, announcementLayoutPlan.height);
    const outerHeight = Math.max(
      minHeight,
      contentHeight + paddingY + borderY,
    );

    notes.forEach((note) => {
      const position = announcementLayoutPlan.positions.get(
        String(note.dataset.announcementOpen),
      );
      if (!position) return;
      const left = position.left * (availableWidth / announcementLayoutPlan.width);
      const verticalScale = contentHeight / announcementLayoutPlan.height;
      const top = position.top * verticalScale;
      const noteWidth = position.width * (availableWidth / announcementLayoutPlan.width);
      const noteHeight = position.height * verticalScale;

      note.style.left = `${paddingX / 2 + left}px`;
      note.style.top = `${parseFloat(styles.paddingTop) + top}px`;
      note.style.width = `${noteWidth}px`;
      note.style.height = `${noteHeight}px`;
      note.style.setProperty("--note-tilt", `${position.tilt}deg`);
      note.classList.remove(...Array.from(
        { length: 6 },
        (_, index) => `announcement-note--style-${index + 1}`,
      ));
      note.classList.add(`announcement-note--style-${position.style}`);
    });

    wall.style.height = `${Math.ceil(outerHeight)}px`;
    wall.classList.add("announcement-wall--scattered");
    wall.classList.remove("announcement-wall--pending");
  };

  const layoutAllAnnouncementWalls = () => {
    document.querySelectorAll(".announcement-wall").forEach(layoutAnnouncementWall);
  };

  const showBoard = () => {
    board.hidden = false;
    detail.hidden = true;
    detailContent.replaceChildren();
    requestAnimationFrame(layoutAllAnnouncementWalls);
  };

  const showDetail = (announcementId) => {
    const template = modalElement.querySelector(
      `template[data-announcement-detail="${CSS.escape(announcementId)}"]`,
    );
    if (!template) return;
    detailContent.replaceChildren(template.content.cloneNode(true));
    const finalArticle = detailContent.querySelector(".announcement-detail");
    board.hidden = true;
    detail.hidden = false;
    finalArticle?.classList.add("announcement-detail--entering");
    detail.querySelector(".announcement-detail-close")?.focus();
  };

  const deleteAnnouncement = async (button) => {
    const announcementId = button.dataset.announcementDeleteConfirm;
    const dialog = button.closest(".announcement-delete-dialog");
    const errorMessage = dialog?.querySelector(".announcement-delete-error");
    if (!announcementId || !dialog || !errorMessage) return;

    button.disabled = true;
    dialog.querySelector(".announcement-delete-cancel").disabled = true;
    errorMessage.hidden = true;
    const requestBody = new URLSearchParams({
      action: "delete",
      announcement_id: announcementId,
      csrf_token: csrfToken,
    });

    try {
      const response = await fetch(modalElement.dataset.processUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: requestBody,
      });
      let data;
      try {
        data = await response.json();
      } catch {
        throw new Error("Votre session a expiré ou vous n’avez pas les droits nécessaires.");
      }
      if (!response.ok || !data.success) {
        throw new Error(data.message || "La suppression de l’annonce a échoué.");
      }

      window.location.reload();
    } catch (error) {
      errorMessage.textContent =
        error instanceof Error
          ? error.message
          : "La suppression de l’annonce a échoué. Réessayez.";
      errorMessage.hidden = false;
      button.disabled = false;
      dialog.querySelector(".announcement-delete-cancel").disabled = false;
    }
  };

  const markAnnouncementsSeen = () => {
    if (markSeenRequested || !modalElement.dataset.hasUnseen || !csrfToken) return;
    markSeenRequested = true;
    const requestBody = new URLSearchParams({
      action: "mark_seen",
      csrf_token: csrfToken,
    });
    announcementIds.forEach((announcementId) => {
      requestBody.append("announcement_ids[]", String(announcementId));
    });

    fetch(modalElement.dataset.processUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: requestBody,
    })
      .then((response) => {
        if (!response.ok) throw new Error("Impossible d’enregistrer les annonces consultées.");
        return response.json();
      })
      .then((data) => {
        if (!data.success) throw new Error("Impossible d’enregistrer les annonces consultées.");
      })
      .catch((error) => {
        markSeenRequested = false;
        console.error(error);
      });
  };

  document.addEventListener("click", (event) => {
    const imageNote = event.target.closest("[data-announcement-image]");
    if (imageNote) {
      const article = imageNote.closest(".announcement-detail");
      const imageViewer = article?.querySelector(".announcement-image-viewer");
      const viewerImage = imageViewer?.querySelector(".announcement-image-viewer-image");
      if (imageViewer && viewerImage) {
        viewerImage.src = imageNote.dataset.announcementImage;
        imageViewer.showModal();
        imageViewer.querySelector(".announcement-image-viewer-close")?.focus();
      }
      return;
    }

    const imageViewerClose = event.target.closest(".announcement-image-viewer-close");
    if (imageViewerClose) {
      imageViewerClose.closest(".announcement-image-viewer")?.close();
      return;
    }

    const imageViewer = event.target.closest(".announcement-image-viewer");
    if (imageViewer && event.target === imageViewer) {
      imageViewer.close();
      return;
    }

    const deleteButton = event.target.closest("[data-announcement-delete]");
    if (deleteButton) {
      const deleteDialog = deleteButton
        .closest(".announcement-detail")
        ?.querySelector(".announcement-delete-dialog");
      if (deleteDialog && !deleteDialog.open) {
        deleteDialog.showModal();
        deleteDialog.querySelector(".announcement-delete-cancel")?.focus();
      }
      return;
    }

    const confirmDeleteButton = event.target.closest("[data-announcement-delete-confirm]");
    if (confirmDeleteButton) {
      deleteAnnouncement(confirmDeleteButton);
      return;
    }

    const cancelDeleteButton = event.target.closest(".announcement-delete-cancel");
    if (cancelDeleteButton) {
      cancelDeleteButton.closest(".announcement-delete-dialog")?.close();
      return;
    }

    const note = event.target.closest("[data-announcement-open]");
    if (note) {
      const announcementId = note.dataset.announcementOpen;
      if (note.dataset.announcementContext === "modal") {
        showDetail(announcementId);
      } else {
        if (!modal) return;
        showBoard();
        modalElement.addEventListener(
          "shown.bs.modal",
          () => showDetail(announcementId),
          { once: true },
        );
        modal.show();
      }
      return;
    }

    if (event.target.closest(".announcement-detail-close")) {
      showBoard();
    }
  });

  modalElement.addEventListener("shown.bs.modal", markAnnouncementsSeen);
  modalElement.addEventListener("shown.bs.modal", layoutAllAnnouncementWalls);
  modalElement.addEventListener("hidden.bs.modal", showBoard);

  layoutAllAnnouncementWalls();
  window.addEventListener("resize", layoutAllAnnouncementWalls);

  if (modal && modalElement.dataset.autoOpen === "1" && !document.querySelector(".modal.show")) {
    modal.show();
  }
});
