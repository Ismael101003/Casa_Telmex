let timeoutBusqueda
let usuarioExistente

document.addEventListener("DOMContentLoaded", () => {
  inicializarFormulario()
  configurarBusquedaUsuario()
  cargarCursos()
})

function inicializarFormulario() {
  // Configurar validaciones en tiempo real
  const inputs = document.querySelectorAll("input[required]")
  inputs.forEach((input) => {
    input.addEventListener("blur", validarCampo)
    input.addEventListener("input", limpiarErrores)
  })

  // Configurar validación de CURP
  const curpInput = document.getElementById("curp")
  if (curpInput) {
    curpInput.addEventListener("input", function () {
      this.value = this.value.toUpperCase()
      validarCURP(this.value)
    })
  }

  // Configurar validación de teléfonos
  const telefonos = ["numero_tutor", "numero_usuario"]
  telefonos.forEach((id) => {
    const input = document.getElementById(id)
    if (input) {
      input.addEventListener("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "")
        if (this.value.length > 10) {
          this.value = this.value.substring(0, 10)
        }
      })
    }
  })

  // Configurar cálculo automático de edad
  const fechaNacimiento = document.getElementById("fecha_nacimiento")
  if (fechaNacimiento) {
    fechaNacimiento.addEventListener("change", calcularEdad)
  }
}

function configurarBusquedaUsuario() {
  const buscarInput = document.getElementById("buscar_usuario")
  const resultadosBusqueda = document.getElementById("resultados_busqueda")

  if (!buscarInput || !resultadosBusqueda) return

  // Configurar búsqueda con debounce
  buscarInput.addEventListener("input", function () {
    const query = this.value.trim()

    // Limpiar timeout anterior
    if (timeoutBusqueda) {
      clearTimeout(timeoutBusqueda)
    }

    if (query.length < 2) {
      resultadosBusqueda.innerHTML = ""
      resultadosBusqueda.style.display = "none"
      return
    }

    // Mostrar indicador de carga
    resultadosBusqueda.innerHTML = '<div class="loading">Buscando...</div>'
    resultadosBusqueda.style.display = "block"

    // Ejecutar búsqueda con delay
    timeoutBusqueda = setTimeout(() => {
      buscarUsuarios(query)
    }, 300)
  })

  // Ocultar resultados al hacer clic fuera
  document.addEventListener("click", (e) => {
    if (!buscarInput.contains(e.target) && !resultadosBusqueda.contains(e.target)) {
      resultadosBusqueda.style.display = "none"
    }
  })

  // Navegación con teclado
  buscarInput.addEventListener("keydown", (e) => {
    const items = resultadosBusqueda.querySelectorAll(".resultado-usuario")
    const selected = resultadosBusqueda.querySelector(".selected")
    let index = -1

    if (selected) {
      index = Array.from(items).indexOf(selected)
    }

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault()
        if (selected) selected.classList.remove("selected")
        index = (index + 1) % items.length
        if (items[index]) items[index].classList.add("selected")
        break

      case "ArrowUp":
        e.preventDefault()
        if (selected) selected.classList.remove("selected")
        index = index <= 0 ? items.length - 1 : index - 1
        if (items[index]) items[index].classList.add("selected")
        break

      case "Enter":
        e.preventDefault()
        if (selected) {
          selected.click()
        }
        break

      case "Escape":
        resultadosBusqueda.style.display = "none"
        break
    }
  })
}

async function buscarUsuarios(query) {
  const resultadosBusqueda = document.getElementById("resultados_busqueda")

  try {
    const response = await fetch(`api/buscar_usuarios_autocompletado.php?q=${encodeURIComponent(query)}`)
    const data = await response.json()

    if (data.exito && data.usuarios && data.usuarios.length > 0) {
      let html = ""
      data.usuarios.forEach((usuario) => {
        html += `
                    <div class="resultado-usuario" onclick="seleccionarUsuario(${JSON.stringify(usuario).replace(/"/g, "&quot;")})">
                        <div class="nombre">${usuario.nombre_completo}</div>
                        <div class="detalles">
                            CURP: ${usuario.curp || "No disponible"} | 
                            Edad: ${usuario.edad || 0} años |
                            Registrado: ${usuario.fecha_registro_formateada || "Sin fecha"}
                        </div>
                    </div>
                `
      })
      resultadosBusqueda.innerHTML = html
    } else {
      resultadosBusqueda.innerHTML = '<div class="no-resultados">No se encontraron usuarios</div>'
    }

    resultadosBusqueda.style.display = "block"
  } catch (error) {
    console.error("Error en búsqueda:", error)
    resultadosBusqueda.innerHTML = '<div class="error">Error al buscar usuarios</div>'
  }
}

function seleccionarUsuario(usuario) {
  usuarioExistente = usuario

  // Llenar formulario con datos del usuario
  document.getElementById("nombre").value = usuario.nombre || ""
  document.getElementById("apellidos").value = usuario.apellidos || ""
  document.getElementById("curp").value = usuario.curp || ""
  document.getElementById("fecha_nacimiento").value = usuario.fecha_nacimiento || ""
  document.getElementById("edad").value = usuario.edad || ""
  document.getElementById("meses").value = usuario.meses || ""
  document.getElementById("salud").value = usuario.salud || ""
  document.getElementById("tutor").value = usuario.tutor || ""
  document.getElementById("numero_tutor").value = usuario.numero_tutor || ""
  document.getElementById("numero_usuario").value = usuario.numero_usuario || ""

  // Deshabilitar campos que no deben editarse
  const camposNoEditables = ["nombre", "apellidos", "curp", "fecha_nacimiento"]
  camposNoEditables.forEach((campo) => {
    const input = document.getElementById(campo)
    if (input) {
      input.disabled = true
      input.style.backgroundColor = "#f8f9fa"
    }
  })

  // Ocultar resultados de búsqueda
  document.getElementById("resultados_busqueda").style.display = "none"
  document.getElementById("buscar_usuario").value = usuario.nombre_completo

  // Mostrar mensaje informativo
  mostrarAlerta("Usuario encontrado. Solo podrás inscribirlo en cursos adicionales.", "info")

  // Obtener cursos en los que ya está inscrito
  obtenerCursosUsuario(usuario.id)
}

