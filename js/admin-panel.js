document.addEventListener("DOMContentLoaded", () => {
  console.log("=== INICIANDO ADMIN PANEL ===")

  // Referencias a elementos del DOM
  const navItems = document.querySelectorAll(".nav-item")
  const contentSections = document.querySelectorAll(".content-section")
  const pageTitle = document.getElementById("pageTitle")
  const pageSubtitle = document.getElementById("pageSubtitle")
  const logoutBtn = document.getElementById("logoutBtn")
  const addNewBtn = document.getElementById("addNewBtn")

  // Modales
  const cursoModal = document.getElementById("cursoModal")
  const adminModal = document.getElementById("adminModal")
  const usuarioDetallesModal = document.getElementById("usuarioDetallesModal")
  const listaUsuariosModal = document.getElementById("listaUsuariosModal")

  // Elementos de gestión de usuarios
  const tabButtons = document.querySelectorAll(".tab-button")
  const addUserMode = document.getElementById("addUserMode")
  const updateUserMode = document.getElementById("updateUserMode")
  const searchUsuarioInput = document.getElementById("searchUsuarioInput")
  const clearSearchBtn = document.getElementById("clearSearchBtn")
  const autocompleteDropdown = document.getElementById("autocompleteDropdown")
  const updateFormContainer = document.getElementById("updateFormContainer")
  const updateUsuarioForm = document.getElementById("updateUsuarioForm")
  const cancelUpdateBtn = document.getElementById("cancelUpdateBtn")
  const userFoundAlert = document.getElementById("userFoundAlert")
  const clearSelectionBtn = document.getElementById("clearSelectionBtn")

  // Nuevos elementos
  const coursesGrid = document.getElementById("coursesGrid")
  const limpiarCursosBtn = document.getElementById("limpiarCursosBtn")

  // Variables globales
  let currentSection = "dashboard"
  let currentUserMode = "add"
  let cursosConUsuarios = []
  let editingCourseId = null
  let searchTimeout = null
  let selectedUser = null
  let catalogos = {
    tipos_seguros: [],
    salas: [],
    instructores: [],
  }

  // Inicializar panel
  inicializarPanel()

  // Event listeners para navegación
  navItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault()
      const section = item.dataset.section
      cambiarSeccion(section)
    })
  })

  // Event listeners para tabs de gestión de usuarios
  tabButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault()
      const mode = button.dataset.mode
      cambiarModoUsuario(mode)
    })
  })

  // Event listener para logout
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      if (confirm("¿Estás seguro que deseas cerrar sesión?")) {
        window.location.href = "admin-login.html"
      }
    })
  }

  // Event listener para botón agregar nuevo
  if (addNewBtn) {
    addNewBtn.addEventListener("click", () => {
      if (currentSection === "cursos") {
        abrirModalCurso()
      } else if (currentSection === "usuarios") {
        window.location.href = "registro.html"
      } else if (currentSection === "admins") {
        abrirModalAdmin()
      } else if (currentSection === "gestionar-usuario") {
        cambiarModoUsuario("add")
      }
    })
  }

  // Event listeners para modales
  setupModalEventListeners()

  // Event listener para limpiar cursos
  if (limpiarCursosBtn) {
    limpiarCursosBtn.addEventListener("click", limpiarCursosTerminados)
  }

  // Event listeners para gestión de usuarios
  setupUsuarioEventListeners()

  // Event listeners para documentación
  setupDocumentacionEventListeners()

  function inicializarPanel() {
    cargarCatalogos()
    cargarEstadisticas()
    cambiarSeccion("dashboard")
  }

  async function cargarCatalogos() {
    try {
      const response = await fetch("api/obtener_catalogos.php")
      const data = await response.json()

      if (data.exito) {
        catalogos = data.catalogos
        console.log("Catálogos cargados:", catalogos)

        // Llenar selects de tipos de seguro
        llenarSelectTiposSeguro()

        // Llenar selects de salas e instructores en modal de curso
        llenarSelectsSalaInstructor()
      }
    } catch (error) {
      console.error("Error cargando catálogos:", error)
    }
  }

  function llenarSelectTiposSeguro() {
    const selects = [document.getElementById("addTipoSeguro"), document.getElementById("updateTipoSeguro")]

    selects.forEach((select) => {
      if (select) {
        select.innerHTML = '<option value="">Seleccionar...</option>'
        catalogos.tipos_seguros.forEach((tipo) => {
          select.innerHTML += `<option value="${tipo.nombre_seguro}">${tipo.nombre_seguro}</option>`
        })
      }
    })
  }

  function llenarSelectsSalaInstructor() {
    // Llenar select de salas
    const salaSelect = document.getElementById("salaCurso")
    if (salaSelect) {
      salaSelect.innerHTML = '<option value="">Seleccionar sala...</option>'
      catalogos.salas.forEach((sala) => {
        salaSelect.innerHTML += `<option value="${sala.nombre_sala}">${sala.nombre_sala}</option>`
      })
    }

    // Llenar select de instructores
    const instructorSelect = document.getElementById("instructorCurso")
    if (instructorSelect) {
      instructorSelect.innerHTML = '<option value="">Seleccionar instructor...</option>'
      catalogos.instructores.forEach((instructor) => {
        instructorSelect.innerHTML += `<option value="${instructor.nombre_instructor}">${instructor.nombre_instructor}</option>`
      })
    }
  }

  function cambiarModoUsuario(mode) {
    currentUserMode = mode

    // Actualizar tabs
    tabButtons.forEach((btn) => {
      btn.classList.remove("active")
      if (btn.dataset.mode === mode) {
        btn.classList.add("active")
      }
    })

    // Mostrar/ocultar modos
    if (mode === "add") {
      addUserMode.classList.add("active")
      updateUserMode.classList.remove("active")
    } else {
      addUserMode.classList.remove("active")
      updateUserMode.classList.add("active")
      // Limpiar búsqueda al cambiar a modo actualizar
      limpiarBusquedaCompleta()
    }
  }

  function setupModalEventListeners() {
    const closeCursoModal = document.getElementById("closeCursoModal")
    const closeAdminModal = document.getElementById("closeAdminModal")
    const cancelCursoBtn = document.getElementById("cancelCursoBtn")
    const cancelAdminBtn = document.getElementById("cancelAdminBtn")
    const saveCursoBtn = document.getElementById("saveCursoBtn")
    const saveAdminBtn = document.getElementById("saveAdminBtn")

    if (closeCursoModal) closeCursoModal.addEventListener("click", cerrarModalCurso)
    if (closeAdminModal) closeAdminModal.addEventListener("click", cerrarModalAdmin)
    if (cancelCursoBtn) cancelCursoBtn.addEventListener("click", cerrarModalCurso)
    if (cancelAdminBtn) cancelAdminBtn.addEventListener("click", cerrarModalAdmin)
    if (saveCursoBtn) saveCursoBtn.addEventListener("click", guardarCurso)
    if (saveAdminBtn) saveAdminBtn.addEventListener("click", guardarAdmin)

    // Event listeners para modal de detalles de usuario
    const closeUsuarioDetallesModal = document.getElementById("closeUsuarioDetallesModal")
    const cerrarDetallesBtn = document.getElementById("cerrarDetallesBtn")
    if (closeUsuarioDetallesModal) closeUsuarioDetallesModal.addEventListener("click", cerrarModalUsuarioDetalles)
    if (cerrarDetallesBtn) cerrarDetallesBtn.addEventListener("click", cerrarModalUsuarioDetalles)

    // Event listeners para modal de lista de usuarios
    const closeListaUsuariosModal = document.getElementById("closeListaUsuariosModal")
    const cerrarListaBtn = document.getElementById("cerrarListaBtn")
    const imprimirListaBtn = document.getElementById("imprimirListaBtn")
    const exportarListaBtn = document.getElementById("exportarListaBtn")

    if (closeListaUsuariosModal) closeListaUsuariosModal.addEventListener("click", cerrarModalListaUsuarios)
    if (cerrarListaBtn) cerrarListaBtn.addEventListener("click", cerrarModalListaUsuarios)
    if (imprimirListaBtn) imprimirListaBtn.addEventListener("click", imprimirLista)
    if (exportarListaBtn) exportarListaBtn.addEventListener("click", exportarLista)
  }

  function setupUsuarioEventListeners() {
    // Event listener para formulario de añadir usuario
    const addUsuarioForm = document.getElementById("addUsuarioForm")
    if (addUsuarioForm) {
      addUsuarioForm.addEventListener("submit", async (e) => {
        e.preventDefault()

        const formData = new FormData(addUsuarioForm)

        try {
          const response = await fetch("api/guardar_usuario_completo.php", {
            method: "POST",
            body: formData,
          })

          const result = await response.json()

          if (result.exito) {
            alert("Usuario guardado exitosamente")
            addUsuarioForm.reset()
            cargarEstadisticas()
            if (currentSection === "usuarios") {
              cargarUsuarios()
            }
          } else {
            alert("Error al guardar usuario: " + result.mensaje)
          }
        } catch (error) {
          console.error("Error:", error)
          alert("Error de conexión al guardar usuario")
        }
      })
    }

    // Event listeners para búsqueda avanzada con autocompletado
    if (searchUsuarioInput) {
      searchUsuarioInput.addEventListener("input", manejarBusquedaAutocompletado)
      searchUsuarioInput.addEventListener("focus", mostrarDropdownSiTieneResultados)
      searchUsuarioInput.addEventListener("keydown", manejarTeclasAutocompletado)
    }

    // Event listener para limpiar búsqueda
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener("click", limpiarBusquedaCompleta)
    }

    // Event listener para limpiar selección
    if (clearSelectionBtn) {
      clearSelectionBtn.addEventListener("click", limpiarBusquedaCompleta)
    }

    // Event listener para cancelar actualización
    if (cancelUpdateBtn) {
      cancelUpdateBtn.addEventListener("click", limpiarBusquedaCompleta)
    }

    // Event listener para formulario de actualización
    if (updateUsuarioForm) {
      updateUsuarioForm.addEventListener("submit", async (e) => {
        e.preventDefault()

        const formData = new FormData(updateUsuarioForm)

        try {
          const response = await fetch("api/actualizar_usuario_completo.php", {
            method: "POST",
            body: formData,
          })

          const result = await response.json()

          if (result.exito) {
            alert("Usuario actualizado exitosamente")
            limpiarBusquedaCompleta()
            cargarEstadisticas()
            if (currentSection === "usuarios") {
              cargarUsuarios()
            }
          } else {
            alert("Error al actualizar usuario: " + result.mensaje)
          }
        } catch (error) {
          console.error("Error:", error)
          alert("Error de conexión al actualizar usuario")
        }
      })
    }

    // Event listeners para mostrar/ocultar cédula de afiliación
    const addDerechohabienteRadios = document.querySelectorAll('#addUserMode input[name="es_derechohabiente"]')
    addDerechohabienteRadios.forEach((radio) => {
      radio.addEventListener("change", () => {
        const docCedulaAfiliacion = document.getElementById("addDocCedulaAfiliacion")
        if (radio.value === "1" && radio.checked) {
          if (docCedulaAfiliacion) docCedulaAfiliacion.style.display = "block"
        } else if (radio.value === "0" && radio.checked) {
          if (docCedulaAfiliacion) docCedulaAfiliacion.style.display = "none"
        }
      })
    })

    const updateDerechohabienteRadios = document.querySelectorAll('#updateUserMode input[name="es_derechohabiente"]')
    updateDerechohabienteRadios.forEach((radio) => {
      radio.addEventListener("change", () => {
        const docCedulaAfiliacion = document.getElementById("updateDocCedulaAfiliacion")
        if (radio.value === "1" && radio.checked) {
          if (docCedulaAfiliacion) docCedulaAfiliacion.style.display = "block"
        } else if (radio.value === "0" && radio.checked) {
          if (docCedulaAfiliacion) docCedulaAfiliacion.style.display = "none"
        }
      })
    })

    // Event listeners para CURP y extracción automática de fecha
    setupCurpEventListeners()

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".autocomplete-container")) {
        ocultarDropdown()
      }
    })
  }

  function setupCurpEventListeners() {
    // CURP para modo añadir
    const addCurpInput = document.getElementById("addCurp")
    const addFechaNacimientoInput = document.getElementById("addFechaNacimiento")
    const addFechaAutoNotice = document.getElementById("addFechaAutoNotice")

    if (addCurpInput) {
      addCurpInput.addEventListener("input", function () {
        this.value = this.value.toUpperCase()
        if (this.value.trim() && this.value.length >= 10) {
          const fechaExtraida = extraerFechaDeCURP(this.value)
          if (fechaExtraida && addFechaNacimientoInput) {
            addFechaNacimientoInput.value = fechaExtraida
            if (addFechaAutoNotice) {
              addFechaAutoNotice.style.display = "block"
            }
            // Efecto visual
            addFechaNacimientoInput.style.background = "#e8f5e8"
            setTimeout(() => {
              addFechaNacimientoInput.style.background = ""
            }, 2000)
          }
        }
      })
    }

    // CURP para modo actualizar
    const updateCurpInput = document.getElementById("updateCurp")
    const updateFechaNacimientoInput = document.getElementById("updateFechaNacimiento")
    const updateFechaAutoNotice = document.getElementById("updateFechaAutoNotice")

    if (updateCurpInput) {
      updateCurpInput.addEventListener("input", function () {
        this.value = this.value.toUpperCase()
        if (this.value.trim() && this.value.length >= 10) {
          const fechaExtraida = extraerFechaDeCURP(this.value)
          if (fechaExtraida && updateFechaNacimientoInput) {
            updateFechaNacimientoInput.value = fechaExtraida
            if (updateFechaAutoNotice) {
              updateFechaAutoNotice.style.display = "block"
            }
            // Efecto visual
            updateFechaNacimientoInput.style.background = "#e8f5e8"
            setTimeout(() => {
              updateFechaNacimientoInput.style.background = ""
            }, 2000)
          }
        }
      })
    }
  }

  function extraerFechaDeCURP(curp) {
    console.log("🔍 Extrayendo fecha del CURP:", curp)

    if (!curp || curp.length < 10) {
      console.log("❌ CURP muy corto o vacío")
      return null
    }

    try {
      const yearStr = curp.substring(4, 6)
      const monthStr = curp.substring(6, 8)
      const dayStr = curp.substring(8, 10)

      let year = Number.parseInt(yearStr, 10)
      const month = Number.parseInt(monthStr, 10)
      const day = Number.parseInt(dayStr, 10)

      if (isNaN(year) || isNaN(month) || isNaN(day)) {
        console.log("❌ Componentes de fecha no válidos")
        return null
      }

      if (month < 1 || month > 12 || day < 1 || day > 31) {
        console.log("❌ Fecha inválida en CURP")
        return null
      }

      // Determinar el siglo correcto
      const currentYear = new Date().getFullYear()
      const currentTwoDigitYear = currentYear % 100

      if (year <= currentTwoDigitYear + 5) {
        year += 2000
      } else {
        year += 1900
      }

      // Validar que la fecha sea válida
      const fecha = new Date(year, month - 1, day)
      if (fecha.getFullYear() !== year || fecha.getMonth() !== month - 1 || fecha.getDate() !== day) {
        console.log("❌ Fecha no válida")
        return null
      }

      // Validar que no sea una fecha futura
      if (fecha > new Date()) {
        console.log("❌ Fecha futura no válida")
        return null
      }

      const fechaFormateada = `${year}-${month.toString().padStart(2, "0")}-${day.toString().padStart(2, "0")}`
      console.log("✅ Fecha extraída:", fechaFormateada)
      return fechaFormateada
    } catch (error) {
      console.error("Error al extraer fecha del CURP:", error)
      return null
    }
  }

  // Funciones para autocompletado mejoradas
  function manejarBusquedaAutocompletado() {
    if (!searchUsuarioInput) return

    const query = searchUsuarioInput.value.trim()

    if (query.length > 0 && clearSearchBtn) {
      clearSearchBtn.style.display = "block"
    } else if (clearSearchBtn) {
      clearSearchBtn.style.display = "none"
      ocultarDropdown()
      return
    }

    clearTimeout(searchTimeout)

    if (query.length < 2) {
      ocultarDropdown()
      return
    }

    // Mostrar indicador de carga
    if (autocompleteDropdown) {
      autocompleteDropdown.innerHTML = `
        <div class="autocomplete-item loading">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Buscando usuarios...</span>
        </div>
      `
      autocompleteDropdown.style.display = "block"
    }

    searchTimeout = setTimeout(() => {
      buscarUsuarios(query)
    }, 300)
  }

  function manejarTeclasAutocompletado(e) {
    if (!autocompleteDropdown || autocompleteDropdown.style.display === "none") return

    const items = autocompleteDropdown.querySelectorAll(".autocomplete-item:not(.no-results):not(.loading)")
    const activeItem = autocompleteDropdown.querySelector(".autocomplete-item.active")
    let currentIndex = -1

    if (activeItem) {
      currentIndex = Array.from(items).indexOf(activeItem)
    }

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault()
        if (currentIndex < items.length - 1) {
          if (activeItem) activeItem.classList.remove("active")
          items[currentIndex + 1].classList.add("active")
        }
        break

      case "ArrowUp":
        e.preventDefault()
        if (currentIndex > 0) {
          if (activeItem) activeItem.classList.remove("active")
          items[currentIndex - 1].classList.add("active")
        }
        break

      case "Enter":
        e.preventDefault()
        if (activeItem && !activeItem.classList.contains("no-results") && !activeItem.classList.contains("loading")) {
          const usuario = JSON.parse(activeItem.dataset.usuario)
          seleccionarUsuario(usuario)
        }
        break

      case "Escape":
        ocultarDropdown()
        searchUsuarioInput.blur()
        break
    }
  }

  async function buscarUsuarios(query) {
    try {
      console.log("🔍 Buscando usuarios con query:", query)

      const response = await fetch(`api/buscar_usuarios_autocompletado.php?q=${encodeURIComponent(query)}`)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const text = await response.text()
      console.log("📥 Respuesta del servidor:", text.substring(0, 200))

      let data
      try {
        data = JSON.parse(text)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        throw new Error("Respuesta del servidor no válida")
      }

      if (data.exito) {
        console.log("✅ Usuarios encontrados:", data.usuarios.length)
        mostrarResultadosAutocompletado(data.usuarios)
      } else {
        console.log("❌ No se encontraron usuarios:", data.mensaje)
        mostrarSinResultados(data.mensaje)
      }
    } catch (error) {
      console.error("Error en búsqueda:", error)
      mostrarErrorBusqueda(error.message)
    }
  }

  function mostrarResultadosAutocompletado(usuarios) {
    if (!autocompleteDropdown) return

    if (usuarios.length === 0) {
      mostrarSinResultados("No se encontraron usuarios")
      return
    }

    const resultadosHTML = usuarios
      .map(
        (usuario) => `
      <div class="autocomplete-item" data-usuario='${JSON.stringify(usuario)}'>
        <div class="autocomplete-item-content">
          <div class="autocomplete-name">
            <i class="fas fa-user"></i>
            <strong>${usuario.nombre_completo}</strong>
          </div>
          <div class="autocomplete-details">
            <span class="autocomplete-curp">
              <i class="fas fa-id-card"></i> ${usuario.curp || "Sin CURP"}
            </span>
            <span class="autocomplete-age">
              <i class="fas fa-birthday-cake"></i> ${usuario.edad} años
            </span>
            <span class="autocomplete-date">
              <i class="fas fa-calendar"></i> ${usuario.fecha_registro_formateada}
            </span>
          </div>
        </div>
        <div class="autocomplete-action">
          <i class="fas fa-arrow-right"></i>
        </div>
      </div>
    `,
      )
      .join("")

    autocompleteDropdown.innerHTML = resultadosHTML
    autocompleteDropdown.style.display = "block"

    // Agregar event listeners
    autocompleteDropdown.querySelectorAll(".autocomplete-item").forEach((item, index) => {
      if (!item.classList.contains("no-results") && !item.classList.contains("loading")) {
        item.addEventListener("click", () => {
          const usuario = JSON.parse(item.dataset.usuario)
          seleccionarUsuario(usuario)
        })

        item.addEventListener("mouseenter", () => {
          autocompleteDropdown.querySelectorAll(".autocomplete-item").forEach((i) => i.classList.remove("active"))
          item.classList.add("active")
        })
      }
    })
  }

  function mostrarSinResultados(mensaje) {
    if (!autocompleteDropdown) return

    autocompleteDropdown.innerHTML = `
      <div class="autocomplete-item no-results">
        <i class="fas fa-info-circle"></i>
        <span>${mensaje}</span>
      </div>
    `
    autocompleteDropdown.style.display = "block"
  }

  function mostrarErrorBusqueda(mensaje) {
    if (!autocompleteDropdown) return

    autocompleteDropdown.innerHTML = `
      <div class="autocomplete-item error">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Error: ${mensaje}</span>
      </div>
    `
    autocompleteDropdown.style.display = "block"
  }

  function mostrarDropdownSiTieneResultados() {
    if (autocompleteDropdown && autocompleteDropdown.children.length > 0) {
      autocompleteDropdown.style.display = "block"
    }
  }

  function ocultarDropdown() {
    if (autocompleteDropdown) {
      autocompleteDropdown.style.display = "none"
      autocompleteDropdown.querySelectorAll(".autocomplete-item").forEach((i) => i.classList.remove("active"))
    }
  }

  function seleccionarUsuario(usuario) {
    console.log("👤 Usuario seleccionado:", usuario)
    console.log("📋 Datos completos del usuario:", JSON.stringify(usuario, null, 2))

    selectedUser = usuario
    if (searchUsuarioInput) searchUsuarioInput.value = usuario.nombre_completo
    ocultarDropdown()
    llenarDatosUsuario(usuario)

    if (userFoundAlert) {
      userFoundAlert.style.display = "flex"
    }

    if (updateFormContainer) {
      updateFormContainer.style.display = "block"
    }

    // Scroll suave hacia el formulario
    setTimeout(() => {
      updateFormContainer.scrollIntoView({ behavior: "smooth", block: "start" })
    }, 100)
  }

  function llenarDatosUsuario(usuario) {
    console.log("📝 Llenando datos del usuario:", usuario)

    // Mostrar información del usuario seleccionado
    const selectedUserName = document.getElementById("selectedUserName")
    const selectedUserId = document.getElementById("selectedUserId")
    const selectedUserCurp = document.getElementById("selectedUserCurp")
    const selectedUserAge = document.getElementById("selectedUserAge")

    if (selectedUserName) selectedUserName.textContent = usuario.nombre_completo
    if (selectedUserId) selectedUserId.textContent = usuario.id
    if (selectedUserCurp) selectedUserCurp.textContent = usuario.curp || "Sin CURP"
    if (selectedUserAge) selectedUserAge.textContent = usuario.edad || 0

    // Llenar formulario con datos del usuario - TODOS LOS CAMPOS
    const updateUsuarioId = document.getElementById("updateUsuarioId")
    const updateNombre = document.getElementById("updateNombre")
    const updateApellidos = document.getElementById("updateApellidos")
    const updateCurp = document.getElementById("updateCurp")
    const updateFechaNacimiento = document.getElementById("updateFechaNacimiento")
    const updateNumeroUsuario = document.getElementById("updateNumeroUsuario")
    const updateSalud = document.getElementById("updateSalud")

    if (updateUsuarioId) updateUsuarioId.value = usuario.id || ""
    if (updateNombre) updateNombre.value = usuario.nombre || ""
    if (updateApellidos) updateApellidos.value = usuario.apellidos || ""
    if (updateCurp) updateCurp.value = usuario.curp || ""
    if (updateFechaNacimiento) updateFechaNacimiento.value = usuario.fecha_nacimiento || ""
    if (updateNumeroUsuario) updateNumeroUsuario.value = usuario.numero_usuario || ""
    if (updateSalud) updateSalud.value = usuario.salud || ""

    // Datos del tutor
    const updateTutor = document.getElementById("updateTutor")
    const updateNumeroTutor = document.getElementById("updateNumeroTutor")

    if (updateTutor) updateTutor.value = usuario.tutor || ""
    if (updateNumeroTutor) updateNumeroTutor.value = usuario.numero_tutor || ""

    // Derechohabiencia
    const updateDerechohabienteSi = document.getElementById("updateDerechohabienteSi")
    const updateDerechohabienteNo = document.getElementById("updateDerechohabienteNo")
    const updateDocCedulaAfiliacion = document.getElementById("updateDocCedulaAfiliacion")

    if (usuario.es_derechohabiente == 1) {
      if (updateDerechohabienteSi) updateDerechohabienteSi.checked = true
      if (updateDocCedulaAfiliacion) updateDocCedulaAfiliacion.style.display = "block"
    } else {
      if (updateDerechohabienteNo) updateDerechohabienteNo.checked = true
      if (updateDocCedulaAfiliacion) updateDocCedulaAfiliacion.style.display = "none"
    }

    // Tipo de seguro
    const updateTipoSeguro = document.getElementById("updateTipoSeguro")
    if (updateTipoSeguro) updateTipoSeguro.value = usuario.tipo_seguro || ""

    // Dirección
    const updateDireccionCalle = document.getElementById("updateDireccionCalle")
    const updateDireccionNumero = document.getElementById("updateDireccionNumero")
    const updateDireccionColonia = document.getElementById("updateDireccionColonia")
    const updateDireccionCiudad = document.getElementById("updateDireccionCiudad")
    const updateDireccionEstado = document.getElementById("updateDireccionEstado")
    const updateDireccionCp = document.getElementById("updateDireccionCp")

    if (updateDireccionCalle) updateDireccionCalle.value = usuario.direccion_calle || ""
    if (updateDireccionNumero) updateDireccionNumero.value = usuario.direccion_numero || ""
    if (updateDireccionColonia) updateDireccionColonia.value = usuario.direccion_colonia || ""
    if (updateDireccionCiudad) updateDireccionCiudad.value = usuario.direccion_ciudad || ""
    if (updateDireccionEstado) updateDireccionEstado.value = usuario.direccion_estado || ""
    if (updateDireccionCp) updateDireccionCp.value = usuario.direccion_cp || ""

    // Documentos
    const updateDocFotografias = document.getElementById("updateDocFotografias")
    const updateDocActa = document.getElementById("updateDocActa")
    const updateDocCurp = document.getElementById("updateDocCurp")
    const updateDocComprobante = document.getElementById("updateDocComprobante")
    const updateDocIne = document.getElementById("updateDocIne")
    const updateDocCedula = document.getElementById("updateDocCedula")
    const updateDocFotosTutores = document.getElementById("updateDocFotosTutores")
    const updateDocInesTutores = document.getElementById("updateDocInesTutores")

    if (updateDocFotografias) updateDocFotografias.checked = usuario.doc_fotografias == 1
    if (updateDocActa) updateDocActa.checked = usuario.doc_acta_nacimiento == 1
    if (updateDocCurp) updateDocCurp.checked = usuario.doc_curp == 1
    if (updateDocComprobante) updateDocComprobante.checked = usuario.doc_comprobante_domicilio == 1
    if (updateDocIne) updateDocIne.checked = usuario.doc_ine == 1
    if (updateDocCedula) updateDocCedula.checked = usuario.doc_cedula_afiliacion == 1
    if (updateDocFotosTutores) updateDocFotosTutores.checked = usuario.doc_fotos_tutores == 1
    if (updateDocInesTutores) updateDocInesTutores.checked = usuario.doc_ines_tutores == 1

    console.log("✅ Todos los datos del usuario han sido cargados en el formulario")
  }

  function limpiarBusquedaCompleta() {
    console.log("🧹 Limpiando búsqueda completa")

    if (searchUsuarioInput) searchUsuarioInput.value = ""
    if (clearSearchBtn) clearSearchBtn.style.display = "none"
    if (userFoundAlert) userFoundAlert.style.display = "none"
    if (updateFormContainer) updateFormContainer.style.display = "none"

    selectedUser = null
    ocultarDropdown()

    // Limpiar formulario de actualización
    if (updateUsuarioForm) updateUsuarioForm.reset()

    // Ocultar notificaciones automáticas
    const updateFechaAutoNotice = document.getElementById("updateFechaAutoNotice")
    if (updateFechaAutoNotice) updateFechaAutoNotice.style.display = "none"
  }

  function setupDocumentacionEventListeners() {
    const filterAllDocs = document.getElementById("filterAllDocs")
    const filterIncomplete = document.getElementById("filterIncomplete")
    const filterComplete = document.getElementById("filterComplete")

    if (filterAllDocs) filterAllDocs.addEventListener("click", () => cargarDocumentacion("todos"))
    if (filterIncomplete) filterIncomplete.addEventListener("click", () => cargarDocumentacion("incompletos"))
    if (filterComplete) filterComplete.addEventListener("click", () => cargarDocumentacion("completos"))
  }

  function cambiarSeccion(section) {
    console.log("Cambiando a sección:", section)

    // Actualizar navegación
    navItems.forEach((item) => {
      item.classList.remove("active")
      if (item.dataset.section === section) {
        item.classList.add("active")
      }
    })

    // Actualizar contenido
    contentSections.forEach((content) => {
      content.classList.remove("active")
      if (content.id === `${section}-section`) {
        content.classList.add("active")
      }
    })

    // Actualizar título
    actualizarTitulo(section)
    currentSection = section

    // Cargar datos específicos de la sección
    switch (section) {
      case "dashboard":
        cargarEstadisticas()
        break
      case "usuarios":
        cargarUsuarios()
        break
      case "gestionar-usuario":
        // Asegurar que esté en modo añadir por defecto
        cambiarModoUsuario("add")
        break
      case "documentacion":
        cargarDocumentacion()
        break
      case "cursos":
        cargarCursos()
        break
      case "cursos-usuarios":
        cargarCursosConUsuarios()
        break
      case "inscripciones":
        cargarInscripciones()
        break
      case "admins":
        cargarAdministradores()
        break
    }
  }

  function actualizarTitulo(section) {
    const titulos = {
      dashboard: { titulo: "Dashboard", subtitulo: "Resumen general del sistema" },
      usuarios: { titulo: "Gestión de Usuarios", subtitulo: "Administrar usuarios registrados" },
      "gestionar-usuario": {
        titulo: "Gestionar Usuario",
        subtitulo: "Añadir nuevos usuarios o actualizar existentes",
      },
      documentacion: { titulo: "Documentación", subtitulo: "Control de documentos de usuarios" },
      cursos: { titulo: "Gestión de Cursos", subtitulo: "Administrar cursos disponibles" },
      "cursos-usuarios": { titulo: "Cursos y Listas", subtitulo: "Ver listas de usuarios por curso" },
      inscripciones: { titulo: "Inscripciones", subtitulo: "Gestionar inscripciones de usuarios" },
      admins: { titulo: "Administradores", subtitulo: "Gestionar administradores del sistema" },
      
    }

    const info = titulos[section] || { titulo: "Panel", subtitulo: "Administración" }
    if (pageTitle) pageTitle.textContent = info.titulo
    if (pageSubtitle) pageSubtitle.textContent = info.subtitulo
  }

  async function cargarEstadisticas() {
    console.log("Cargando estadísticas...")
    try {
      const response = await fetch("api/estadisticas.php")
      const data = await response.json()

      console.log("Estadísticas recibidas:", data)

      if (data.exito) {
        const stats = data.estadisticas
        const totalUsuarios = document.getElementById("totalUsuarios")
        const totalCursos = document.getElementById("totalCursos")
        const totalInscripciones = document.getElementById("totalInscripciones")
        const usuariosSinDocumentacion = document.getElementById("usuariosSinDocumentacion")

        if (totalUsuarios) totalUsuarios.textContent = stats.total_usuarios || 0
        if (totalCursos) totalCursos.textContent = stats.total_cursos || 0
        if (totalInscripciones) totalInscripciones.textContent = stats.total_inscripciones || 0
        if (usuariosSinDocumentacion) usuariosSinDocumentacion.textContent = stats.usuarios_sin_documentacion || 0
      } else {
        console.error("Error al cargar estadísticas:", data.mensaje)
      }
    } catch (error) {
      console.error("Error al cargar estadísticas:", error)
    }
  }

  async function cargarUsuarios() {
    console.log("=== CARGANDO USUARIOS ===")
    const tableBody = document.getElementById("usuariosTableBody")
    if (!tableBody) return

    tableBody.innerHTML = '<tr><td colspan="8" class="loading-row">Cargando usuarios...</td></tr>'

    try {
      const response = await fetch("api/obtener_usuarios.php")
      const text = await response.text()
      console.log("Response text completo:", text)

      let usuarios
      try {
        usuarios = JSON.parse(text)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        throw new Error("Respuesta del servidor no válida")
      }

      if (Array.isArray(usuarios)) {
        if (usuarios.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="8" class="loading-row">No hay usuarios registrados</td></tr>'
          return
        }

        const usuariosHTML = usuarios
          .map((usuario) => {
            const id = usuario.id_usuario || usuario.id || "N/A"
            const esDerechohabiente = usuario.es_derechohabiente ? "Sí" : "No"
            const documentacionCompleta = usuario.documentacion_completa ? "COMPLETA" : "INCOMPLETA"
            const badgeClass = usuario.documentacion_completa ? "badge-success" : "badge-warning"

            return `
          <tr>
            <td>${id}</td>
            <td>${usuario.nombre || "N/A"} ${usuario.apellidos || ""}</td>
            <td>${usuario.curp || "N/A"}</td>
            <td>${usuario.edad || "N/A"} años</td>
            <td>${esDerechohabiente}</td>
            <td><span class="badge ${badgeClass}"><i class="fas ${usuario.documentacion_completa ? "fa-check" : "fa-exclamation-triangle"}"></i> ${documentacionCompleta}</span></td>
            <td>${usuario.fecha_registro || "N/A"}</td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="verUsuario(${id})" ${id === "N/A" ? "disabled" : ""}>
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-primary btn-sm" onclick="irAGestionarUsuario(${id})" ${id === "N/A" ? "disabled" : ""}>
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-danger btn-sm" onclick="eliminarUsuario(${id})" ${id === "N/A" ? "disabled" : ""}>
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `
          })
          .join("")

        tableBody.innerHTML = usuariosHTML
      } else if (usuarios.error) {
        tableBody.innerHTML = `<tr><td colspan="8" class="loading-row">Error: ${usuarios.mensaje}</td></tr>`
      } else {
        tableBody.innerHTML = '<tr><td colspan="8" class="loading-row">Formato de respuesta no válido</td></tr>'
      }
    } catch (error) {
      console.error("Error al cargar usuarios:", error)
      tableBody.innerHTML = `<tr><td colspan="8" class="loading-row">Error al cargar usuarios: ${error.message}</td></tr>`
    }
  }

  // Función para ir a gestionar usuario desde otras secciones
  window.irAGestionarUsuario = async (idUsuario) => {
    cambiarSeccion("gestionar-usuario")
    cambiarModoUsuario("update")

    // Simular búsqueda y selección del usuario
    try {
      const response = await fetch(`api/obtener_usuario_completo.php?id=${idUsuario}`)
      const data = await response.json()

      if (data.exito) {
        const usuario = data.usuario
        // Crear objeto compatible con la función de selección
        const usuarioFormateado = {
          id: usuario.id_usuario,
          nombre: usuario.nombre,
          apellidos: usuario.apellidos,
          nombre_completo: `${usuario.nombre} ${usuario.apellidos}`,
          curp: usuario.curp,
          edad: usuario.edad,
          fecha_nacimiento: usuario.fecha_nacimiento,
          numero_usuario: usuario.numero_usuario,
          salud: usuario.salud,
          tutor: usuario.tutor,
          numero_tutor: usuario.numero_tutor,
          es_derechohabiente: usuario.es_derechohabiente,
          tipo_seguro: usuario.tipo_seguro,
          direccion_calle: usuario.direccion_calle,
          direccion_numero: usuario.direccion_numero,
          direccion_colonia: usuario.direccion_colonia,
          direccion_ciudad: usuario.direccion_ciudad,
          direccion_estado: usuario.direccion_estado,
          direccion_cp: usuario.direccion_cp,
          doc_fotografias: usuario.doc_fotografias,
          doc_acta_nacimiento: usuario.doc_acta_nacimiento,
          doc_curp: usuario.doc_curp,
          doc_comprobante_domicilio: usuario.doc_comprobante_domicilio,
          doc_ine: usuario.doc_ine,
          doc_cedula_afiliacion: usuario.doc_cedula_afiliacion,
          doc_fotos_tutores: usuario.doc_fotos_tutores,
          doc_ines_tutores: usuario.doc_ines_tutores,
          fecha_registro_formateada: usuario.fecha_registro || "Sin fecha",
        }

        setTimeout(() => {
          seleccionarUsuario(usuarioFormateado)
        }, 500)
      } else {
        alert("Error al cargar datos del usuario: " + data.mensaje)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión al cargar usuario")
    }
  }

  async function cargarDocumentacion(filtro = "todos") {
    console.log("Cargando documentación con filtro:", filtro)
    const tableBody = document.getElementById("documentacionTableBody")
    if (!tableBody) return

    tableBody.innerHTML = '<tr><td colspan="5" class="loading-row">Cargando información de documentación...</td></tr>'

    try {
      const response = await fetch(`api/obtener_usuarios.php`)
      const usuarios = await response.json()

      if (Array.isArray(usuarios)) {
        // Filtrar según el filtro seleccionado
        let usuariosFiltrados = usuarios
        if (filtro === "completos") {
          usuariosFiltrados = usuarios.filter((u) => u.documentacion_completa)
        } else if (filtro === "incompletos") {
          usuariosFiltrados = usuarios.filter((u) => !u.documentacion_completa)
        }

        if (usuariosFiltrados.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="5" class="loading-row">No hay usuarios con este filtro</td></tr>'
          return
        }

        const usuariosHTML = usuariosFiltrados
          .map((usuario) => {
            const progressClass =
              usuario.documentacion_porcentaje === 100
                ? "progress-complete"
                : usuario.documentacion_porcentaje >= 50
                  ? "progress-partial"
                  : "progress-low"

            const documentosFaltantes = usuario.documentos_requeridos - usuario.documentos_completos
            const textoFaltantes =
              documentosFaltantes > 0 ? `${documentosFaltantes} documento(s) faltante(s)` : "Documentación completa"

            return `
            <tr>
              <td>${usuario.id}</td>
              <td>${usuario.nombre_completo}</td>
              <td>
                <div class="documentos-faltantes">
                  ${textoFaltantes}
                </div>
              </td>
              <td>
                <div class="progress-container">
                  <div class="progress-bar ${progressClass}" style="width: ${usuario.documentacion_porcentaje}%"></div>
                  <span class="progress-text">${usuario.documentacion_porcentaje}%</span>
                </div>
              </td>
              <td>
                <button class="btn btn-primary btn-sm" onclick="irAGestionarUsuario(${usuario.id})">
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>
          `
          })
          .join("")

        tableBody.innerHTML = usuariosHTML
      } else {
        tableBody.innerHTML = `<tr><td colspan="5" class="loading-row">Error al cargar documentación</td></tr>`
      }
    } catch (error) {
      console.error("Error al cargar documentación:", error)
      tableBody.innerHTML = '<tr><td colspan="5" class="loading-row">Error al cargar documentación</td></tr>'
    }
  }

  async function cargarCursos() {
    console.log("=== CARGANDO CURSOS ===")
    const tableBody = document.getElementById("cursosTableBody")
    if (!tableBody) return

    tableBody.innerHTML = '<tr><td colspan="10" class="loading-row">Cargando cursos...</td></tr>'

    try {
      const response = await fetch("api/obtener_cursos.php")
      const text = await response.text()

      let data
      try {
        data = JSON.parse(text)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        throw new Error("Respuesta del servidor no válida")
      }

      if (data.exito && Array.isArray(data.cursos)) {
        const cursos = data.cursos

        if (cursos.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="10" class="loading-row">No hay cursos registrados</td></tr>'
          return
        }

        const cursosHTML = cursos
          .map(
            (curso) => `
          <tr>
            <td>${curso.id_curso}</td>
            <td>${curso.nombre_curso}</td>
            <td>${curso.edad_min}</td>
            <td>${curso.edad_max}</td>
            <td>${curso.cupo_maximo || 30}</td>
            <td>${curso.horario || "Por definir"}</td>
            <td>${curso.sala || "Sin asignar"}</td>
            <td>${curso.instructor || "Sin asignar"}</td>
            <td>
              <span class="badge ${curso.activo == 1 ? "badge-success" : "badge-danger"}">
                ${curso.activo == 1 ? "Activo" : "Inactivo"}
              </span>
            </td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="editarCurso(${curso.id_curso})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-danger btn-sm" onclick="eliminarCurso(${curso.id_curso})">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `,
          )
          .join("")

        tableBody.innerHTML = cursosHTML
      } else {
        tableBody.innerHTML = `<tr><td colspan="10" class="loading-row">Error: ${data.mensaje || "Error desconocido"}</td></tr>`
      }
    } catch (error) {
      console.error("Error al cargar cursos:", error)
      tableBody.innerHTML = `<tr><td colspan="10" class="loading-row">Error al cargar cursos: ${error.message}</td></tr>`
    }
  }

  async function cargarCursosConUsuarios() {
    console.log("=== CARGANDO CURSOS CON USUARIOS ===")
    if (!coursesGrid) return

    coursesGrid.innerHTML =
      '<div class="loading-courses"><i class="fas fa-spinner fa-spin"></i> Cargando cursos...</div>'

    try {
      const response = await fetch("api/obtener_cursos_con_usuarios.php")
      const data = await response.json()

      console.log("Datos de cursos con usuarios:", data)

      if (data.exito && Array.isArray(data.cursos)) {
        cursosConUsuarios = data.cursos

        if (cursosConUsuarios.length === 0) {
          coursesGrid.innerHTML =
            '<div class="no-courses"><i class="fas fa-info-circle"></i> No hay cursos disponibles</div>'
          return
        }

        const cursosHTML = cursosConUsuarios
          .map(
            (curso) => `
        <div class="course-card-admin">
          <div class="course-header-admin">
            <h3>${curso.nombre_curso}</h3>
            <span class="course-count">${curso.total_inscritos} usuarios</span>
          </div>
          <div class="course-info-admin">
            <p><i class="fas fa-users"></i> Edad: ${curso.edad_min}-${curso.edad_max} años</p>
            <p><i class="fas fa-clock"></i> ${curso.horario || "Horario por definir"}</p>
            <p><i class="fas fa-chalkboard"></i> Sala: ${curso.sala || "Sin asignar"}</p>
            <p><i class="fas fa-user-tie"></i> Instructor: ${curso.instructor || "Sin asignar"}</p>
            ${
              curso.activo == 1
                ? '<p><i class="fas fa-check-circle" style="color: #10b981;"></i> Curso Activo</p>'
                : '<p><i class="fas fa-times-circle" style="color: #ef4444;"></i> Curso Inactivo</p>'
            }
          </div>
          <div class="course-actions-admin">
            <button class="btn btn-primary btn-sm" onclick="verListaUsuarios(${curso.id_curso})" ${curso.total_inscritos === 0 ? "disabled" : ""}>
              <i class="fas fa-list"></i> Ver Lista (${curso.total_inscritos})
            </button>
            <button class="btn btn-secondary btn-sm" onclick="editarCurso(${curso.id_curso})">
              <i class="fas fa-edit"></i> Editar
            </button>
          </div>
        </div>
      `,
          )
          .join("")

        coursesGrid.innerHTML = cursosHTML
      } else {
        coursesGrid.innerHTML = `<div class="error-message">Error al cargar cursos: ${data.mensaje || "Error desconocido"}</div>`
      }
    } catch (error) {
      console.error("Error al cargar cursos con usuarios:", error)
      coursesGrid.innerHTML = '<div class="error-message">Error de conexión al cargar cursos</div>'
    }
  }

  async function cargarInscripciones() {
    console.log("Cargando inscripciones...")
    const tableBody = document.getElementById("inscripcionesTableBody")
    if (!tableBody) return

    tableBody.innerHTML = '<tr><td colspan="5" class="loading-row">Cargando inscripciones...</td></tr>'

    try {
      const response = await fetch("api/obtener_inscripciones.php")
      const data = await response.json()

      console.log("Datos de inscripciones:", data)

      if (data.exito && Array.isArray(data.inscripciones)) {
        const inscripciones = data.inscripciones

        if (inscripciones.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="5" class="loading-row">No hay inscripciones registradas</td></tr>'
          return
        }

        const inscripcionesHTML = inscripciones
          .map(
            (inscripcion) => `
          <tr>
            <td>${inscripcion.id_inscripcion}</td>
            <td>${inscripcion.nombre_usuario}</td>
            <td>${inscripcion.nombre_curso}</td>
            <td>${inscripcion.fecha_inscripcion_formateada}</td>
            <td>
              <button class="btn btn-danger btn-sm" onclick="eliminarInscripcion(${inscripcion.id_inscripcion})">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `,
          )
          .join("")

        tableBody.innerHTML = inscripcionesHTML
      } else {
        tableBody.innerHTML = `<tr><td colspan="5" class="loading-row">Error: ${data.mensaje || "Error al cargar inscripciones"}</td></tr>`
      }
    } catch (error) {
      console.error("Error al cargar inscripciones:", error)
      tableBody.innerHTML = '<tr><td colspan="5" class="loading-row">Error al cargar inscripciones</td></tr>'
    }
  }

  async function cargarAdministradores() {
    console.log("Cargando administradores...")
    const tableBody = document.getElementById("adminsTableBody")
    if (!tableBody) return

    tableBody.innerHTML = '<tr><td colspan="7" class="loading-row">Cargando administradores...</td></tr>'

    try {
      const response = await fetch("api/obtener_admins.php")
      const data = await response.json()

      if (data.exito && Array.isArray(data.admins)) {
        const admins = data.admins

        if (admins.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="7" class="loading-row">No hay administradores registrados</td></tr>'
          return
        }

        const adminsHTML = admins
          .map(
            (admin) => `
          <tr>
            <td>${admin.id_admin}</td>
            <td>${admin.usuario}</td>
            <td>${admin.nombre}</td>
            <td>${admin.email}</td>
            <td>
              <span class="badge ${admin.activo ? "badge-success" : "badge-danger"}">
                ${admin.activo ? "Activo" : "Inactivo"}
              </span>
            </td>
            <td>${admin.ultimo_acceso || "Nunca"}</td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="editarAdmin(${admin.id_admin})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-danger btn-sm" onclick="eliminarAdmin(${admin.id_admin})">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `,
          )
          .join("")

        tableBody.innerHTML = adminsHTML
      }
    } catch (error) {
      console.error("Error al cargar administradores:", error)
      tableBody.innerHTML = '<tr><td colspan="7" class="loading-row">Error al cargar administradores</td></tr>'
    }
  }

  // Funciones de modales
  function abrirModalCurso(curso = null) {
    console.log("=== ABRIENDO MODAL CURSO ===")
    console.log("Curso recibido:", curso)

    const cursoModalTitle = document.getElementById("cursoModalTitle")
    const cursoForm = document.getElementById("cursoForm")

    if (!cursoModalTitle || !cursoForm) {
      console.error("No se encontraron elementos del modal")
      return
    }

    // Resetear completamente el formulario
    cursoForm.reset()

    // Limpiar explícitamente todos los campos
    const cursoId = document.getElementById("cursoId")
    const nombreCurso = document.getElementById("nombreCurso")
    const edadMinima = document.getElementById("edadMinima")
    const edadMaxima = document.getElementById("edadMaxima")
    const cupoMaximo = document.getElementById("cupoMaximo")
    const horarioCurso = document.getElementById("horarioCurso")
    const estadoCurso = document.getElementById("estadoCurso")
    const salaCurso = document.getElementById("salaCurso")
    const instructorCurso = document.getElementById("instructorCurso")

    if (curso) {
      // MODO EDICIÓN
      console.log("MODO: Editar curso")
      editingCourseId = curso.id_curso
      cursoModalTitle.textContent = "Editar Curso"

      if (cursoId) cursoId.value = curso.id_curso
      if (nombreCurso) nombreCurso.value = curso.nombre_curso || ""
      if (edadMinima) edadMinima.value = curso.edad_min || 0
      if (edadMaxima) edadMaxima.value = curso.edad_max || 100
      if (cupoMaximo) cupoMaximo.value = curso.cupo_maximo || 30
      if (horarioCurso) horarioCurso.value = curso.horario || ""
      if (estadoCurso) estadoCurso.value = curso.activo || 1
      if (salaCurso) salaCurso.value = curso.sala || ""
      if (instructorCurso) instructorCurso.value = curso.instructor || ""

      console.log("Datos cargados para edición:", {
        id: curso.id_curso,
        nombre: curso.nombre_curso,
        edad_min: curso.edad_min,
        edad_max: curso.edad_max,
      })
    } else {


      // MODO CREACIÓN
      console.log("MODO: Crear nuevo curso")
      editingCourseId = null
      cursoModalTitle.textContent = "Nuevo Curso"

      // Asegurar que el ID esté vacío o en 0
      if (cursoId) cursoId.value = ""
      if (nombreCurso) nombreCurso.value = ""
      if (edadMinima) edadMinima.value = ""
      if (edadMaxima) edadMaxima.value = ""
      if (cupoMaximo) cupoMaximo.value = "30"
      if (horarioCurso) horarioCurso.value = ""
      if (estadoCurso) estadoCurso.value = "1"
      if (salaCurso) salaCurso.value = ""
      if (instructorCurso) instructorCurso.value = ""

      console.log("Formulario preparado para nuevo curso")
    }

    if (cursoModal) cursoModal.style.display = "block"
    console.log("=== MODAL CURSO ABIERTO ===")
  }

  function cerrarModalCurso() {
    console.log("=== CERRANDO MODAL CURSO ===")
    if (cursoModal) cursoModal.style.display = "none"

    // Limpiar variables de estado
    editingCourseId = null

    // Resetear formulario
    const cursoForm = document.getElementById("cursoForm")
    if (cursoForm) cursoForm.reset()

    console.log("Modal curso cerrado y limpiado")
  }

  function abrirModalAdmin(admin = null) {
    const adminModalTitle = document.getElementById("adminModalTitle")
    const adminForm = document.getElementById("adminForm")

    if (!adminModalTitle || !adminForm) return

    adminModalTitle.textContent = admin ? "Editar Administrador" : "Nuevo Administrador"
    adminForm.reset()

    if (admin) {
      const adminId = document.getElementById("adminId")
      const usuarioAdmin = document.getElementById("usuarioAdmin")
      const nombreAdmin = document.getElementById("nombreAdmin")
      const emailAdmin = document.getElementById("emailAdmin")
      const passwordAdmin = document.getElementById("passwordAdmin")

      if (adminId) adminId.value = admin.id_admin
      if (usuarioAdmin) usuarioAdmin.value = admin.usuario
      if (nombreAdmin) nombreAdmin.value = admin.nombre
      if (emailAdmin) emailAdmin.value = admin.email
      if (passwordAdmin) passwordAdmin.removeAttribute("required")
    } else {
      const passwordAdmin = document.getElementById("passwordAdmin")
      if (passwordAdmin) passwordAdmin.setAttribute("required", "")
    }

    if (adminModal) adminModal.style.display = "block"
  }

  function cerrarModalAdmin() {
    if (adminModal) adminModal.style.display = "none"
  }

  function cerrarModalListaUsuarios() {
    if (listaUsuariosModal) listaUsuariosModal.style.display = "none"
  }

  function cerrarModalUsuarioDetalles() {
    if (usuarioDetallesModal) usuarioDetallesModal.style.display = "none"
  }

  async function guardarCurso() {
    console.log("=== GUARDANDO CURSO ===")

    const form = document.getElementById("cursoForm")
    if (!form) {
      alert("Error: Formulario no encontrado")
      return
    }

    // Obtener datos del formulario
    const formData = new FormData(form)

    // Verificar el ID del curso
    const cursoIdField = document.getElementById("cursoId")
    const cursoIdValue = cursoIdField ? cursoIdField.value : ""

    console.log("ID del curso desde campo:", cursoIdValue)
    console.log("editingCourseId:", editingCourseId)

    // Lógica para determinar si es nuevo o edición
    if (editingCourseId && editingCourseId > 0) {
      // MODO EDICIÓN
      formData.set("id_curso", editingCourseId.toString())
      console.log("MODO: Actualizar curso existente ID:", editingCourseId)
    } else {
      // MODO CREACIÓN - Asegurar que el ID esté vacío o en 0
      formData.set("id_curso", "0")
      console.log("MODO: Crear nuevo curso")
    }

    console.log("Datos del formulario:")
    for (const [key, value] of formData.entries()) {
      console.log(`${key}: ${value}`)
    }

    try {
      console.log("Enviando petición...")

      const response = await fetch("api/guardar_curso.php", {
        method: "POST",
        body: formData,
      })

      console.log("Response status:", response.status)

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`)
      }

      const text = await response.text()
      console.log("Response text:", text)

      if (!text || text.trim() === "") {
        throw new Error("Respuesta vacía del servidor")
      }

      let result
      try {
        result = JSON.parse(text)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        console.error("Response text:", text)
        throw new Error("Respuesta no es JSON válido")
      }

      console.log("Resultado:", result)

      if (result.exito) {
        const mensaje =
          result.modo === "crear"
            ? `¡Curso creado exitosamente! ID: ${result.id_curso}`
            : "¡Curso actualizado exitosamente!"

        alert(mensaje)
        cerrarModalCurso()
        cargarCursos()
        cargarEstadisticas()
        if (currentSection === "cursos-usuarios") {
          cargarCursosConUsuarios()
        }
      } else {
        let mensaje = "Error al guardar curso: " + (result.mensaje || "Error desconocido")
        if (result.errores && Array.isArray(result.errores)) {
          mensaje += "\n\nDetalles:\n" + result.errores.join("\n")
        }
        alert(mensaje)
      }
    } catch (error) {
      console.error("Error completo:", error)
      alert("Error de conexión al guardar curso: " + error.message)
    }

    console.log("=== FIN GUARDAR CURSO ===")
  }

  async function guardarAdmin() {
    const form = document.getElementById("adminForm")
    if (!form) return

    const formData = new FormData(form)

    try {
      const response = await fetch("api/guardar_admin.php", {
        method: "POST",
        body: formData,
      })

      const result = await response.json()

      if (result.exito) {
        alert("Administrador guardado exitosamente")
        cerrarModalAdmin()
        cargarAdministradores()
        cargarEstadisticas()
      } else {
        alert("Error al guardar administrador: " + result.mensaje)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión")
    }
  }

  async function verListaUsuarios(idCurso) {
    console.log("🔍 Viendo lista de usuarios del curso:", idCurso)

    try {
      // Mostrar modal inmediatamente con loading
      const listaUsuariosTitle = document.getElementById("listaUsuariosTitle")
      const tableBody = document.getElementById("listaUsuariosTableBody")

      if (listaUsuariosTitle) {
        listaUsuariosTitle.textContent = "Cargando lista de usuarios..."
      }

      if (tableBody) {
        tableBody.innerHTML =
          '<tr><td colspan="7" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Cargando usuarios del curso...</td></tr>'
      }

      if (listaUsuariosModal) listaUsuariosModal.style.display = "block"

      const response = await fetch(`api/obtener_tabla_curso.php?id_curso=${idCurso}`)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const text = await response.text()
      console.log("📥 Respuesta del servidor:", text.substring(0, 200))

      let data
      try {
        data = JSON.parse(text)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        throw new Error("Respuesta del servidor no válida")
      }

      console.log("📊 Datos de la lista de usuarios:", data)

      if (data.exito) {
        const curso = data.curso
        const usuarios = data.usuarios

        if (listaUsuariosTitle) {
          listaUsuariosTitle.textContent = `Lista de Usuarios - ${curso.nombre_curso}`
        }

        if (tableBody) {
          if (usuarios.length === 0) {
            tableBody.innerHTML =
              '<tr><td colspan="9" class="no-data"><i class="fas fa-info-circle"></i> No hay usuarios inscritos en este curso</td></tr>'
          } else {
            const usuariosHTML = usuarios
              .map(
                (usuario, index) => `
              <tr>
                <td>${index + 1}</td>
                <td>${usuario.nombre} ${usuario.apellidos}</td>
                <td>${usuario.tutor || "Sin tutor"}</td>
                <td>${usuario.edad} años</td>
                <td>${usuario.numero_tutor || "N/A"}</td>
                <td>${usuario.salud || "Ninguna"}</td>
                </tr>
            `,
              )
              .join("")

            tableBody.innerHTML = usuariosHTML
          }
        }

        // Guardar datos para exportar/imprimir
        window.currentCourseData = {
          curso: curso,
          usuarios: usuarios,
        }

        console.log("✅ Lista de usuarios cargada correctamente")
      } else {
        console.error("❌ Error del servidor:", data.mensaje)
        alert("Error al cargar lista de usuarios: " + data.mensaje)
        cerrarModalListaUsuarios()
      }
    } catch (error) {
      console.error("💥 Error completo:", error)
      alert("Error de conexión al cargar lista de usuarios: " + error.message)
      cerrarModalListaUsuarios()
    }
  }

  function imprimirLista() {
    if (!window.currentCourseData) {
      alert("No hay datos para imprimir")
      return
    }

    const { curso, usuarios } = window.currentCourseData

    const ventanaImpresion = window.open("", "_blank")
    ventanaImpresion.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Lista de Usuarios - ${curso.nombre_curso}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .header { text-align: center; margin-bottom: 30px; }
          .course-info { margin-bottom: 20px; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f2f2f2; }
          .footer { margin-top: 30px; text-align: center; font-size: 12px; }
        </style>
      </head>
      <body>
        <div class="header">
          <h3>DIRECCIÓN GENERAL ADJUNTA DE SEGURIDAD Y BIENESTAR SOCIAL</h3>
          <h3>CENTRO EDUCATIVO CUEMANCO</h3>
          <h3>DEPARTAMENTO DE HABILIDADES PEDAGOGICAS Y EDUCATIVAS</h3>
          <h4>Lista de Usuarios</h4>
        </div>
        <div class="course-info">
          <p><strong>Curso:</strong> ${curso.nombre_curso}</p>
          <p><strong>Horario:</strong> ${curso.horario || "Por definir"}</p>
          <p><strong>Edad:</strong> ${curso.edad_min}-${curso.edad_max} años</p>
          <p><strong>Total de usuarios:</strong> ${usuarios.length}</p>
        </div>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre Completo</th>
              <th>Tutor</th>
              <th>Edad</th>
              <th>Telefono</th>
              <th>Padecimiento</th>
              <th>Asistencia</th>
              <th>Documentacion</th>
              </tr>
          </thead>
          <tbody>
            ${usuarios
              .map(
                (usuario, index) => `
              <tr>
                <td>${index + 1}</td>
                <td>${usuario.nombre} ${usuario.apellidos}</td>
                <td>${usuario.tutor || "Sin tutor"}</td>
                <td>${usuario.edad} años</td>
                <td>${usuario.numero_tutor || "N/A"}</td>
                <td>${usuario.salud || "ninguna"} </td>
                <td></td>
                <td></td>
              </tr>
            `,
              )
              .join("")}
          </tbody>
        </table>
        <div class="footer">
          <p>Casa Telmex - Sistema de Gestión de Cursos</p>
        </div>
      </body>
      </html>
    `)

    ventanaImpresion.document.close()
    ventanaImpresion.print()
  }

  function exportarLista() {
  if (!window.currentCourseData || !window.currentCourseData.usuarios || window.currentCourseData.usuarios.length === 0) {
    showAlert('error', 'No hay datos de usuarios para exportar', 3000);
    return;
  }

  const { curso, usuarios } = window.currentCourseData;

  try {
    // Información institucional
    const headerLines = [
      'DIRECCIÓN GENERAL ADJUNTA DE SEGURIDAD Y BIENESTAR SOCIAL',
      'CENTRO EDUCATIVO CUEMANCO',
      'DEPARTAMENTO DE HABILIDADES PEDAGOGICAS Y EDUCATIVAS',
      `LISTA DE USUARIOS - CURSO: ${curso.nombre_curso}`,
      `FECHA DE EXPORTACIÓN: ${formatDateForHeader(new Date())}`,
      '' // Línea vacía para separar
    ];

    // Cabeceras del CSV
    const headers = [
      'No.', 
      'Nombre Completo',
      'CURP',
      'Edad',
      'Tutor',
      'Teléfono Tutor',
      'Documentación Completa'
    ];

    // Construcción del contenido CSV
    let csvContent = headerLines.join('\n') + '\n';
    csvContent += headers.join(',') + '\n';

    usuarios.forEach((usuario, index) => {
      const row = [
        index + 1,
        `"${usuario.nombre} ${usuario.apellidos || ''}"`.replace(/"/g, '""'),
        `"${usuario.curp || 'Sin CURP'}"`,
        `"${usuario.edad || 0} años"`,
        `"${usuario.tutor || 'Sin tutor'}"`,
        `"${usuario.numero_tutor || 'N/A'}"`,
        `"${usuario.documentacion_completa ? 'Completa' : 'Incompleta'}"`
      ];
      
      csvContent += row.join(',') + '\n';
    });

    // Creación y descarga del archivo
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const filename = `lista_usuarios_${normalizeFilename(curso.nombre_curso)}_${formatDate(new Date())}.csv`;

    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    
    // Limpieza
    setTimeout(() => {
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }, 100);

  } catch (error) {
    console.error('Error al exportar lista:', error);
    showAlert('error', 'Ocurrió un error al generar el archivo', 3000);
  }
}

// Función para formatear fecha del encabezado
function formatDateForHeader(date) {
  const options = { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  };
  return date.toLocaleDateString('es-MX', options);
}

// Funciones auxiliares
function normalizeFilename(str) {
  return str.replace(/[^a-z0-9áéíóúüñÁÉÍÓÚÜÑ]/gi, '_').replace(/_+/g, '_');
}

function formatDate(date) {
  const pad = num => num.toString().padStart(2, '0');
  return `${date.getFullYear()}${pad(date.getMonth()+1)}${pad(date.getDate())}`;
}

// Ejemplo de función showAlert (deberías tener una implementación similar)
function showAlert(type, message, duration) {
  // Implementa tu propio sistema de notificaciones
  alert(`${type.toUpperCase()}: ${message}`);
}

  async function limpiarCursosTerminados() {
    if (!cursosConUsuarios || cursosConUsuarios.length === 0) {
      alert("No hay cursos para limpiar")
      return
    }

    const cursosConInscripciones = cursosConUsuarios.filter((curso) => curso.total_inscritos > 0)

    if (cursosConInscripciones.length === 0) {
      alert("No hay cursos con inscripciones para limpiar")
      return
    }

    let mensaje = "Selecciona los cursos que deseas limpiar (eliminar inscripciones):\n\n"
    cursosConInscripciones.forEach((curso, index) => {
      mensaje += `${index + 1}. ${curso.nombre_curso} (${curso.total_inscritos} usuarios)\n`
    })

    const seleccion = prompt(
      mensaje + "\nIngresa los números separados por comas (ej: 1,3,5) o 'todos' para limpiar todos:",
    )

    if (!seleccion) return

    let cursosALimpiar = []

    if (seleccion.toLowerCase() === "todos") {
      cursosALimpiar = cursosConInscripciones.map((curso) => curso.id_curso)
    } else {
      const indices = seleccion.split(",").map((i) => Number.parseInt(i.trim()) - 1)
      cursosALimpiar = indices
        .filter((i) => i >= 0 && i < cursosConInscripciones.length)
        .map((i) => cursosConInscripciones[i].id_curso)
    }

    if (cursosALimpiar.length === 0) {
      alert("No se seleccionaron cursos válidos")
      return
    }

    if (
      !confirm(
        `¿Estás seguro que deseas limpiar ${cursosALimpiar.length} curso(s)? Esto eliminará todas las inscripciones de los cursos seleccionados.`,
      )
    ) {
      return
    }

    try {
      const formData = new FormData()
      cursosALimpiar.forEach((id) => {
        formData.append("cursos[]", id)
      })

      const response = await fetch("api/limpiar_cursos_terminados.php", {
        method: "POST",
        body: formData,
      })

      const result = await response.json()

      if (result.exito) {
        alert(result.mensaje)
        cargarCursosConUsuarios()
        cargarEstadisticas()
      } else {
        alert("Error al limpiar cursos: " + result.mensaje)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión")
    }
  }

  // FUNCIONES GLOBALES - Asignar al objeto window
  window.verUsuario = async (id) => {
    console.log("Viendo detalles del usuario:", id)

    try {
      const usuarioDetallesTitle = document.getElementById("usuarioDetallesTitle")
      const cursosUsuarioContainer = document.getElementById("cursosUsuarioContainer")

      if (usuarioDetallesTitle) usuarioDetallesTitle.textContent = "Cargando detalles..."
      if (cursosUsuarioContainer) {
        cursosUsuarioContainer.innerHTML = `
          <div class="loading-courses">
            <i class="fas fa-spinner fa-spin"></i> Cargando cursos del usuario...
          </div>
        `
      }
      if (usuarioDetallesModal) usuarioDetallesModal.style.display = "block"

      const response = await fetch(`api/obtener_cursos_usuario.php?id=${id}`)
      const data = await response.json()

      console.log("Datos del usuario:", data)

      if (data.exito) {
        const usuario = data.usuario
        const cursos = data.cursos

        const detalleNombre = document.getElementById("detalleNombre")
        const detalleCurp = document.getElementById("detalleCurp")
        const detalleEdad = document.getElementById("detalleEdad")
        const detalleSalud = document.getElementById("detalleSalud")
        const detalleNumTutor= document.getElementById("detalleNumTutor")
        const detalleFechaRegistro = document.getElementById("detalleFechaRegistro")
        const totalCursosUsuario = document.getElementById("totalCursosUsuario")

        if (usuarioDetallesTitle)
          usuarioDetallesTitle.textContent = `Detalles de ${usuario.nombre} ${usuario.apellidos}`
        if (detalleNombre) detalleNombre.textContent = `${usuario.nombre} ${usuario.apellidos}`
        if (detalleCurp) detalleCurp.textContent = usuario.curp
        if (detalleEdad) detalleEdad.textContent = `${usuario.edad} años`
        if (detalleSalud) detalleSalud.textContent = usuario.salud
        if (detalleNumTutor) detalleNumTutor.textContent = usuario.numero_tutor
        if (detalleFechaRegistro) detalleFechaRegistro.textContent = usuario.fecha_registro
        if (totalCursosUsuario) totalCursosUsuario.textContent = `(${data.total_cursos})`

        if (cursosUsuarioContainer) {
          if (cursos.length === 0) {
            cursosUsuarioContainer.innerHTML = `
              <div class="no-cursos-message">
                <i class="fas fa-info-circle"></i>
                <p>Este usuario no está inscrito en ningún curso</p>
              </div>
            `
          } else {
            const cursosHTML = cursos
              .map(
                (curso) => `
              <div class="curso-usuario-card">
                <div class="curso-header">
                  <span class="curso-nombre">${curso.nombre_curso}</span>
                  <span class="curso-estado ${curso.estado}">${curso.estado}</span>
                </div>
                <div class="curso-detalles">
                  <span>
                    <i class="fas fa-clock"></i>
                    ${curso.horario || "Horario por definir"}
                  </span>
                  <span>
                    <i class="fas fa-users"></i>
                    Edad: ${curso.edad_min}-${curso.edad_max} años
                  </span>
                  <span>
                    <i class="fas fa-calendar-plus"></i>
                    Inscrito: ${curso.fecha_inscripcion}
                  </span>
                </div>
              </div>
            `,
              )
              .join("")

            cursosUsuarioContainer.innerHTML = cursosHTML
          }
        }
      } else {
        alert("Error al cargar detalles del usuario: " + data.mensaje)
        cerrarModalUsuarioDetalles()
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión al cargar usuario")
    }
  }

  window.eliminarUsuario = async (id) => {
    if (confirm(`¿Estás seguro que deseas eliminar el usuario ${id}?`)) {
      try {
        const formData = new FormData()
        formData.append("id", id)

        const response = await fetch("api/eliminar_usuario.php", {
          method: "POST",
          body: formData,
        })

        const result = await response.json()

        if (result.success) {
          alert("Usuario eliminado exitosamente")
          cargarUsuarios()
          cargarEstadisticas()
        } else {
          alert("Error al eliminar usuario: " + result.message)
        }
      } catch (error) {
        console.error("Error:", error)
        alert("Error de conexión")
      }
    }
  }

  window.editarCurso = async (id) => {
    console.log("=== EDITANDO CURSO ===")
    console.log("ID del curso a editar:", id)

    try {
      const response = await fetch(`api/obtener_curso.php?id=${id}`)
      const data = await response.json()

      console.log("Datos del curso obtenidos:", data)

      if (data.exito) {
        abrirModalCurso(data.curso)
      } else {
        alert("Error al cargar curso: " + data.mensaje)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión: " + error.message)
    }
  }

  window.eliminarCurso = async (id) => {
    if (confirm(`¿Estás seguro que deseas eliminar el curso ${id}? Esto también eliminará su tabla específica.`)) {
      try {
        const formData = new FormData()
        formData.append("id", id)

        const response = await fetch("api/eliminar_curso.php", {
          method: "POST",
          body: formData,
        })

        const result = await response.json()

        if (result.exito) {
          alert("Curso eliminado exitosamente")
          cargarCursos()
          cargarEstadisticas()
          if (currentSection === "cursos-usuarios") {
            cargarCursosConUsuarios()
          }
        } else {
          alert("Error al eliminar curso: " + result.mensaje)
        }
      } catch (error) {
        console.error("Error:", error)
        alert("Error de conexión")
      }
    }
  }

  window.editarAdmin = async (id) => {
    try {
      const response = await fetch(`api/obtener_admin.php?id=${id}`)
      const data = await response.json()

      if (data.exito) {
        abrirModalAdmin(data.admin)
      } else {
        alert("Error al cargar administrador: " + data.mensaje)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión")
    }
  }

  window.eliminarAdmin = async (id) => {
    if (confirm(`¿Estás seguro que deseas eliminar el administrador ${id}?`)) {
      try {
        const formData = new FormData()
        formData.append("id", id)

        const response = await fetch("api/eliminar_admin.php", {
          method: "POST",
          body: formData,
        })

        const result = await response.json()

        if (result.exito) {
          alert("Administrador eliminado exitosamente")
          cargarAdministradores()
          cargarEstadisticas()
        } else {
          alert("Error al eliminar administrador: " + result.mensaje)
        }
      } catch (error) {
        console.error("Error:", error)
        alert("Error de conexión")
      }
    }
  }

  window.eliminarInscripcion = async (id) => {
    if (confirm(`¿Estás seguro que deseas eliminar la inscripción ${id}?`)) {
      try {
        const formData = new FormData()
        formData.append("id", id)

        const response = await fetch("api/eliminar_inscripcion.php", {
          method: "POST",
          body: formData,
        })

        const result = await response.json()

        if (result.exito) {
          alert("Inscripción eliminada exitosamente")
          cargarInscripciones()
          cargarEstadisticas()
        } else {
          alert("Error al eliminar inscripción: " + result.mensaje)
        }
      } catch (error) {
        console.error("Error:", error)
        alert("Error de conexión")
      }
    }
  }

  // Asignar función global para ver lista de usuarios
  window.verListaUsuarios = verListaUsuarios

  // Cerrar modales al hacer clic fuera
  window.addEventListener("click", (e) => {
    if (e.target === cursoModal) cerrarModalCurso()
    if (e.target === adminModal) cerrarModalAdmin()
    if (e.target === usuarioDetallesModal) cerrarModalUsuarioDetalles()
    if (e.target === listaUsuariosModal) cerrarModalListaUsuarios()
  })

  console.log("=== ADMIN PANEL INICIALIZADO ===")
})
