const form = document.getElementById("mailForm");
const hiddenMessage = document.getElementById("messageHtml");
const imageInput = document.getElementById("imageInput");
const suggestions = document.getElementById("mailingSuggestions");
const contacts = Array.isArray(window.mailingContacts) ? window.mailingContacts : [];

function syncEditor() {
  hiddenMessage.value = quill.root.innerHTML.trim();
}

function normalize(value) {
  return value.trim().toLocaleLowerCase();
}

const quill = new Quill("#editor", {
  theme: "snow",
  modules: {
    toolbar: {
      container: "#toolbar",
      handlers: {
        image: () => imageInput.click(),
      },
    },
  },
  placeholder: "Écrivez votre message ici...",
});

const recipientRoot = document.querySelector('[data-recipient-field="to"]');
const recipientField = {
  input: recipientRoot.querySelector("input"),
  chips: recipientRoot.querySelector(".mailing-chips"),
  recipients: [],
};
const commands = [
  { token: "all", label: "Tous les utilisateurs", predicate: () => true },
  { token: "admin", label: "Tous les administrateurs", predicate: (contact) => contact.admin },
  { token: "nadmin", label: "Tous les non-administrateurs", predicate: (contact) => !contact.admin },
];

function recipientExists(email) {
  const normalizedEmail = normalize(email);
  return recipientField.recipients.some(
    (recipient) => normalize(recipient.email) === normalizedEmail,
  );
}

function renderRecipients() {
  recipientField.chips.replaceChildren();
  recipientField.recipients.forEach((recipient, index) => {
    const chip = document.createElement("span");
    chip.className = "mailing-recipient-chip";

    const label = document.createElement("span");
    label.className = "mailing-chip-label";
    label.textContent = recipient.name
      ? `${recipient.name} <${recipient.email}>`
      : recipient.email;

    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "mailing-chip-remove";
    removeButton.setAttribute("aria-label", `Supprimer ${recipient.email}`);
    removeButton.innerHTML = "&times;";
    removeButton.addEventListener("click", () => {
      recipientField.recipients.splice(index, 1);
      renderRecipients();
      recipientField.input.focus();
    });

    const hiddenInput = document.createElement("input");
    hiddenInput.type = "hidden";
    hiddenInput.name = "to[]";
    hiddenInput.value = recipient.email;

    chip.append(label, removeButton, hiddenInput);
    recipientField.chips.append(chip);
  });
}

function addEmail(value, suggestedContact = null) {
  const email = (suggestedContact?.email || value).trim();
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    recipientField.input.setCustomValidity("Saisissez une adresse e-mail valide ou une commande proposée.");
    return false;
  }
  recipientField.input.setCustomValidity("");

  if (recipientExists(email)) {
    suggestions.hidden = true;
    return true;
  }

  const contact =
    suggestedContact ||
    contacts.find((candidate) => normalize(candidate.email) === normalize(email));
  recipientField.recipients.push({
    email,
    name: contact?.name || "",
  });
  renderRecipients();
  suggestions.hidden = true;
  return true;
}

function expandCommand(token) {
  const command = commands.find((candidate) => candidate.token === normalize(token));
  if (!command) return false;

  contacts.filter(command.predicate).forEach((contact) => {
    if (!recipientExists(contact.email)) {
      recipientField.recipients.push(contact);
    }
  });
  renderRecipients();
  recipientField.input.setCustomValidity("");
  suggestions.hidden = true;
  return true;
}

function commitRecipientToken(token) {
  const value = token.trim();
  if (!value) return true;
  return expandCommand(value) || addEmail(value);
}