function limpiarBusqueda() {
  usuarioExistente = null

  // Limpiar formulario
  document.getElementById("formulario_registro").reset()
  document.getElementById("buscar_usuario").value = ""
  document.getElementById("resultados_busqueda").innerHTML = ""
  document.getElementById("resultados_busqueda").style.display = "none"

  // Habilitar todos los campos
  const inputs = document.querySelectorAll("input")
  inputs.forEach((input) => {
    input.disabled = false
    input.style.backgroundColor = ""
  })

  // Limpiar alertas
  limpiarAlertas()

  // Recargar cursos
  cargarCursos()
}

async function obtenerCursosUsuario(idUsuario) {
  try {
    const response = await fetch(`api/obtener_cursos_usuario.php?id_usuario=${idUsuario}`)
    const data = await response.json()

    if (data.exito && data.cursos) {
      const cursosInscritos = data.cursos.map((curso) => curso.id_curso)
      marcarCursosInscritos(cursosInscritos)
    }
  } catch (error) {
    console.error("Error al obtener cursos del usuario:", error)
  }
}

function marcarCursosInscritos(cursosInscritos) {
  const checkboxes = document.querySelectorAll('input[name="cursos[]"]')
  checkboxes.forEach((checkbox) => {
    const idCurso = Number.parseInt(checkbox.value)
    if (cursosInscritos.includes(idCurso)) {
      checkbox.disabled = true
      checkbox.checked = false
      const label = checkbox.closest("label")
      if (label) {
        label.style.opacity = "0.5"
        label.title = "El usuario ya está inscrito en este curso"
      }
    }
  })
}

async function cargarCursos() {
  try {
    const response = await fetch("api/obtener_cursos.php")
    const data = await response.json()

    if (data.exito && data.cursos) {
      const container = document.getElementById("cursos_container")
      if (container) {
        let html = ""
        data.cursos.forEach((curso) => {
          html += `
                        <label class="curso-option">
                            <input type="checkbox" name="cursos[]" value="${curso.id_curso}">
                            <span class="checkmark"></span>
                            ${curso.nombre_curso}
                        </label>
                    `
        })
        container.innerHTML = html
      }
    }
  } catch (error) {
    console.error("Error al cargar cursos:", error)
  }
}

function validarCampo(event) {
  const input = event.target
  const valor = input.value.trim()

  // Limpiar errores previos
  limpiarErrorCampo(input)

  // Validaciones específicas
  switch (input.id) {
    case "curp":
      if (valor && !validarCURP(valor)) {
        mostrarErrorCampo(input, "CURP no válida")
      }
      break

    case "numero_tutor":
    case "numero_usuario":
      if (valor && !validarTelefono(valor)) {
        mostrarErrorCampo(input, "Teléfono debe tener 10 dígitos")
      }
      break

    case "edad":
      const edad = Number.parseInt(valor)
      if (valor && (edad < 0 || edad > 120)) {
        mostrarErrorCampo(input, "Edad no válida")
      }
      break
  }
}

function validarCURP(curp) {
  if (!curp || curp.length !== 18) return false
  const patron = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/
  return patron.test(curp.toUpperCase())
}

function validarTelefono(telefono) {
  const telefonoLimpio = telefono.replace(/[^0-9]/g, "")
  return telefonoLimpio.length === 10
}

function calcularEdad() {
  const fechaNacimiento = document.getElementById("fecha_nacimiento").value
  const edadInput = document.getElementById("edad")
  const mesesInput = document.getElementById("meses")

  if (!fechaNacimiento) return

  const hoy = new Date()
  const nacimiento = new Date(fechaNacimiento)

  let edad = hoy.getFullYear() - nacimiento.getFullYear()
  let meses = hoy.getMonth() - nacimiento.getMonth()

  if (meses < 0 || (meses === 0 && hoy.getDate() < nacimiento.getDate())) {
    edad--
    meses += 12
  }

  if (hoy.getDate() < nacimiento.getDate()) {
    meses--
  }

  edadInput.value = edad
  mesesInput.value = meses
}

function mostrarErrorCampo(input, mensaje) {
  const errorDiv = document.createElement("div")
  errorDiv.className = "error-campo"
  errorDiv.textContent = mensaje

  input.classList.add("error")
  input.parentNode.appendChild(errorDiv)
}

function limpiarErrorCampo(input) {
  input.classList.remove("error")
  const errorDiv = input.parentNode.querySelector(".error-campo")
  if (errorDiv) {
    errorDiv.remove()
  }
}

function limpiarErrores() {
  const errores = document.querySelectorAll(".error-campo")
  errores.forEach((error) => error.remove())

  const inputs = document.querySelectorAll(".error")
  inputs.forEach((input) => input.classList.remove("error"))
}

function mostrarAlerta(mensaje, tipo = "info") {
  const alertaDiv = document.createElement("div")
  alertaDiv.className = `alerta alerta-${tipo}`
  alertaDiv.innerHTML = `
        <span>${mensaje}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `

  const container = document.querySelector(".container")
  container.insertBefore(alertaDiv, container.firstChild)

  // Auto-ocultar después de 5 segundos
  setTimeout(() => {
    if (alertaDiv.parentNode) {
      alertaDiv.remove()
    }
  }, 5000)
}

function limpiarAlertas() {
  const alertas = document.querySelectorAll(".alerta")
  alertas.forEach((alerta) => alerta.remove())
}

