document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("iaChatForm");
  const input = document.getElementById("iaChatInput");
  const messages = document.getElementById("iaChatMessages");
  const status = document.getElementById("iaChatStatus");
  const resetButton = document.getElementById("iaChatReset");

  let conversation = [];

  function addMessage(role, text) {
    const messageEl = document.createElement("div");
    messageEl.className = "ia-chat-message ia-chat-message-" + role;
    messageEl.textContent = text;
    messages.appendChild(messageEl);
    messages.scrollTop = messages.scrollHeight;
  }

  function setStatus(text) {
    status.textContent = text;
  }

  async function sendMessage(message) {
    setStatus("Envoi en cours…");
    try {
      const response = await fetch("includes/general/chat_process.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ message }),
      });

      const data = await response.json();
      if (!data.success) {
        addMessage("error", data.error || "Erreur lors de la requête.");
      } else {
        addMessage("assistant", data.reply);
        conversation.push({ role: "assistant", content: data.reply });
      }
    } catch (error) {
      console.error(error);
      addMessage("error", "Impossible de joindre le service IA.");
    } finally {
      setStatus("");
    }
  }

  async function resetConversation() {
    setStatus("Réinitialisation de la conversation…");
    try {
      const response = await fetch("includes/general/chat_process.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ action: "reset" }),
      });

      const data = await response.json();
      if (!data.success) {
        addMessage(
          "error",
          data.error || "Erreur lors de la réinitialisation.",
        );
      } else {
        conversation = [];
        messages.innerHTML = "";
        addMessage(
          "system",
          "Conversation réinitialisée. Vous pouvez poser une nouvelle question.",
        );
      }
    } catch (error) {
      console.error(error);
      addMessage("error", "Impossible de réinitialiser la conversation.");
    } finally {
      setStatus("");
    }
  }

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    const message = input.value.trim();
    if (!message) {
      return;
    }

    addMessage("user", message);
    conversation.push({ role: "user", content: message });
    input.value = "";
    sendMessage(message);
  });

  if (resetButton) {
    resetButton.addEventListener("click", function () {
      resetConversation();
    });
  }
});
