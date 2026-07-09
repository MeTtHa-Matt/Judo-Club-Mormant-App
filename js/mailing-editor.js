const form = document.getElementById("mailForm");
const hiddenMessage = document.getElementById("messageHtml");
const imageInput = document.getElementById("imageInput");

function syncEditor() {
  hiddenMessage.value = quill.root.innerHTML.trim();
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

form.addEventListener("submit", () => {
  syncEditor();
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