function showSuggestions() {
  const query = normalize(recipientField.input.value);
  suggestions.replaceChildren();
  if (!query) {
    suggestions.hidden = true;
    return;
  }

  commands
    .filter((command) => command.token.startsWith(query))
    .forEach((command) => {
      const option = document.createElement("button");
      option.type = "button";
      option.className = "mailing-suggestion mailing-command-suggestion";
      option.setAttribute("role", "option");

      const name = document.createElement("strong");
      name.textContent = command.token;
      const description = document.createElement("small");
      description.textContent = command.label;
      option.append(name, description);
      option.addEventListener("mousedown", (event) => event.preventDefault());
      option.addEventListener("click", () => {
        expandCommand(command.token);
        recipientField.input.value = "";
        recipientField.input.focus();
      });
      suggestions.append(option);
    });

  const matches = contacts
    .filter((contact) => {
      const email = normalize(contact.email);
      const name = normalize(contact.name);
      return (
        (email.includes(query) || name.includes(query)) &&
        !recipientExists(contact.email)
      );
    })
    .slice(0, 7);

  if (matches.length === 0 && suggestions.childElementCount === 0) {
    suggestions.hidden = true;
    return;
  }

  matches.forEach((contact) => {
    const option = document.createElement("button");
    option.type = "button";
    option.className = "mailing-suggestion";
    option.setAttribute("role", "option");

    const name = document.createElement("strong");
    name.textContent = contact.name || contact.email;
    const email = document.createElement("small");
    email.textContent = contact.email;
    option.append(name, email);
    option.addEventListener("mousedown", (event) => {
      event.preventDefault();
    });
    option.addEventListener("click", () => {
      addEmail(contact.email, contact);
      recipientField.input.value = "";
      recipientField.input.focus();
    });
    suggestions.append(option);
  });

  suggestions.hidden = false;
}

recipientField.input.addEventListener("input", () => {
  recipientField.input.setCustomValidity("");
  const value = recipientField.input.value;
  const delimiterMatch = value.match(/[\s,;]+$/);
  if (delimiterMatch) {
    const completedValue = value.slice(0, -delimiterMatch[0].length);
    const values = completedValue.split(/[\s,;]+/).filter(Boolean);
    const currentValue = value.slice(completedValue.length + delimiterMatch[0].length);
    recipientField.input.value = currentValue;
    values.forEach(commitRecipientToken);
  }
  showSuggestions();
});

recipientField.input.addEventListener("keydown", (event) => {
  if (event.key === "ArrowDown" && !suggestions.hidden) {
    event.preventDefault();
    suggestions.querySelector(".mailing-suggestion")?.focus();
  } else if (
    event.code === "Space" ||
    event.key === " " ||
    event.key === "Spacebar" ||
    event.key === "Enter" ||
    event.key === "," ||
    event.key === ";"
  ) {
    if (!recipientField.input.value.trim()) return;
    event.preventDefault();
    const added = commitRecipientToken(recipientField.input.value);
    if (added) recipientField.input.value = "";
    else recipientField.input.reportValidity();
    showSuggestions();
  } else if (
    (event.key === "Backspace" || event.key === "Delete") &&
    recipientField.input.value === "" &&
    recipientField.recipients.length > 0
  ) {
    recipientField.recipients.pop();
    renderRecipients();
  } else if (event.key === "Escape") {
    suggestions.hidden = true;
  }
});

recipientField.input.addEventListener("focus", showSuggestions);

document.addEventListener("click", (event) => {
  if (!event.target.closest(".mailing-recipients")) {
    suggestions.hidden = true;
  }
});

quill.on("text-change", () => {
  syncEditor();
});

quill.on("selection-change", () => {
  const formats = [];
  if (quill.getFormat().bold) formats.push("gras");
  if (quill.getFormat().italic) formats.push("italique");
  if (quill.getFormat().underline) formats.push("souligné");
  if (formats.length > 0) {
    // optional: handle active formatting state here if needed
  }
});

form.addEventListener("submit", (event) => {
  syncEditor();
  if (recipientField.input.value.trim()) {
    if (!commitRecipientToken(recipientField.input.value)) {
      event.preventDefault();
      recipientField.input.reportValidity();
      return;
    }
    recipientField.input.value = "";
  }
  if (recipientField.recipients.length === 0) {
    event.preventDefault();
    recipientField.input.setCustomValidity("Ajoutez au moins un destinataire dans le champ À.");
    recipientField.input.reportValidity();
    recipientField.input.focus();
    return;
  }

  if (!quill.getText().trim() && quill.root.querySelectorAll("img").length === 0) {
    event.preventDefault();
    document.querySelector(".mailing-editor .ql-editor").focus();
    window.alert("Ajoutez du texte ou une image dans votre message.");
  }
});

imageInput.addEventListener("change", function () {
  const file = this.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function () {
    const range = quill.getSelection(true);
    quill.insertEmbed(range?.index ?? 0, "image", reader.result);
    quill.setSelection((range?.index ?? 0) + 1);
    syncEditor();
  };
  reader.readAsDataURL(file);
  this.value = "";
});

syncEditor();
