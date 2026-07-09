document.addEventListener("DOMContentLoaded", function () {
  var toggle = document.getElementById("accept_email_toggle");
  if (!toggle) return;

  toggle.addEventListener("change", function () {
    var checked = toggle.checked;
    toggle.disabled = true;

    fetch("includes/general/update_accept_email.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accept_email: checked ? 1 : 0 }),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        toggle.disabled = false;

        if (!data.success) {
          toggle.checked = !checked;
          if (window.jcmToast) {
            jcmToast("alert", data.message || "La mise à jour a échoué.");
          }
          return;
        }

        if (window.jcmToast) {
          jcmToast(
            "success",
            checked
              ? "Vous recevrez désormais les emails du club."
              : "Vous ne recevrez plus les emails du club.",
          );
        }
      })
      .catch(function () {
        toggle.disabled = false;
        toggle.checked = !checked;
        if (window.jcmToast) {
          jcmToast("alert", "Erreur réseau, merci de réessayer.");
        }
      });
  });
});