// Función para enviar formulario
async function enviarFormulario(event) {
  event.preventDefault()

  // Validar formulario
  if (!validarFormulario()) {
    return
  }

  const formData = new FormData(event.target)

  // Agregar información de usuario existente si aplica
  if (usuarioExistente) {
    formData.append("usuario_existente", "true")
    formData.append("id_usuario_existente", usuarioExistente.id)
  }

  try {
    const response = await fetch("api/registro.php", {
      method: "POST",
      body: formData,
    })

    const data = await response.json()

    if (data.exito) {
      mostrarAlerta("Usuario registrado exitosamente", "success")

      // Limpiar formulario después de un registro exitoso
      setTimeout(() => {
        limpiarBusqueda()
      }, 2000)
    } else {
      mostrarAlerta(data.mensaje || "Error al registrar usuario", "error")
    }
  } catch (error) {
    console.error("Error:", error)
    mostrarAlerta("Error de conexión al servidor", "error")
  }
}

function validarFormulario() {
  const camposRequeridos = ["nombre", "apellidos", "curp", "fecha_nacimiento"]
  let valido = true

  // Limpiar errores previos
  limpiarErrores()

  // Validar campos requeridos
  camposRequeridos.forEach((campo) => {
    const input = document.getElementById(campo)
    if (!input.value.trim()) {
      mostrarErrorCampo(input, "Este campo es obligatorio")
      valido = false
    }
  })

  // Validar que al menos un curso esté seleccionado
  const cursosSeleccionados = document.querySelectorAll('input[name="cursos[]"]:checked')
  if (cursosSeleccionados.length === 0) {
    mostrarAlerta("Debe seleccionar al menos un curso", "error")
    valido = false
  }

  return valido
}

// Configurar el formulario cuando se carga la página
document.addEventListener("DOMContentLoaded", () => {
  const formulario = document.getElementById("formulario_registro")
  if (formulario) {
    formulario.addEventListener("submit", enviarFormulario)
  }
})

