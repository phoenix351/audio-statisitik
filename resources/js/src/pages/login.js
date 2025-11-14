export function initLogin() {
  function togglePassword() {
    const fieldId = "password";
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + "-toggle-icon");

    if (field.type === "password") {
      field.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
      field.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
    }
  }

  const emailField = document.getElementById("email");
  if (emailField) {
    emailField.focus();
  }

  document
    .getElementById("password-toggle-icon")
    .addEventListener("click", togglePassword);
}
