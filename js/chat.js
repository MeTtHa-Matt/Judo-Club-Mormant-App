document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("iaChatForm");
  const input = document.getElementById("iaChatInput");
  const messagesContainer = document.getElementById("iaChatMessages");
  const status = document.getElementById("iaChatStatus");
  const resetButton = document.getElementById("iaChatReset");
  const sendBtn = document.getElementById("iaChatSendBtn");

  let isLoading = false;

  function scrollToBottom() {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  function addMessage(role, text) {
    const messageEl = document.createElement("div");
    messageEl.className = "ia-chat-message ia-chat-message-" + role;

    const bubble = document.createElement("div");
    bubble.className = "ia-chat-bubble";

    if (role === "assistant" || role === "user") {
      bubble.innerHTML = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
        .replace(
          /https?:\/\/[^\s]+/g,
          (url) => `<a href="${url}" target="_blank">${url}</a>`,
        )
        .replace(/\n/g, "<br>");
    } else {
      bubble.textContent = text;
    }

    messageEl.appendChild(bubble);
    messagesContainer.appendChild(messageEl);
    scrollToBottom();
  }

  function showLoadingIndicator() {
    const messageEl = document.createElement("div");
    messageEl.className = "ia-chat-message ia-chat-message-assistant";
    messageEl.id = "loading-indicator";

    const bubble = document.createElement("div");
    bubble.className = "ia-chat-loading";
    bubble.innerHTML =
      '<div class="ia-chat-loading-dot"></div><div class="ia-chat-loading-dot"></div><div class="ia-chat-loading-dot"></div>';

    messageEl.appendChild(bubble);
    messagesContainer.appendChild(messageEl);
    scrollToBottom();
  }

  function removeLoadingIndicator() {
    const loadingEl = document.getElementById("loading-indicator");
    if (loadingEl) {
      loadingEl.remove();
    }
  }

  function setStatus(text) {
    status.textContent = text;
  }

  function setButtonState(disabled) {
    sendBtn.disabled = disabled;
    input.disabled = disabled;
    resetButton.disabled = disabled;
    isLoading = disabled;
  }

  async function sendMessage(message) {
    setStatus("Recherche en cours…");
    setButtonState(true);
    showLoadingIndicator();

    try {
      const response = await fetch("includes/general/chat_process.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ message }),
      });

      const data = await response.json();
      removeLoadingIndicator();

      if (!data.success) {
        addMessage(
          "error",
          "⚠️ " + (data.error || "Erreur lors de la requête."),
        );
      } else {
        addMessage("assistant", data.reply);
      }
    } catch (error) {
      console.error(error);
      removeLoadingIndicator();
      addMessage("error", "⚠️ Erreur de connexion au service.");
    } finally {
      setStatus("");
      setButtonState(false);
      input.focus();
    }
  }

  async function resetConversation() {
    if (!confirm("Réinitialiser la conversation ?")) {
      return;
    }

    setButtonState(true);

    try {
      const response = await fetch("includes/general/chat_process.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ action: "reset" }),
      });

      const data = await response.json();
      messagesContainer.innerHTML = "";
      addMessage("system", "✓ Conversation réinitialisée.");
    } catch (error) {
      console.error(error);
    } finally {
      setButtonState(false);
      input.focus();
    }
  }

  form.addEventListener("submit", function (event) {
    event.preventDefault();

    if (isLoading) return;

    const message = input.value.trim();
    if (!message) {
      return;
    }

    addMessage("user", message);
    input.value = "";
    input.style.height = "auto";
    sendMessage(message);
  });

  input.addEventListener("keydown", function (event) {
    if (event.key === "Enter" && !event.shiftKey && !isLoading) {
      event.preventDefault();
      form.dispatchEvent(new Event("submit"));
    }
  });

  input.addEventListener("input", function () {
    this.style.height = "auto";
    this.style.height = Math.min(this.scrollHeight, 120) + "px";
  });

  if (resetButton) {
    resetButton.addEventListener("click", function () {
      resetConversation();
    });
  }
});
