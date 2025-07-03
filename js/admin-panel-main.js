// Archivo principal que inicializa y coordina todo el panel de administración
window.AdminPanel = {
  // Estado global del panel
  currentSection: "dashboard",
  currentUserMode: "add",
  catalogos: {},
  cursosConUsuarios: [],

  // Referencias a elementos del DOM
  elements: {},

  // Inicializar el panel de administración
  async init() {
    console.log("🚀 Inicializando Panel de Administración...")

    try {
      // Verificar sesión
      await this.verificarSesion()

      // Obtener referencias del DOM
      this.obtenerElementosDOM()

      // Configurar event listeners
      this.setupEventListeners()

      // Cargar datos iniciales
      await this.cargarDatosIniciales()

      // Configurar módulos
      this.setupModulos()

      console.log("✅ Panel de Administración inicializado correctamente")
    } catch (error) {
      console.error("❌ Error inicializando panel:", error)
      this.manejarErrorInicializacion(error)
    }
  },

  // Verificar sesión del administrador
  async verificarSesion() {
    try {
      console.log("🔐 Verificando sesión de administrador...")

      const response = await fetch("api/verificar_sesion.php")
      const data = await response.json()

      if (!data.exito) {
        throw new Error("Sesión no válida")
      }

      console.log("✅ Sesión verificada:", data.admin)
    } catch (error) {
      console.error("❌ Error verificando sesión:", error)
      // Redirigir al login
      window.location.href = "admin-login.html"
      throw error
    }
  },

  // Obtener referencias a elementos del DOM
  obtenerElementosDOM() {
    console.log("🔍 Obteniendo referencias del DOM...")

    this.elements = {
      // Navegación
      navItems: document.querySelectorAll(".nav-item"),
      contentSections: document.querySelectorAll(".content-section"),

      // Header
      pageTitle: document.getElementById("pageTitle"),
      pageSubtitle: document.getElementById("pageSubtitle"),
      addNewBtn: document.getElementById("addNewBtn"),
      logoutBtn: document.getElementById("logoutBtn"),

      // Gestión de usuarios
      tabButtons: document.querySelectorAll(".tab-button"),
      addUserMode: document.getElementById("addUserMode"),
      updateUserMode: document.getElementById("updateUserMode"),

      // Búsqueda de usuarios
      searchUsuarioInput: document.getElementById("searchUsuarioInput"),
      autocompleteDropdown: document.getElementById("autocompleteDropdown"),
      clearSearchBtn: document.getElementById("clearSearchBtn"),
      clearSelectionBtn: document.getElementById("clearSelectionBtn"),
      cancelUpdateBtn: document.getElementById("cancelUpdateBtn"),
      userFoundAlert: document.getElementById("userFoundAlert"),
      updateFormContainer: document.getElementById("updateFormContainer"),
    }

    // Verificar elementos críticos
    const elementosCriticos = ["navItems", "contentSections", "pageTitle"]
    const faltantes = elementosCriticos.filter((key) => !this.elements[key] || this.elements[key].length === 0)

    if (faltantes.length > 0) {
      console.warn("⚠️ Elementos DOM faltantes:", faltantes)
    }

    console.log("✅ Referencias DOM obtenidas")
  },

  // Configurar event listeners principales
  setupEventListeners() {
    console.log("🎧 Configurando event listeners principales...")

    // Navegación
    this.elements.navItems.forEach((item) => {
      item.addEventListener("click", (e) => {
        e.preventDefault()
        const section = item.dataset.section
        if (section && window.AdminPanelNavigation) {
          window.AdminPanelNavigation.cambiarSeccion(section)
        }
      })
    })

    // Botón agregar nuevo
    if (this.elements.addNewBtn) {
      this.elements.addNewBtn.addEventListener("click", this.manejarAgregarNuevo.bind(this))
    }

    // Botón cerrar sesión
    if (this.elements.logoutBtn) {
      this.elements.logoutBtn.addEventListener("click", this.cerrarSesion.bind(this))
    }

    // Event listeners para clicks fuera de dropdowns
    document.addEventListener("click", (e) => {
      if (window.AdminPanelUsers && this.elements.autocompleteDropdown) {
        if (
          !this.elements.searchUsuarioInput?.contains(e.target) &&
          !this.elements.autocompleteDropdown.contains(e.target)
        ) {
          window.AdminPanelUsers.ocultarDropdown()
        }
      }
    })

    console.log("✅ Event listeners principales configurados")
  },

  // Cargar datos iniciales
  async cargarDatosIniciales() {
    console.log("📊 Cargando datos iniciales...")

    if (!window.AdminPanelAPI) {
      console.error("❌ AdminPanelAPI no está disponible")
      return
    }

    try {
      // Cargar catálogos primero
      await window.AdminPanelAPI.cargarCatalogos()

      // Cargar estadísticas del dashboard
      await window.AdminPanelAPI.cargarEstadisticas()

      console.log("✅ Datos iniciales cargados")
    } catch (error) {
      console.error("❌ Error cargando datos iniciales:", error)
      window.AdminPanelUtils?.mostrarNotificacion("Error cargando datos iniciales: " + error.message, "error")
    }
  },

  // Configurar módulos específicos
  setupModulos() {
    console.log("🔧 Configurando módulos específicos...")

    // Configurar módulo de usuarios
    if (window.AdminPanelUsers) {
      window.AdminPanelUsers.setupEventListeners()
    }

    // Configurar módulo de cursos
    if (window.AdminPanelCourses) {
      window.AdminPanelCourses.setupEventListeners()
    }

    // Configurar módulo de modales
    if (window.AdminPanelModals) {
      window.AdminPanelModals.setupEventListeners()
    }

    console.log("✅ Módulos configurados")
  },

  // Manejar botón "Agregar Nuevo"
  manejarAgregarNuevo() {
    console.log("➕ Manejando agregar nuevo para sección:", this.currentSection)

    switch (this.currentSection) {
      case "usuarios":
      case "gestionar-usuario":
        if (window.AdminPanelNavigation) {
          window.AdminPanelNavigation.cambiarSeccion("gestionar-usuario")
        }
        if (window.AdminPanelUsers) {
          window.AdminPanelUsers.cambiarModoUsuario("add")
        }
        break

      case "cursos":
        if (window.AdminPanelModals) {
          window.AdminPanelModals.abrirModalCurso()
        }
        break

      case "admins":
        if (window.AdminPanelModals) {
          window.AdminPanelModals.abrirModalAdmin()
        }
        break

      default:
        window.AdminPanelUtils?.mostrarNotificacion("Función no disponible para esta sección", "info")
    }
  },

  // Cerrar sesión
  async cerrarSesion() {
    try {
      if (!confirm("¿Estás seguro que deseas cerrar sesión?")) {
        return
      }

      console.log("🚪 Cerrando sesión...")

      // Llamar al endpoint de logout
      await fetch("api/logout.php", { method: "POST" })

      // Limpiar datos locales
      this.limpiarDatosLocales()

      // Redirigir al login
      window.location.href = "admin-login.html"
    } catch (error) {
      console.error("❌ Error cerrando sesión:", error)
      // Forzar redirección en caso de error
      window.location.href = "admin-login.html"
    }
  },

  // Limpiar datos locales
  limpiarDatosLocales() {
    // Limpiar variables globales
    this.catalogos = {}
    this.cursosConUsuarios = []
    this.currentSection = "dashboard"
    this.currentUserMode = "add"

    // Limpiar localStorage si se usa
    try {
      localStorage.removeItem("adminPanel")
    } catch (error) {
      console.warn("⚠️ No se pudo limpiar localStorage:", error)
    }
  },

  // Manejar errores de inicialización
  manejarErrorInicializacion(error) {
    console.error("💥 Error crítico en inicialización:", error)

    // Mostrar mensaje de error al usuario
    const errorHTML = `
      <div style="
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #f8d7da;
        color: #721c24;
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #f5c6cb;
        text-align: center;
        z-index: 10000;
        max-width: 400px;
      ">
        <h3>Error de Inicialización</h3>
        <p>No se pudo inicializar el panel de administración.</p>
        <p><strong>Error:</strong> ${error.message}</p>
        <button onclick="window.location.reload()" style="
          background: #dc3545;
          color: white;
          border: none;
          padding: 10px 20px;
          border-radius: 3px;
          cursor: pointer;
          margin-top: 10px;
        ">
          Recargar Página
        </button>
      </div>
    `

    document.body.insertAdjacentHTML("beforeend", errorHTML)
  },

  // Método para debugging
  debug() {
    console.log("🐛 Estado actual del AdminPanel:", {
      currentSection: this.currentSection,
      currentUserMode: this.currentUserMode,
      catalogos: this.catalogos,
      cursosConUsuarios: this.cursosConUsuarios.length,
      elements: Object.keys(this.elements),
    })
  },
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  console.log("📄 DOM cargado, inicializando AdminPanel...")
  window.AdminPanel.init()
})

// Manejar errores globales
window.addEventListener("error", (event) => {
  console.error("💥 Error global capturado:", event.error)
})

// Manejar promesas rechazadas
window.addEventListener("unhandledrejection", (event) => {
  console.error("💥 Promesa rechazada no manejada:", event.reason)
})
