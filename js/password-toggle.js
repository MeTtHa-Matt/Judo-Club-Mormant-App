document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  const input = document.getElementById(button.dataset.passwordToggle);
  const icon = button.querySelector('i');
  if (!input || !icon) return;

  button.addEventListener('click', () => {
    const showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(showPassword));
    button.setAttribute('aria-label', showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    icon.classList.toggle('bi-eye', !showPassword);
    icon.classList.toggle('bi-eye-slash', showPassword);
  });
});