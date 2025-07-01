document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("adminLoginForm")
  const errorAlert = document.getElementById("errorAlert")
  const errorMessage = document.getElementById("errorMessage")
  const togglePassword = document.getElementById("togglePassword")
  const passwordInput = document.getElementById("password")
  const loginButton = document.getElementById("loginButton")
  const currentYearSpan = document.getElementById("currentYear")

  // Establecer año actual
  currentYearSpan.textContent = new Date().getFullYear()

  // Toggle password visibility
  togglePassword.addEventListener("click", () => {
    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password"
    passwordInput.setAttribute("type", type)

    const icon = togglePassword.querySelector("i")
    icon.classList.toggle("fa-eye")
    icon.classList.toggle("fa-eye-slash")
  })

  // Manejar envío del formulario
  form.addEventListener("submit", async (e) => {
    e.preventDefault()

    const usuario = document.getElementById("usuario").value.trim()
    const password = document.getElementById("password").value

    if (!usuario || !password) {
      mostrarError("Por favor, completa todos los campos")
      return
    }

    // Deshabilitar botón durante la petición
    loginButton.disabled = true
    loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...'

    try {
      console.log("Intentando login con:", { usuario, password: "***" })

      const formData = new FormData()
      formData.append("usuario", usuario)
      formData.append("password", password)

      const response = await fetch("api/admin-login.php", {
        method: "POST",
        body: formData,
      })

      console.log("Response status:", response.status)
      const result = await response.json()
      console.log("Response data:", result)

      if (result.exito) {
        // Login exitoso
        alert("Login exitoso! Redirigiendo...")
        window.location.href = "admin-panel.html"
      } else {
        mostrarError(result.mensaje || "Usuario o contraseña incorrectos")
      }
    } catch (error) {
      console.error("Error:", error)
      mostrarError("Error de conexión. Intenta nuevamente.")
    } finally {
      // Rehabilitar botón
      loginButton.disabled = false
      loginButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión'
    }
  })

  function mostrarError(mensaje) {
    errorMessage.textContent = mensaje
    errorAlert.style.display = "flex"

    // Ocultar error después de 5 segundos
    setTimeout(() => {
      errorAlert.style.display = "none"
    }, 5000)
  }
})
