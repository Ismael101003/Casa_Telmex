/**
 * Módulo para validación de edad y manejo de documentos según la edad del usuario
 * Específicamente para ocultar el campo INE cuando el usuario es menor de edad
 */

document.addEventListener("DOMContentLoaded", () => {
  console.log("=== INICIANDO VALIDACIÓN DE EDAD ===")

  // Inicializar validaciones de edad
  inicializarValidacionesEdad()

  function inicializarValidacionesEdad() {
    console.log("Configurando validaciones de edad...")

    // Event listeners para campos de fecha de nacimiento
    setupFechaNacimientoListeners()

    // Event listeners para campos CURP (que pueden cambiar la fecha)
    setupCurpEdadListeners()

    // Validar edad inicial si hay datos precargados
    validarEdadInicial()
  }

  function setupFechaNacimientoListeners() {
    // Para modo añadir usuario
    const addFechaNacimientoInput = document.getElementById("addFechaNacimiento")
    if (addFechaNacimientoInput) {
      addFechaNacimientoInput.addEventListener("change", (e) => {
        const fecha = e.target.value
        console.log("📅 Fecha de nacimiento cambiada (añadir):", fecha)
        toggleIneVisibilityByMode("add", fecha)
      })

      addFechaNacimientoInput.addEventListener("blur", (e) => {
        const fecha = e.target.value
        if (fecha) {
          toggleIneVisibilityByMode("add", fecha)
        }
      })
    }

    // Para modo actualizar usuario
    const updateFechaNacimientoInput = document.getElementById("updateFechaNacimiento")
    if (updateFechaNacimientoInput) {
      updateFechaNacimientoInput.addEventListener("change", (e) => {
        const fecha = e.target.value
        console.log("📅 Fecha de nacimiento cambiada (actualizar):", fecha)
        toggleIneVisibilityByMode("update", fecha)
      })

      updateFechaNacimientoInput.addEventListener("blur", (e) => {
        const fecha = e.target.value
        if (fecha) {
          toggleIneVisibilityByMode("update", fecha)
        }
      })
    }
  }

  function setupCurpEdadListeners() {
    // Para modo añadir - cuando se extrae fecha del CURP
    const addCurpInput = document.getElementById("addCurp")
    if (addCurpInput) {
      addCurpInput.addEventListener("input", () => {
        // Esperar un poco para que se procese la extracción de fecha
        setTimeout(() => {
          const fechaInput = document.getElementById("addFechaNacimiento")
          if (fechaInput && fechaInput.value) {
            toggleIneVisibilityByMode("add", fechaInput.value)
          }
        }, 100)
      })
    }

    // Para modo actualizar - cuando se extrae fecha del CURP
    const updateCurpInput = document.getElementById("updateCurp")
    if (updateCurpInput) {
      updateCurpInput.addEventListener("input", () => {
        // Esperar un poco para que se procese la extracción de fecha
        setTimeout(() => {
          const fechaInput = document.getElementById("updateFechaNacimiento")
          if (fechaInput && fechaInput.value) {
            toggleIneVisibilityByMode("update", fechaInput.value)
          }
        }, 100)
      })
    }
  }

  function validarEdadInicial() {
    // Validar en modo actualizar si ya hay datos cargados
    const updateFechaNacimientoInput = document.getElementById("updateFechaNacimiento")
    if (updateFechaNacimientoInput && updateFechaNacimientoInput.value) {
      console.log("🔍 Validando edad inicial en modo actualizar")
      toggleIneVisibilityByMode("update", updateFechaNacimientoInput.value)
    }

    // Validar en modo añadir si ya hay datos
    const addFechaNacimientoInput = document.getElementById("addFechaNacimiento")
    if (addFechaNacimientoInput && addFechaNacimientoInput.value) {
      console.log("🔍 Validando edad inicial en modo añadir")
      toggleIneVisibilityByMode("add", addFechaNacimientoInput.value)
    }
  }

  function toggleIneVisibilityByMode(mode, fechaNacimiento) {
    if (!fechaNacimiento) {
      console.log("❌ No hay fecha de nacimiento para validar")
      return
    }

    const edad = calcularEdad(fechaNacimiento)
    console.log(`👤 Edad calculada: ${edad} años (modo: ${mode})`)

    // Obtener elementos específicos del modo
    const docIneContainer = getDocIneContainer(mode)
    const docIneCheckbox = getDocIneCheckbox(mode)

    if (!docIneContainer) {
      console.log(`❌ No se encontró contenedor INE para modo: ${mode}`)
      return
    }

    if (edad < 18) {
      // Menor de edad - ocultar INE
      console.log("🚫 Usuario menor de edad - ocultando campo INE")
      docIneContainer.style.display = "none"

      if (docIneCheckbox) {
        docIneCheckbox.checked = false
        // Disparar evento change para que otros sistemas se enteren
        docIneCheckbox.dispatchEvent(new Event("change"))
      }

      // Mostrar mensaje informativo
      mostrarMensajeEdad(mode, "menor", edad)
    } else {
      // Mayor de edad - mostrar INE
      console.log("✅ Usuario mayor de edad - mostrando campo INE")
      docIneContainer.style.display = "block"

      // Ocultar mensaje si existe
      ocultarMensajeEdad(mode)
    }
  }

  function getDocIneContainer(mode) {
    // Buscar el contenedor del documento INE
    const selector =
      mode === "add" ? '.document-item:has(input[name="doc_ine"])' : '.document-item:has(input[id="updateDocIne"])'

    let container = document.querySelector(selector)

    // Fallback: buscar por diferentes métodos
    if (!container) {
      const checkbox = getDocIneCheckbox(mode)
      if (checkbox) {
        container = checkbox.closest(".document-item")
      }
    }

    return container
  }

  function getDocIneCheckbox(mode) {
    if (mode === "add") {
      return document.querySelector('input[name="doc_ine"]')
    } else {
      return document.getElementById("updateDocIne")
    }
  }

  function mostrarMensajeEdad(mode, tipoEdad, edad) {
    const containerId = mode === "add" ? "addEdadMessage" : "updateEdadMessage"
    let messageContainer = document.getElementById(containerId)

    // Crear contenedor de mensaje si no existe
    if (!messageContainer) {
      messageContainer = document.createElement("div")
      messageContainer.id = containerId
      messageContainer.className = "edad-message"

      // Buscar dónde insertar el mensaje
      const documentsSection =
        mode === "add"
          ? document.querySelector("#addUserMode .documents-checklist")
          : document.querySelector("#updateUserMode .documents-checklist")

      if (documentsSection) {
        documentsSection.insertBefore(messageContainer, documentsSection.firstChild)
      }
    }

    if (tipoEdad === "menor") {
      messageContainer.innerHTML = `
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <strong>Usuario menor de edad (${edad} años)</strong>
          <p>El documento INE no es requerido para menores de 18 años.</p>
        </div>
      `
      messageContainer.style.display = "block"
    }
  }

  function ocultarMensajeEdad(mode) {
    const containerId = mode === "add" ? "addEdadMessage" : "updateEdadMessage"
    const messageContainer = document.getElementById(containerId)

    if (messageContainer) {
      messageContainer.style.display = "none"
    }
  }

  function calcularEdad(fechaNacimiento) {
    if (!fechaNacimiento) return 0

    try {
      const hoy = new Date()
      const nacimiento = new Date(fechaNacimiento)

      // Validar que la fecha sea válida
      if (isNaN(nacimiento.getTime())) {
        console.error("❌ Fecha de nacimiento no válida:", fechaNacimiento)
        return 0
      }

      let edad = hoy.getFullYear() - nacimiento.getFullYear()
      const mes = hoy.getMonth() - nacimiento.getMonth()

      // Ajustar si aún no ha cumplido años este año
      if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--
      }

      // Validar que la edad sea razonable
      if (edad < 0 || edad > 120) {
        console.error("❌ Edad calculada fuera de rango:", edad)
        return 0
      }

      return edad
    } catch (error) {
      console.error("❌ Error calculando edad:", error)
      return 0
    }
  }

  // Función para validar edad desde otros módulos
  window.validarEdadUsuario = (fechaNacimiento, modo = "add") => {
    if (fechaNacimiento) {
      toggleIneVisibilityByMode(modo, fechaNacimiento)
    }
  }

  // Función para obtener edad calculada
  window.obtenerEdadUsuario = (fechaNacimiento) => calcularEdad(fechaNacimiento)

  // Observer para detectar cuando se cargan datos en el formulario de actualización
  const observerConfig = { childList: true, subtree: true }
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === "childList") {
        // Verificar si se mostró el formulario de actualización
        const updateFormContainer = document.getElementById("updateFormContainer")
        if (updateFormContainer && updateFormContainer.style.display === "block") {
          // Esperar un poco y validar edad
          setTimeout(() => {
            const fechaInput = document.getElementById("updateFechaNacimiento")
            if (fechaInput && fechaInput.value) {
              console.log("🔄 Formulario de actualización detectado, validando edad...")
              toggleIneVisibilityByMode("update", fechaInput.value)
            }
          }, 200)
        }
      }
    })
  })

  // Observar cambios en el contenedor principal
  const mainContent = document.querySelector(".main-content")
  if (mainContent) {
    observer.observe(mainContent, observerConfig)
  }

  console.log("✅ Validación de edad inicializada correctamente")
})

// Estilos CSS para los mensajes de edad
const edadStyles = document.createElement("style")
edadStyles.textContent = `
  .edad-message {
    margin-bottom: 15px;
  }

  .edad-message .alert {
    padding: 12px 15px;
    border-radius: 6px;
    border: 1px solid #bee5eb;
    background-color: #d1ecf1;
    color: #0c5460;
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  .edad-message .alert-info {
    border-color: #bee5eb;
    background-color: #d1ecf1;
    color: #0c5460;
  }

  .edad-message .alert i {
    margin-top: 2px;
    flex-shrink: 0;
  }

  .edad-message .alert div {
    flex: 1;
  }

  .edad-message .alert strong {
    display: block;
    margin-bottom: 4px;
  }

  .edad-message .alert p {
    margin: 0;
    font-size: 14px;
  }

  /* Animación suave para mostrar/ocultar */
  .document-item {
    transition: opacity 0.3s ease, height 0.3s ease;
  }

  .document-item[style*="display: none"] {
    opacity: 0;
    height: 0;
    overflow: hidden;
    margin: 0;
    padding: 0;
  }
`

document.head.appendChild(edadStyles)
