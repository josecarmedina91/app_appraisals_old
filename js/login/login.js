// Manejar almacenamiento de las credenciales del usuario
const CredentialStorage = {
  emailInput: null,
  rememberCheckbox: null,

  init() {
    this.emailInput = document.getElementById("email");
    this.rememberCheckbox = document.getElementById("remember-me");

    // Carga cualquier email almacenado al cargar la página
    this.loadCredentials();

    // Escuchar cambios en la casilla de "Remember me"
    this.rememberCheckbox.addEventListener("change", this.toggleRememberMe.bind(this));
  },

  loadCredentials() {
    const rememberedEmail = localStorage.getItem("rememberedEmail");
    if (rememberedEmail) {
      this.emailInput.value = rememberedEmail;
      this.rememberCheckbox.checked = true;
    }
  },

  saveCredentials() {
    localStorage.setItem("rememberedEmail", this.rememberCheckbox.checked ? this.emailInput.value : '');
  },

  toggleRememberMe() {
    this.saveCredentials();
  },
};

// Mostrar y/o ocultar la password
const PasswordToggle = {
  input: null,
  eyeIcon: null,
  toggleButton: null,

  init() {
    this.input = document.getElementById("password");
    this.eyeIcon = document.getElementById("eye-icon");
    this.toggleButton = document.getElementById("toggle-password");

    this.toggleButton.addEventListener("click", this.toggle.bind(this));
  },

  toggle() {
    const type = this.input.type === "password" ? "text" : "password";
    this.input.type = type;
    this.eyeIcon.textContent = type === "password" ? "👁️" : "🚫";
  },
};

document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('form');

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch('php/login/login.php', {
      method: 'POST',
      body: formData,
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        document.getElementById('error-message').innerText = data.error;
        document.getElementById('error-popup').classList.remove('hidden');
      } else {
        window.location.href = data.redirect; // Modificado para redireccionar basado en el rol
      }
    })
    .catch(error => console.error('Error:', error));
  });

  CredentialStorage.init();
  PasswordToggle.init();
});


function validateForm() {
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  let isValid = true;

  // Validación de email
  if (!email.includes("@") || email.trim() === "") {
    document.getElementById("email-error").innerText =
      "Please enter a valid email.";
    isValid = false;
  } else {
    document.getElementById("email-error").innerText = "";
  }

  // Validación de contraseña
  if (password.trim() === "") {
    document.getElementById("password-error").innerText =
      "Please enter a password.";
    isValid = false;
  } else {
    document.getElementById("password-error").innerText = "";
  }

  return isValid;
}
