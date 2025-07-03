class AdminLogin {
  constructor() {
    this.form = document.getElementById("adminLoginForm")
    this.alertContainer = document.getElementById("alertContainer")
    this.loginButton = document.getElementById("loginButton")
    this.togglePassword = document.getElementById("togglePassword")
    this.passwordInput = document.getElementById("password")
    this.usuarioInput = document.getElementById("usuario")
    this.rememberMe = document.getElementById("rememberMe")

    this.isLoading = false
    this.validators = {
      usuario: this.validateUsuario.bind(this),
      password: this.validatePassword.bind(this),
    }

    this.init()
  }

  init() {
    this.setupEventListeners()
    this.setCurrentYear()
    this.loadRememberedUser()
    this.setupKeyboardShortcuts()
  }

  setupEventListeners() {
    // Form submission
    this.form.addEventListener("submit", this.handleSubmit.bind(this))

    // Password toggle
    this.togglePassword.addEventListener("click", this.togglePasswordVisibility.bind(this))

    // Real-time validation
    this.usuarioInput.addEventListener("blur", () => this.validateField("usuario"))
    this.passwordInput.addEventListener("blur", () => this.validateField("password"))

    // Clear errors on input
    this.usuarioInput.addEventListener("input", () => this.clearFieldError("usuario"))
    this.passwordInput.addEventListener("input", () => this.clearFieldError("password"))

    // Enter key handling
    this.usuarioInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        this.passwordInput.focus()
      }
    })

    this.passwordInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        this.form.dispatchEvent(new Event("submit"))
      }
    })
  }

  setupKeyboardShortcuts() {
    document.addEventListener("keydown", (e) => {
      // Alt + L para enfocar usuario
      if (e.altKey && e.key === "l") {
        e.preventDefault()
        this.usuarioInput.focus()
      }

      // Escape para limpiar formulario
      if (e.key === "Escape") {
        this.clearForm()
      }
    })
  }

  setCurrentYear() {
    const currentYearSpan = document.getElementById("currentYear")
    if (currentYearSpan) {
      currentYearSpan.textContent = new Date().getFullYear()
    }
  }

  loadRememberedUser() {
    const rememberedUser = localStorage.getItem("rememberedUser")
    if (rememberedUser) {
      this.usuarioInput.value = rememberedUser
      this.rememberMe.checked = true
      this.passwordInput.focus()
    }
  }

  async handleSubmit(e) {
    e.preventDefault()

    if (this.isLoading) return

    const usuario = this.usuarioInput.value.trim()
    const password = this.passwordInput.value

    // Validate form
    const isValid = this.validateForm()
    if (!isValid) return

    this.setLoadingState(true)

    try {
      const result = await this.performLogin(usuario, password)

      if (result.exito) {
        this.handleLoginSuccess(usuario)
      } else {
        this.showAlert("error", result.mensaje || "Usuario o contraseña incorrectos")
      }
    } catch (error) {
      console.error("Error en login:", error)
      this.showAlert("error", "Error de conexión. Por favor, intenta nuevamente.")
    } finally {
      this.setLoadingState(false)
    }
  }

  async performLogin(usuario, password) {
    const formData = new FormData()
    formData.append("usuario", usuario)
    formData.append("password", password)

    const response = await fetch("api/admin-login.php", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
  }

  handleLoginSuccess(usuario) {
    // Remember user if checkbox is checked
    if (this.rememberMe.checked) {
      localStorage.setItem("rememberedUser", usuario)
    } else {
      localStorage.removeItem("rememberedUser")
    }

    this.showAlert("success", "¡Login exitoso! Redirigiendo...")

    // Redirect after short delay
    setTimeout(() => {
      window.location.href = "admin-panel.html"
    }, 1500)
  }

  validateForm() {
    let isValid = true

    // Validate all fields
    for (const fieldName in this.validators) {
      if (!this.validateField(fieldName)) {
        isValid = false
      }
    }

    return isValid
  }

  validateField(fieldName) {
    const validator = this.validators[fieldName]
    if (!validator) return true

    const input = document.getElementById(fieldName)
    const value = input.value.trim()
    const result = validator(value)

    if (result.isValid) {
      this.clearFieldError(fieldName)
      return true
    } else {
      this.showFieldError(fieldName, result.message)
      return false
    }
  }

  validateUsuario(value) {
    if (!value) {
      return { isValid: false, message: "El usuario es obligatorio" }
    }

    if (value.length < 3) {
      return { isValid: false, message: "El usuario debe tener al menos 3 caracteres" }
    }

    if (!/^[a-zA-Z0-9_]+$/.test(value)) {
      return { isValid: false, message: "El usuario solo puede contener letras, números y guiones bajos" }
    }

    return { isValid: true }
  }

  validatePassword(value) {
    if (!value) {
      return { isValid: false, message: "La contraseña es obligatoria" }
    }

    if (value.length < 4) {
      return { isValid: false, message: "La contraseña debe tener al menos 4 caracteres" }
    }

    return { isValid: true }
  }

  showFieldError(fieldName, message) {
    const errorElement = document.getElementById(`${fieldName}Error`)
    const inputElement = document.getElementById(fieldName)

    if (errorElement) {
      errorElement.textContent = message
      errorElement.classList.add("show")
    }

    if (inputElement) {
      inputElement.style.borderColor = "var(--error-color)"
    }
  }

  clearFieldError(fieldName) {
    const errorElement = document.getElementById(`${fieldName}Error`)
    const inputElement = document.getElementById(fieldName)

    if (errorElement) {
      errorElement.classList.remove("show")
    }

    if (inputElement) {
      inputElement.style.borderColor = ""
    }
  }

  togglePasswordVisibility() {
    const type = this.passwordInput.getAttribute("type") === "password" ? "text" : "password"
    this.passwordInput.setAttribute("type", type)

    const icon = this.togglePassword.querySelector("i")
    icon.classList.toggle("fa-eye")
    icon.classList.toggle("fa-eye-slash")

    // Add visual feedback
    this.togglePassword.style.transform = "scale(0.9)"
    setTimeout(() => {
      this.togglePassword.style.transform = ""
    }, 150)
  }

  setLoadingState(loading) {
    this.isLoading = loading

    if (loading) {
      this.loginButton.classList.add("loading")
      this.loginButton.disabled = true
    } else {
      this.loginButton.classList.remove("loading")
      this.loginButton.disabled = false
    }
  }

  showAlert(type, message) {
    // Clear existing alerts
    this.alertContainer.innerHTML = ""

    const alert = document.createElement("div")
    alert.className = `alert ${type}`

    const icon = type === "error" ? "fas fa-exclamation-circle" : "fas fa-check-circle"

    alert.innerHTML = `
            <i class="${icon}"></i>
            <span>${message}</span>
        `

    this.alertContainer.appendChild(alert)

    // Trigger animation
    setTimeout(() => {
      alert.classList.add("show")
    }, 10)

    // Auto-hide error alerts after 5 seconds
    if (type === "error") {
      setTimeout(() => {
        this.hideAlert(alert)
      }, 5000)
    }
  }

  hideAlert(alertElement) {
    alertElement.classList.remove("show")
    setTimeout(() => {
      if (alertElement.parentNode) {
        alertElement.parentNode.removeChild(alertElement)
      }
    }, 300)
  }

  clearForm() {
    this.form.reset()
    this.clearFieldError("usuario")
    this.clearFieldError("password")
    this.alertContainer.innerHTML = ""
    this.usuarioInput.focus()
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new AdminLogin()
})

// Add some utility functions for better UX
document.addEventListener("visibilitychange", () => {
  if (!document.hidden) {
    // Page became visible, focus on first empty field
    const usuarioInput = document.getElementById("usuario")
    const passwordInput = document.getElementById("password")

    if (!usuarioInput.value) {
      usuarioInput.focus()
    } else if (!passwordInput.value) {
      passwordInput.focus()
    }
  }
})

// Handle browser back button
window.addEventListener("pageshow", (event) => {
  if (event.persisted) {
    // Page was loaded from cache, reset form state
    const form = document.getElementById("adminLoginForm")
    if (form) {
      form.reset()
    }
  }
})
