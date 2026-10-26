function appendReportMessage(container, message, isAdmin) {
  const senderIsAdmin = message.sender === "admin";
  const isOwnMessage = senderIsAdmin === isAdmin;
  const article = document.createElement("article");
  article.className = `report-chat-message${isOwnMessage ? " report-chat-message-own" : ""}`;
  const sender = document.createElement("div");
  sender.className = "report-chat-sender";
  sender.textContent = isOwnMessage
    ? "Vous"
    : senderIsAdmin
      ? "Administration"
      : "Visiteur";
  const content = document.createElement("div");
  content.className = "report-chat-text";
  content.textContent = message.message;
  if (!message.message) content.hidden = true;
  article.append(sender);
  if (message.message) article.append(content);
  for (const attachment of message.attachments || []) {
    const link = document.createElement("a");
    link.className = "report-chat-attachment";
    link.href = attachment.url;
    link.target = "_blank";
    link.rel = "noopener";
    const image = document.createElement("img");
    image.src = attachment.url;
    image.alt = attachment.name || "Image jointe";
    image.loading = "lazy";
    link.append(image);
    article.append(link);
  }
  const date = document.createElement("time");
  date.dateTime = message.created_at;
  date.textContent = new Intl.DateTimeFormat("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(message.created_at));
  article.append(date);
  container.append(article);
}

document.querySelectorAll(".report-chat-form").forEach((form) => {
  const chat = form.closest(".report-chat");
  const messages = chat.querySelector(".report-chat-messages");
  const isAdmin = chat.dataset.isAdmin === "1";
  let displayedCount = messages.querySelectorAll(".report-chat-message").length;
  let refreshInProgress = null;
  let sendingMessage = false;
  messages.scrollTop = messages.scrollHeight;
  const imageInput = form.querySelector(".report-chat-file-input");
  const selectedFiles = form.querySelector(".report-chat-selected-files");

  imageInput?.addEventListener("change", () => {
    selectedFiles.replaceChildren();
    for (const file of imageInput.files) {
      const item = document.createElement("span");
      item.textContent = file.name;
      selectedFiles.append(item);
    }
  });

  async function refreshMessages() {
    if (refreshInProgress) return refreshInProgress;
    refreshInProgress = (async () => {
      const response = await fetch(
        `${form.action}?report_id=${encodeURIComponent(chat.dataset.reportId)}`,
        { headers: { Accept: "application/json" } },
      );
      const result = await response.json();
      if (!response.ok || !result.success || !Array.isArray(result.messages)) {
        throw new Error(result.message || "Impossible d’actualiser la conversation.");
      }

      const shouldScroll = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 100;
      for (const message of result.messages.slice(displayedCount)) {
        appendReportMessage(messages, message, isAdmin);
        displayedCount += 1;
      }
      if (shouldScroll) messages.scrollTop = messages.scrollHeight;
    })();
    try {
      await refreshInProgress;
    } finally {
      refreshInProgress = null;
    }
  }

  window.setInterval(() => {
    if (!sendingMessage) {
      refreshMessages().catch((error) => console.error(error));
    }
  }, 8000);

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const textarea = form.querySelector('textarea[name="message"]');
    const submitButton = form.querySelector('button[type="submit"]');
    const errorMessage = form.querySelector(".report-chat-error");
    const message = textarea.value.trim();
    if (!message && !imageInput?.files.length) return;

    submitButton.disabled = true;
    sendingMessage = true;
    errorMessage.textContent = "";
    try {
      if (refreshInProgress) {
        await refreshInProgress.catch((error) => console.error(error));
      }

      const response = await fetch(form.action, {
        method: "POST",
        headers: { Accept: "application/json" },
        body: new FormData(form),
      });
      const result = await response.json();
      if (!response.ok || !result.success || !result.message) {
        throw new Error(result.message || "Impossible d’envoyer le message.");
      }

      appendReportMessage(messages, result.message, isAdmin);
      displayedCount = messages.querySelectorAll(".report-chat-message").length;
      messages.scrollTop = messages.scrollHeight;
      textarea.value = "";
      imageInput.value = "";
      selectedFiles.replaceChildren();
    } catch (error) {
      errorMessage.textContent = error.message || "Erreur réseau. Réessayez.";
    } finally {
      sendingMessage = false;
      submitButton.disabled = false;
    }
  });
});