document.addEventListener("DOMContentLoaded", () => {
  console.log("=== INICIANDO REGISTRO.JS ===")

  // Referencias a elementos del DOM con verificación completa
  const elementos = {
    form: document.getElementById("registroForm"),
    curpInput: document.getElementById("curp"),
    fechaNacimientoInput: document.getElementById("fecha_nacimiento"),
    edadInput: document.getElementById("edad"),
    mesesInput: document.getElementById("meses"),
    numeroTutorInput: document.getElementById("numero_tutor"),
    numeroUsuarioInput: document.getElementById("numero_usuario"),
    tutorInput: document.getElementById("tutor"),
    successAlert: document.getElementById("successAlert"),
    currentYearSpan: document.getElementById("currentYear"),
    fechaAutoNotice: document.getElementById("fechaAutoNotice"),
    coursesContainer: document.getElementById("coursesContainer"),
    selectedCourses: document.getElementById("selectedCourses"),
    selectedCoursesList: document.getElementById("selectedCoursesList"),
    selectedCount: document.getElementById("selectedCount"),
    tutorSection: document.getElementById("tutorSection"),
    searchUserInput: document.getElementById("searchUser"),
    autocompleteDropdown: document.getElementById("autocompleteDropdown"),
    clearSearchBtn: document.getElementById("clearSearchBtn"),
    userFoundAlert: document.getElementById("userFoundAlert"),
    courseSearchInput: document.getElementById("courseSearch"),
    clearCourseSearchBtn: document.getElementById("clearCourseSearch"),
  }

  // Log detallado de cada elemento
  Object.entries(elementos).forEach(([nombre, elemento]) => {
    if (elemento) {
      console.log(`✅ ${nombre}: encontrado`)
    } else {
      console.warn(`❌ ${nombre}: NO encontrado`)
    }
  })

  console.log("=== FIN VERIFICACIÓN DE ELEMENTOS ===")

  // Variables globales
  let cursosDisponibles = []
  let cursosSeleccionados = []
  let edadUsuario = 0
  let esAdulto = false
  let currentFilter = "all"
  let searchTimeout = null

  // Establecer el año actual
  if (elementos.currentYearSpan) {
    elementos.currentYearSpan.textContent = new Date().getFullYear()
  }

  // Expresiones regulares para validación
  const regexCurp = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/i
  const regexTelefono = /^\d{10}$/

  // Inicializar componentes
  inicializarEventListeners()
  cargarCursos()

  function inicializarEventListeners() {
    console.log("=== INICIALIZANDO EVENT LISTENERS ===")

    // Event listeners para tipo de usuario
    const tipoUsuarioRadios = document.querySelectorAll('input[name="tipo_usuario"]')
    if (tipoUsuarioRadios && tipoUsuarioRadios.length > 0) {
      console.log("✅ Agregando listeners para tipo de usuario")
      tipoUsuarioRadios.forEach((radio) => {
        if (radio) {
          radio.addEventListener("change", manejarCambioTipoUsuario)
        }
      })
    } else {
      console.warn("⚠️ No se encontraron radios de tipo de usuario")
    }

    // Event listeners para autocompletado
    if (elementos.searchUserInput) {
      console.log("✅ Agregando listeners para búsqueda de usuario")
      elementos.searchUserInput.addEventListener("input", manejarBusquedaAutocompletado)
      elementos.searchUserInput.addEventListener("focus", mostrarDropdownSiTieneResultados)
      elementos.searchUserInput.addEventListener("keydown", manejarTeclasAutocompletado)
    } else {
      console.warn("⚠️ No se encontró campo de búsqueda de usuario")
    }

    if (elementos.clearSearchBtn) {
      elementos.clearSearchBtn.addEventListener("click", limpiarBusquedaUsuario)
    } else {
      console.warn("⚠️ No se encontró botón de limpiar búsqueda")
    }

    // Event listeners para búsqueda de cursos
    if (elementos.courseSearchInput) {
      console.log("✅ Agregando listeners para búsqueda de cursos")
      elementos.courseSearchInput.addEventListener("input", filtrarCursos)
    } else {
      console.warn("⚠️ No se encontró campo de búsqueda de cursos")
    }

    if (elementos.clearCourseSearchBtn) {
      elementos.clearCourseSearchBtn.addEventListener("click", limpiarBusquedaCursos)
    } else {
      console.warn("⚠️ No se encontró botón de limpiar búsqueda de cursos")
    }

    // Event listeners para filtros de cursos
    const filterButtons = document.querySelectorAll(".filter-btn")
    if (filterButtons && filterButtons.length > 0) {
      console.log("✅ Agregando listeners para filtros de cursos")
      filterButtons.forEach((btn) => {
        if (btn) {
          btn.addEventListener("click", (e) => {
            filterButtons.forEach((b) => b.classList.remove("active"))
            e.target.classList.add("active")
            currentFilter = e.target.dataset.filter
            filtrarCursos()
          })
        }
      })
    } else {
      console.warn("⚠️ No se encontraron botones de filtro")
    }

    // Event listeners para CURP
    if (elementos.curpInput) {
      console.log("✅ Agregando listeners para CURP")

      elementos.curpInput.addEventListener("input", function (e) {
        console.log("🔤 CURP input event:", this.value)
        this.value = this.value.toUpperCase()
        validarCampo(this, regexCurp, "El CURP no tiene un formato válido")

        if (this.value.length >= 10) {
          const fechaExtraida = extraerFechaDeCURP(this.value)
          if (fechaExtraida && elementos.fechaNacimientoInput) {
            elementos.fechaNacimientoInput.value = fechaExtraida
            if (elementos.fechaAutoNotice) {
              elementos.fechaAutoNotice.style.display = "block"
            }
            calcularEdad()

            // Efecto visual
            elementos.fechaNacimientoInput.style.background = "#e8f5e8"
            elementos.fechaNacimientoInput.style.transition = "background 0.3s ease"
            setTimeout(() => {
              elementos.fechaNacimientoInput.style.background = ""
            }, 2000)
          }
        }
      })

      elementos.curpInput.addEventListener("paste", function (e) {
        setTimeout(() => {
          this.value = this.value.toUpperCase()
          if (this.value.length >= 10) {
            const fechaExtraida = extraerFechaDeCURP(this.value)
            if (fechaExtraida && elementos.fechaNacimientoInput) {
              elementos.fechaNacimientoInput.value = fechaExtraida
              if (elementos.fechaAutoNotice) {
                elementos.fechaAutoNotice.style.display = "block"
              }
              calcularEdad()
            }
          }
        }, 100)
      })
    } else {
      console.error("❌ No se encontró el campo CURP")
    }

    // Event listener para fecha de nacimiento
    if (elementos.fechaNacimientoInput) {
      elementos.fechaNacimientoInput.addEventListener("change", () => {
        console.log("📅 Fecha cambiada manualmente:", elementos.fechaNacimientoInput.value)
        calcularEdad()
        if (elementos.fechaAutoNotice) {
          elementos.fechaAutoNotice.style.display = "none"
        }
      })
    } else {
      console.warn("⚠️ No se encontró campo de fecha de nacimiento")
    }

    // Event listeners para validación de teléfonos
    if (elementos.numeroUsuarioInput) {
      elementos.numeroUsuarioInput.addEventListener("input", function () {
        validarCampo(this, regexTelefono, "El número debe tener 10 dígitos")
      })
    } else {
      console.warn("⚠️ No se encontró campo de número de usuario")
    }

    if (elementos.numeroTutorInput) {
      elementos.numeroTutorInput.addEventListener("input", function () {
        if (!esAdulto) {
          validarCampo(this, regexTelefono, "El número debe tener 10 dígitos")
        }
      })
    } else {
      console.warn("⚠️ No se encontró campo de número de tutor")
    }

    if (elementos.tutorInput) {
      elementos.tutorInput.addEventListener("input", function () {
        if (!esAdulto) {
          validarCampo(this, /.{2,}/, "El nombre del tutor debe tener al menos 2 caracteres")
        }
      })
    } else {
      console.warn("⚠️ No se encontró campo de tutor")
    }

    // Event listener para el formulario
    if (elementos.form) {
      console.log("✅ Agregando listener para formulario")
      elementos.form.addEventListener("submit", manejarEnvioFormulario)
      elementos.form.addEventListener("reset", () => {
        console.log("Formulario reseteado")
        limpiarBusquedaUsuario()
      })
    } else {
      console.error("❌ No se encontró el formulario")
    }

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".autocomplete-container")) {
        ocultarDropdown()
      }
    })

    console.log("=== EVENT LISTENERS INICIALIZADOS ===")
  }

  // Función para extraer fecha del CURP
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

  // Función para calcular edad
  function calcularEdad() {
    console.log("🧮 Calculando edad...")

    if (!elementos.fechaNacimientoInput || !elementos.fechaNacimientoInput.value) {
      console.log("❌ No hay fecha de nacimiento")
      return
    }

    try {
      const fechaNacimiento = new Date(elementos.fechaNacimientoInput.value + "T00:00:00")
      const hoy = new Date()

      let edad = hoy.getFullYear() - fechaNacimiento.getFullYear()
      let meses = hoy.getMonth() - fechaNacimiento.getMonth()

      if (meses < 0 || (meses === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--
        meses = meses < 0 ? meses + 12 : 11
      }

      if (meses < 0) {
        meses = 0
      }

      console.log("🎂 Edad calculada:", edad, "años,", meses, "meses")

      if (elementos.edadInput) elementos.edadInput.value = edad
      if (elementos.mesesInput) elementos.mesesInput.value = meses

      edadUsuario = edad

      // Determinar tipo de usuario automáticamente
      if (edad >= 18) {
        const tipoAdulto = document.getElementById("tipoAdulto")
        if (tipoAdulto) {
          tipoAdulto.checked = true
          manejarCambioTipoUsuario()
        }
      } else {
        const tipoNino = document.getElementById("tipoNino")
        if (tipoNino) {
          tipoNino.checked = true
          manejarCambioTipoUsuario()
        }
      }

      filtrarCursosPorEdad()
    } catch (error) {
      console.error("Error al calcular edad:", error)
    }
  }

  // Función para manejar cambio de tipo de usuario
  function manejarCambioTipoUsuario() {
    const tipoSeleccionado = document.querySelector('input[name="tipo_usuario"]:checked')?.value
    esAdulto = tipoSeleccionado === "adulto"

    console.log("👤 Tipo de usuario:", tipoSeleccionado, "Es adulto:", esAdulto)

    if (esAdulto) {
      // Para adultos: ocultar toda la sección del tutor
      if (elementos.tutorSection) {
        elementos.tutorSection.style.display = "none"
      }

      if (elementos.tutorInput) {
        elementos.tutorInput.removeAttribute("required")
        elementos.tutorInput.value = "N/A - Usuario adulto"
      }

      if (elementos.numeroTutorInput) {
        elementos.numeroTutorInput.removeAttribute("required")
        elementos.numeroTutorInput.value = ""
      }
    } else {
      // Para menores: mostrar la sección del tutor
      if (elementos.tutorSection) {
        elementos.tutorSection.style.display = "block"
      }

      if (elementos.tutorInput) {
        elementos.tutorInput.setAttribute("required", "")
        if (elementos.tutorInput.value === "N/A - Usuario adulto") {
          elementos.tutorInput.value = ""
        }
      }

      if (elementos.numeroTutorInput) {
        elementos.numeroTutorInput.setAttribute("required", "")
      }
    }
  }

  // Funciones para autocompletado mejoradas
  function manejarBusquedaAutocompletado() {
    if (!elementos.searchUserInput) return

    const query = elementos.searchUserInput.value.trim()

    if (query.length > 0 && elementos.clearSearchBtn) {
      elementos.clearSearchBtn.style.display = "block"
    } else if (elementos.clearSearchBtn) {
      elementos.clearSearchBtn.style.display = "none"
      ocultarDropdown()
      return
    }

    clearTimeout(searchTimeout)

    if (query.length < 2) {
      ocultarDropdown()
      return
    }

    // Mostrar indicador de carga
    if (elementos.autocompleteDropdown) {
      elementos.autocompleteDropdown.innerHTML = `
        <div class="autocomplete-item loading">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Buscando usuarios...</span>
        </div>
      `
      elementos.autocompleteDropdown.style.display = "block"
    }

    searchTimeout = setTimeout(() => {
      buscarUsuarios(query)
    }, 300)
  }

  function manejarTeclasAutocompletado(e) {
    if (!elementos.autocompleteDropdown || elementos.autocompleteDropdown.style.display === "none") return

    const items = elementos.autocompleteDropdown.querySelectorAll(".autocomplete-item:not(.no-results):not(.loading)")
    const activeItem = elementos.autocompleteDropdown.querySelector(".autocomplete-item.active")
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
        elementos.searchUserInput.blur()
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
    if (!elementos.autocompleteDropdown) return

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
              <i class="fas fa-id-card"></i> ${usuario.curp}
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

    elementos.autocompleteDropdown.innerHTML = resultadosHTML
    elementos.autocompleteDropdown.style.display = "block"

    // Agregar event listeners
    elementos.autocompleteDropdown.querySelectorAll(".autocomplete-item").forEach((item, index) => {
      if (!item.classList.contains("no-results") && !item.classList.contains("loading")) {
        item.addEventListener("click", () => {
          const usuario = JSON.parse(item.dataset.usuario)
          seleccionarUsuario(usuario)
        })

        item.addEventListener("mouseenter", () => {
          elementos.autocompleteDropdown
            .querySelectorAll(".autocomplete-item")
            .forEach((i) => i.classList.remove("active"))
          item.classList.add("active")
        })
      }
    })
  }

  function mostrarSinResultados(mensaje) {
    if (!elementos.autocompleteDropdown) return

    elementos.autocompleteDropdown.innerHTML = `
      <div class="autocomplete-item no-results">
        <i class="fas fa-info-circle"></i>
        <span>${mensaje}</span>
      </div>
    `
    elementos.autocompleteDropdown.style.display = "block"
  }

  function mostrarErrorBusqueda(mensaje) {
    if (!elementos.autocompleteDropdown) return

    elementos.autocompleteDropdown.innerHTML = `
      <div class="autocomplete-item error">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Error: ${mensaje}</span>
      </div>
    `
    elementos.autocompleteDropdown.style.display = "block"
  }

  function mostrarDropdownSiTieneResultados() {
    if (elementos.autocompleteDropdown && elementos.autocompleteDropdown.children.length > 0) {
      elementos.autocompleteDropdown.style.display = "block"
    }
  }

  function ocultarDropdown() {
    if (elementos.autocompleteDropdown) {
      elementos.autocompleteDropdown.style.display = "none"
      elementos.autocompleteDropdown.querySelectorAll(".autocomplete-item").forEach((i) => i.classList.remove("active"))
    }
  }

  function seleccionarUsuario(usuario) {
    console.log("👤 Usuario seleccionado:", usuario)

    usuarioExistente = usuario
    if (elementos.searchUserInput) elementos.searchUserInput.value = usuario.nombre_completo
    ocultarDropdown()
    llenarDatosUsuario(usuario)

    if (elementos.userFoundAlert) {
      elementos.userFoundAlert.style.display = "flex"
    }

    // Deshabilitar campos que no deben editarse para usuarios existentes
    deshabilitarCamposUsuarioExistente(true)

    // Mostrar cursos en los que ya está inscrito
    mostrarCursosInscritos(usuario.cursos_inscritos || [])
  }

  function llenarDatosUsuario(usuario) {
    console.log("📝 Llenando datos del usuario:", usuario)

    const nombreInput = document.getElementById("nombre")
    const apellidosInput = document.getElementById("apellidos")
    const saludInput = document.getElementById("salud")

    // Llenar campos básicos
    if (nombreInput) nombreInput.value = usuario.nombre || ""
    if (apellidosInput) apellidosInput.value = usuario.apellidos || ""
    if (elementos.curpInput) elementos.curpInput.value = usuario.curp || ""
    if (elementos.fechaNacimientoInput) elementos.fechaNacimientoInput.value = usuario.fecha_nacimiento || ""
    if (saludInput) saludInput.value = usuario.salud || ""

    // Llenar números de teléfono
    if (elementos.numeroUsuarioInput) {
      elementos.numeroUsuarioInput.value = usuario.numero_usuario || usuario.telefono_usuario || ""
    }

    // Llenar datos del tutor si existen
    if (usuario.tutor && usuario.tutor !== "N/A - Usuario adulto") {
      if (elementos.tutorInput) elementos.tutorInput.value = usuario.tutor
      if (elementos.numeroTutorInput) elementos.numeroTutorInput.value = usuario.numero_tutor || ""
    }

    // Calcular edad y determinar tipo de usuario
    if (usuario.fecha_nacimiento) {
      calcularEdad()
    } else if (usuario.edad) {
      if (elementos.edadInput) elementos.edadInput.value = usuario.edad
      if (elementos.mesesInput) elementos.mesesInput.value = usuario.meses || 0
      edadUsuario = usuario.edad

      // Determinar tipo de usuario
      if (usuario.edad >= 18) {
        const tipoAdulto = document.getElementById("tipoAdulto")
        if (tipoAdulto) {
          tipoAdulto.checked = true
          manejarCambioTipoUsuario()
        }
      } else {
        const tipoNino = document.getElementById("tipoNino")
        if (tipoNino) {
          tipoNino.checked = true
          manejarCambioTipoUsuario()
        }
      }

      filtrarCursosPorEdad()
    }
  }

  function deshabilitarCamposUsuarioExistente(deshabilitar) {
    const campos = [
      document.getElementById("nombre"),
      document.getElementById("apellidos"),
      elementos.curpInput,
      elementos.fechaNacimientoInput,
      document.getElementById("salud"),
      elementos.tutorInput,
      elementos.numeroTutorInput,
    ]

    const tipoUsuarioRadios = document.querySelectorAll('input[name="tipo_usuario"]')

    campos.forEach((campo) => {
      if (campo) {
        if (deshabilitar) {
          campo.setAttribute("readonly", "")
          campo.style.backgroundColor = "#f8f9fa"
          campo.style.cursor = "not-allowed"
        } else {
          campo.removeAttribute("readonly")
          campo.style.backgroundColor = ""
          campo.style.cursor = ""
        }
      }
    })

    tipoUsuarioRadios.forEach((radio) => {
      if (radio) {
        radio.disabled = deshabilitar
      }
    })
  }

  function limpiarBusquedaUsuario() {
    console.log("🧹 Limpiando búsqueda de usuario")

    usuarioExistente = null

    if (elementos.searchUserInput) elementos.searchUserInput.value = ""
    if (elementos.clearSearchBtn) elementos.clearSearchBtn.style.display = "none"
    if (elementos.userFoundAlert) elementos.userFoundAlert.style.display = "none"

    ocultarDropdown()
    deshabilitarCamposUsuarioExistente(false)

    // Limpiar formulario
    if (elementos.form) {
      elementos.form.reset()
    }

    // Restablecer año actual
    if (elementos.currentYearSpan) {
      elementos.currentYearSpan.textContent = new Date().getFullYear()
    }

    // Ocultar notificación de fecha automática
    if (elementos.fechaAutoNotice) {
      elementos.fechaAutoNotice.style.display = "none"
    }

    // Limpiar selección de cursos
    cursosSeleccionados = []
    actualizarVisualizacionCursos()
  }

  // Funciones para manejo de cursos
  async function cargarCursos() {
    console.log("📚 Cargando cursos disponibles...")

    try {
      const response = await fetch("api/obtener_cursos.php")

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()

      if (data.exito) {
        cursosDisponibles = data.cursos || []
        console.log("✅ Cursos cargados:", cursosDisponibles.length)
        renderizarCursos()
      } else {
        console.error("❌ Error al cargar cursos:", data.mensaje)
        mostrarErrorCursos("Error al cargar cursos: " + data.mensaje)
      }
    } catch (error) {
      console.error("Error al cargar cursos:", error)
      mostrarErrorCursos("Error de conexión al cargar cursos")
    }
  }

  function renderizarCursos() {
    if (!elementos.coursesContainer) {
      console.warn("⚠️ No se encontró el contenedor de cursos")
      return
    }

    if (cursosDisponibles.length === 0) {
      elementos.coursesContainer.innerHTML = `
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          No hay cursos disponibles en este momento.
        </div>
      `
      return
    }

    const cursosHTML = cursosDisponibles
      .map((curso) => {
        const isSelected = cursosSeleccionados.includes(curso.id_curso)
        const cuposDisponibles = curso.cupo_maximo - (curso.usuarios_inscritos || 0)
        const sinCupos = cuposDisponibles <= 0

        return `
        <div class="course-card ${isSelected ? "selected" : ""} ${sinCupos ? "no-cupos" : ""}" 
             data-course-id="${curso.id_curso}"
             data-course-name="${curso.nombre_curso.toLowerCase()}"
             data-course-category="${curso.categoria || "general"}"
             data-course-age="${curso.edad_minima || 0}">
          <div class="course-header">
            <h5 class="course-title">${curso.nombre_curso}</h5>
            <div class="course-actions">
              ${
                sinCupos
                  ? '<span class="badge badge-danger">Sin cupos</span>'
                  : `<span class="badge badge-success">${cuposDisponibles} cupos</span>`
              }
              <button type="button" class="btn btn-sm ${isSelected ? "btn-danger" : "btn-primary"} course-toggle-btn"
                      ${sinCupos ? "disabled" : ""}>
                <i class="fas ${isSelected ? "fa-minus" : "fa-plus"}"></i>
                ${isSelected ? "Quitar" : "Agregar"}
              </button>
            </div>
          </div>
          <div class="course-details">
            <div class="course-info">
              <span class="course-schedule">
                <i class="fas fa-clock"></i>
                ${curso.horario || "Horario por definir"}
              </span>
              <span class="course-duration">
                <i class="fas fa-calendar-alt"></i>
                ${curso.duracion || "Duración por definir"}
              </span>
            </div>
            ${
              curso.descripcion
                ? `<p class="course-description">${curso.descripcion}</p>`
                : '<p class="course-description text-muted">Sin descripción disponible</p>'
            }
            <div class="course-meta">
              <span class="course-capacity">
                <i class="fas fa-users"></i>
                ${curso.usuarios_inscritos || 0}/${curso.cupo_maximo} inscritos
              </span>
              ${
                curso.edad_minima
                  ? `<span class="course-age">
                      <i class="fas fa-child"></i>
                      Edad mínima: ${curso.edad_minima} años
                    </span>`
                  : ""
              }
            </div>
          </div>
        </div>
      `
      })
      .join("")

    elementos.coursesContainer.innerHTML = cursosHTML

    // Agregar event listeners a los botones de cursos
    elementos.coursesContainer.querySelectorAll(".course-toggle-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const courseCard = e.target.closest(".course-card")
        const courseId = Number.parseInt(courseCard.dataset.courseId, 10)
        toggleCurso(courseId)
      })
    })
  }

  function toggleCurso(courseId) {
    console.log("🔄 Toggle curso:", courseId)

    const index = cursosSeleccionados.indexOf(courseId)

    if (index > -1) {
      // Quitar curso
      cursosSeleccionados.splice(index, 1)
      console.log("➖ Curso removido:", courseId)
    } else {
      // Agregar curso
      cursosSeleccionados.push(courseId)
      console.log("➕ Curso agregado:", courseId)
    }

    actualizarVisualizacionCursos()
  }

  function actualizarVisualizacionCursos() {
    // Actualizar cards de cursos
    if (elementos.coursesContainer) {
      elementos.coursesContainer.querySelectorAll(".course-card").forEach((card) => {
        const courseId = Number.parseInt(card.dataset.courseId, 10)
        const isSelected = cursosSeleccionados.includes(courseId)
        const btn = card.querySelector(".course-toggle-btn")

        if (isSelected) {
          card.classList.add("selected")
          if (btn) {
            btn.classList.remove("btn-primary")
            btn.classList.add("btn-danger")
            btn.innerHTML = '<i class="fas fa-minus"></i> Quitar'
          }
        } else {
          card.classList.remove("selected")
          if (btn) {
            btn.classList.remove("btn-danger")
            btn.classList.add("btn-primary")
            btn.innerHTML = '<i class="fas fa-plus"></i> Agregar'
          }
        }
      })
    }

    // Actualizar contador y lista de cursos seleccionados
    if (elementos.selectedCount) {
      elementos.selectedCount.textContent = cursosSeleccionados.length
    }

    if (elementos.selectedCourses) {
      if (cursosSeleccionados.length > 0) {
        elementos.selectedCourses.style.display = "block"
      } else {
        elementos.selectedCourses.style.display = "none"
      }
    }

    if (elementos.selectedCoursesList) {
      if (cursosSeleccionados.length > 0) {
        const cursosSeleccionadosInfo = cursosSeleccionados
          .map((id) => {
            const curso = cursosDisponibles.find((c) => c.id_curso === id)
            return curso
              ? `
            <div class="selected-course-item">
              <span class="selected-course-name">${curso.nombre_curso}</span>
              <button type="button" class="btn btn-sm btn-outline-danger remove-course-btn" data-course-id="${id}">
                <i class="fas fa-times"></i>
              </button>
            </div>
          `
              : ""
          })
          .join("")

        elementos.selectedCoursesList.innerHTML = cursosSeleccionadosInfo

        // Agregar event listeners para remover cursos
        elementos.selectedCoursesList.querySelectorAll(".remove-course-btn").forEach((btn) => {
          btn.addEventListener("click", (e) => {
            e.preventDefault()
            const courseId = Number.parseInt(e.target.closest(".remove-course-btn").dataset.courseId, 10)
            toggleCurso(courseId)
          })
        })
      } else {
        elementos.selectedCoursesList.innerHTML = '<p class="text-muted">No hay cursos seleccionados</p>'
      }
    }
  }

  function filtrarCursos() {
    if (!elementos.coursesContainer) return

    const searchTerm = elementos.courseSearchInput ? elementos.courseSearchInput.value.toLowerCase() : ""

    elementos.coursesContainer.querySelectorAll(".course-card").forEach((card) => {
      const courseName = card.dataset.courseName || ""
      const courseCategory = card.dataset.courseCategory || ""
      const courseAge = Number.parseInt(card.dataset.courseAge, 10) || 0

      let mostrar = true

      // Filtro por búsqueda de texto
      if (searchTerm && !courseName.includes(searchTerm) && !courseCategory.includes(searchTerm)) {
        mostrar = false
      }

      // Filtro por categoría
      if (currentFilter !== "all") {
        if (currentFilter === "ninos" && courseAge > 17) {
          mostrar = false
        } else if (currentFilter === "adultos" && courseAge < 18) {
          mostrar = false
        } else if (currentFilter === "disponibles") {
          const sinCupos = card.classList.contains("no-cupos")
          if (sinCupos) {
            mostrar = false
          }
        }
      }

      card.style.display = mostrar ? "block" : "none"
    })
  }

  function filtrarCursosPorEdad() {
    if (!elementos.coursesContainer || edadUsuario === 0) return

    elementos.coursesContainer.querySelectorAll(".course-card").forEach((card) => {
      const edadMinima = Number.parseInt(card.dataset.courseAge, 10) || 0

      if (edadMinima > 0 && edadUsuario < edadMinima) {
        card.classList.add("edad-no-valida")
        card.style.opacity = "0.5"
        const btn = card.querySelector(".course-toggle-btn")
        if (btn) {
          btn.disabled = true
          btn.title = `Edad mínima requerida: ${edadMinima} años`
        }
      } else {
        card.classList.remove("edad-no-valida")
        card.style.opacity = "1"
        const btn = card.querySelector(".course-toggle-btn")
        if (btn && !card.classList.contains("no-cupos")) {
          btn.disabled = false
          btn.title = ""
        }
      }
    })
  }

  function limpiarBusquedaCursos() {
    if (elementos.courseSearchInput) {
      elementos.courseSearchInput.value = ""
    }
    if (elementos.clearCourseSearchBtn) {
      elementos.clearCourseSearchBtn.style.display = "none"
    }
    filtrarCursos()
  }

  function mostrarErrorCursos(mensaje) {
    if (elementos.coursesContainer) {
      elementos.coursesContainer.innerHTML = `
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i>
          ${mensaje}
        </div>
      `
    }
  }

  // Función para validar campos
  function validarCampo(campo, regex, mensajeError) {
    if (!campo) return false

    const valor = campo.value.trim()
    const esValido = regex.test(valor)

    // Remover clases de validación previas
    campo.classList.remove("is-valid", "is-invalid")

    if (valor === "") {
      // Campo vacío - no mostrar validación
      return false
    }

    if (esValido) {
      campo.classList.add("is-valid")
      // Remover mensaje de error si existe
      const errorMsg = campo.parentNode.querySelector(".invalid-feedback")
      if (errorMsg) {
        errorMsg.remove()
      }
      return true
    } else {
      campo.classList.add("is-invalid")
      // Agregar o actualizar mensaje de error
      let errorMsg = campo.parentNode.querySelector(".invalid-feedback")
      if (!errorMsg) {
        errorMsg = document.createElement("div")
        errorMsg.className = "invalid-feedback"
        campo.parentNode.appendChild(errorMsg)
      }
      errorMsg.textContent = mensajeError
      return false
    }
  }

  // Función para manejar envío del formulario
  async function manejarEnvioFormulario(e) {
    e.preventDefault()
    console.log("📤 Enviando formulario...")

    // Validar que se hayan seleccionado cursos
    if (cursosSeleccionados.length === 0) {
      alert("Por favor, selecciona al menos un curso.")
      return
    }

    // Preparar datos del formulario
    const formData = new FormData(elementos.form)

    // Agregar cursos seleccionados
    cursosSeleccionados.forEach((cursoId) => {
      formData.append("cursos[]", cursoId)
    })

    // Si es usuario existente, agregar información adicional
    if (usuarioExistente) {
      formData.append("actualizar_usuario", "1")
      formData.append("id_usuario_existente", usuarioExistente.id)
    }

    // Mostrar indicador de carga
    const submitBtn = elementos.form.querySelector('button[type="submit"]')
    const originalText = submitBtn ? submitBtn.innerHTML : ""
    if (submitBtn) {
      submitBtn.disabled = true
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...'
    }

    try {
      console.log("📡 Enviando datos al servidor...")

      const response = await fetch("api/registro.php", {
        method: "POST",
        body: formData,
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const text = await response.text()
      console.log("📥 Respuesta del servidor:", text.substring(0, 500))

      let data
      try {
        data = JSON.parse(text)
      } catch (parseError) {
        console.error("Error parsing JSON:", parseError)
        console.error("Respuesta completa:", text)
        throw new Error("Respuesta del servidor no válida")
      }

      if (data.exito) {
        console.log("✅ Registro exitoso")
        mostrarMensajeExito(data.mensaje, data)

        // Limpiar formulario después del éxito
        setTimeout(() => {
          limpiarBusquedaUsuario()
          cursosSeleccionados = []
          actualizarVisualizacionCursos()
        }, 2000)
      } else {
        console.log("❌ Error en registro:", data.mensaje)
        mostrarMensajeError(data.mensaje, data.errores)
      }
    } catch (error) {
      console.error("Error al enviar formulario:", error)
      mostrarMensajeError("Error de conexión: " + error.message)
    } finally {
      // Restaurar botón
      if (submitBtn) {
        submitBtn.disabled = false
        submitBtn.innerHTML = originalText
      }
    }
  }

  function mostrarMensajeExito(mensaje, data) {
    if (elementos.successAlert) {
      elementos.successAlert.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle"></i>
          <strong>¡Éxito!</strong> ${mensaje}
          ${data.total_cursos ? `<br><small>Cursos inscritos: ${data.total_cursos}</small>` : ""}
          ${
            data.advertencias && data.advertencias.length > 0
              ? `<br><small class="text-warning">Advertencias: ${data.advertencias.join(", ")}</small>`
              : ""
          }
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `
      elementos.successAlert.style.display = "block"

      // Auto-ocultar después de 5 segundos
      setTimeout(() => {
        if (elementos.successAlert) {
          elementos.successAlert.style.display = "none"
        }
      }, 5000)
    } else {
      alert("¡Éxito! " + mensaje)
    }
  }

  function mostrarMensajeError(mensaje, errores = []) {
    let mensajeCompleto = mensaje

    if (errores && errores.length > 0) {
      mensajeCompleto += "\n\nDetalles:\n" + errores.join("\n")
    }

    if (elementos.successAlert) {
      elementos.successAlert.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-triangle"></i>
          <strong>Error</strong> ${mensaje}
          ${
            errores && errores.length > 0
              ? `<ul class="mt-2 mb-0">${errores.map((error) => `<li>${error}</li>`).join("")}</ul>`
              : ""
          }
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `
      elementos.successAlert.style.display = "block"
    } else {
      alert("Error: " + mensajeCompleto)
    }
  }

  function mostrarCursosInscritos(cursosInscritos) {
    if (!cursosInscritos || cursosInscritos.length === 0) return

    console.log("📚 Mostrando cursos en los que ya está inscrito:", cursosInscritos)

    // Aquí podrías mostrar una lista de cursos en los que ya está inscrito el usuario
    // Por ejemplo, en un modal o en una sección especial
  }

  console.log("=== REGISTRO.JS INICIALIZADO COMPLETAMENTE ===")
})
