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

  // Nuevos elementos
  const coursesGrid = document.getElementById("coursesGrid")
  const limpiarCursosBtn = document.getElementById("limpiarCursosBtn")

  // Variables globales
  let currentSection = "dashboard"
  let cursosConUsuarios = []
  let editingCourseId = null // Variable para rastrear si estamos editando

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
        abrirModalCurso() // Sin parámetros = nuevo curso
      } else if (currentSection === "usuarios") {
        window.location.href = "registro.html"
      } else if (currentSection === "admins") {
        abrirModalAdmin()
      }
    })
  }

  // Event listeners para modales
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
  const exportarExcelBtn = document.getElementById("exportarExcelBtn")

  if (closeListaUsuariosModal) closeListaUsuariosModal.addEventListener("click", cerrarModalListaUsuarios)
  if (cerrarListaBtn) cerrarListaBtn.addEventListener("click", cerrarModalListaUsuarios)
  if (imprimirListaBtn) imprimirListaBtn.addEventListener("click", imprimirLista)
  if (exportarListaBtn) exportarListaBtn.addEventListener("click", exportarLista)
  if (exportarExcelBtn) exportarExcelBtn.addEventListener("click", exportarExcel)

  // Event listener para limpiar cursos
  if (limpiarCursosBtn) {
    limpiarCursosBtn.addEventListener("click", limpiarCursosTerminados)
  }

  function inicializarPanel() {
    cargarEstadisticas()
    cambiarSeccion("dashboard")
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
      cursos: { titulo: "Gestión de Cursos", subtitulo: "Administrar cursos disponibles" },
      "cursos-usuarios": { titulo: "Cursos y Listas", subtitulo: "Ver listas de usuarios por curso" },
      inscripciones: { titulo: "Inscripciones", subtitulo: "Gestionar inscripciones de usuarios" },
      admins: { titulo: "Administradores", subtitulo: "Gestionar administradores del sistema" },
      reportes: { titulo: "Reportes", subtitulo: "Estadísticas y reportes del sistema" },
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
        const totalAdmins = document.getElementById("totalAdmins")

        if (totalUsuarios) totalUsuarios.textContent = stats.total_usuarios || 0
        if (totalCursos) totalCursos.textContent = stats.total_cursos || 0
        if (totalInscripciones) totalInscripciones.textContent = stats.total_inscripciones || 0
        if (totalAdmins) totalAdmins.textContent = stats.total_admins || 0
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

    tableBody.innerHTML = '<tr><td colspan="7" class="loading-row">Cargando usuarios...</td></tr>'

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
          tableBody.innerHTML = '<tr><td colspan="7" class="loading-row">No hay usuarios registrados</td></tr>'
          return
        }

        const usuariosHTML = usuarios
          .map((usuario) => {
            const id = usuario.id_usuario || usuario.id || "N/A"
            return `
          <tr>
            <td>${id}</td>
            <td>${usuario.nombre || "N/A"} ${usuario.apellidos || ""}</td>
            <td>${usuario.curp || "N/A"}</td>
            <td>${usuario.edad || "N/A"} años</td>
            <td>${usuario.tutor || "N/A"}</td>
            <td>${usuario.fecha_registro || "N/A"}</td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="verUsuario(${id})" ${id === "N/A" ? "disabled" : ""}>
                <i class="fas fa-eye"></i>
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
        tableBody.innerHTML = `<tr><td colspan="7" class="loading-row">Error: ${usuarios.mensaje}</td></tr>`
      } else {
        tableBody.innerHTML = '<tr><td colspan="7" class="loading-row">Formato de respuesta no válido</td></tr>'
      }
    } catch (error) {
      console.error("Error al cargar usuarios:", error)
      tableBody.innerHTML = `<tr><td colspan="7" class="loading-row">Error al cargar usuarios: ${error.message}</td></tr>`
    }
  }

  async function cargarCursos() {
    console.log("=== CARGANDO CURSOS ===")
    const tableBody = document.getElementById("cursosTableBody")
    if (!tableBody) return

    tableBody.innerHTML = '<tr><td colspan="9" class="loading-row">Cargando cursos...</td></tr>'

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
          tableBody.innerHTML = '<tr><td colspan="9" class="loading-row">No hay cursos registrados</td></tr>'
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
            <td>
              <span class="badge ${curso.activo == 1 ? "badge-success" : "badge-danger"}">
                ${curso.activo == 1 ? "Activo" : "Inactivo"}
              </span>
            </td>
            <td>
              <span class="table-badge ${curso.tabla_curso ? "badge-success" : "badge-warning"}">
                ${curso.tabla_curso || "Sin tabla"}
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
        tableBody.innerHTML = `<tr><td colspan="9" class="loading-row">Error: ${data.mensaje || "Error desconocido"}</td></tr>`
      }
    } catch (error) {
      console.error("Error al cargar cursos:", error)
      tableBody.innerHTML = `<tr><td colspan="9" class="loading-row">Error al cargar cursos: ${error.message}</td></tr>`
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
            <p><i class="fas fa-table"></i> Tabla: curso_${curso.id_curso}</p>
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

    tableBody.innerHTML = '<tr><td colspan="6" class="loading-row">Cargando inscripciones...</td></tr>'

    try {
      const response = await fetch("api/obtener_inscripciones.php")
      const data = await response.json()

      console.log("Datos de inscripciones:", data)

      if (data.exito && Array.isArray(data.inscripciones)) {
        const inscripciones = data.inscripciones

        if (inscripciones.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="6" class="loading-row">No hay inscripciones registradas</td></tr>'
          return
        }

        const inscripcionesHTML = inscripciones
          .map(
            (inscripcion) => `
          <tr>
            <td>${inscripcion.id_inscripcion}</td>
            <td>${inscripcion.nombre_usuario}</td>
            <td>${inscripcion.curp}</td>
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
        tableBody.innerHTML = `<tr><td colspan="6" class="loading-row">Error: ${data.mensaje || "Error al cargar inscripciones"}</td></tr>`
      }
    } catch (error) {
      console.error("Error al cargar inscripciones:", error)
      tableBody.innerHTML = '<tr><td colspan="6" class="loading-row">Error al cargar inscripciones</td></tr>'
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

  // Funciones de modales - CORREGIDAS PARA EVITAR CONFLICTOS
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
    console.log("Viendo lista de usuarios del curso:", idCurso)

    try {
      const response = await fetch(`api/obtener_tabla_curso.php?id_curso=${idCurso}`)
      const data = await response.json()

      console.log("Datos de la lista de usuarios:", data)

      if (data.exito) {
        const curso = data.curso
        const usuarios = data.usuarios

        const listaUsuariosTitle = document.getElementById("listaUsuariosTitle")
        if (listaUsuariosTitle) {
          listaUsuariosTitle.textContent = `Lista de Usuarios - ${curso.nombre_curso}`
        }

        const tableBody = document.getElementById("listaUsuariosTableBody")
        if (tableBody) {
          if (usuarios.length === 0) {
            tableBody.innerHTML =
              '<tr><td colspan="7" class="no-data">No hay usuarios inscritos en este curso</td></tr>'
          } else {
            const usuariosHTML = usuarios
              .map(
                (usuario, index) => `
              <tr>
                <td>${index + 1}</td>
                <td>${usuario.nombre} ${usuario.apellidos}</td>
                <td>${usuario.curp}</td>
                <td>${usuario.edad} años</td>
                <td>${usuario.tutor}</td>
                <td>${usuario.numero_tutor}</td>
                <td>${usuario.fecha_inscripcion_formateada}</td>
              </tr>
            `,
              )
              .join("")

            tableBody.innerHTML = usuariosHTML
          }
        }

        window.currentCourseData = {
          curso: curso,
          usuarios: usuarios,
        }

        if (listaUsuariosModal) listaUsuariosModal.style.display = "block"
      } else {
        alert("Error al cargar lista de usuarios: " + data.mensaje)
      }
    } catch (error) {
      console.error("Error:", error)
      alert("Error de conexión al cargar lista de usuarios")
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
          <h1>Casa Telmex</h1>
          <h2>Lista de Usuarios</h2>
        </div>
        <div class="course-info">
          <p><strong>Curso:</strong> ${curso.nombre_curso}</p>
          <p><strong>Horario:</strong> ${curso.horario || "Por definir"}</p>
          <p><strong>Edad:</strong> ${curso.edad_min}-${curso.edad_max} años</p>
          <p><strong>Total de usuarios:</strong> ${usuarios.length}</p>
          <p><strong>Fecha de impresión:</strong> ${new Date().toLocaleDateString()}</p>
        </div>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre Completo</th>
              <th>CURP</th>
              <th>Edad</th>
              <th>Tutor</th>
              <th>Teléfono</th>
              <th>Fecha Inscripción</th>
            </tr>
          </thead>
          <tbody>
            ${usuarios
              .map(
                (usuario, index) => `
              <tr>
                <td>${index + 1}</td>
                <td>${usuario.nombre} ${usuario.apellidos}</td>
                <td>${usuario.curp}</td>
                <td>${usuario.edad} años</td>
                <td>${usuario.tutor}</td>
                <td>${usuario.numero_tutor}</td>
                <td>${usuario.fecha_inscripcion_formateada}</td>
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
    if (!window.currentCourseData) {
      alert("No hay datos para exportar")
      return
    }

    const { curso, usuarios } = window.currentCourseData

    let csv = "No.,Nombre Completo,CURP,Edad,Tutor,Teléfono,Fecha Inscripción\n"

    usuarios.forEach((usuario, index) => {
      csv += `${index + 1},"${usuario.nombre} ${usuario.apellidos}","${usuario.curp}","${usuario.edad} años","${
        usuario.tutor
      }","${usuario.numero_tutor}","${usuario.fecha_inscripcion_formateada}"\n`
    })

    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" })
    const link = document.createElement("a")
    const url = URL.createObjectURL(blob)
    link.setAttribute("href", url)
    link.setAttribute("download", `lista_usuarios_${curso.nombre_curso.replace(/\s+/g, "_")}.csv`)
    link.style.visibility = "hidden"
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }

  function exportarExcel() {
    if (!window.currentCourseData) {
      alert("No hay datos para exportar a Excel")
      return
    }

    const { curso } = window.currentCourseData
    const url = `api/exportar_usuarios_excel.php?id_curso=${curso.id_curso}`

    window.open(url, "_blank")
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
        const detalleFechaRegistro = document.getElementById("detalleFechaRegistro")
        const totalCursosUsuario = document.getElementById("totalCursosUsuario")

        if (usuarioDetallesTitle)
          usuarioDetallesTitle.textContent = `Detalles de ${usuario.nombre} ${usuario.apellidos}`
        if (detalleNombre) detalleNombre.textContent = `${usuario.nombre} ${usuario.apellidos}`
        if (detalleCurp) detalleCurp.textContent = usuario.curp
        if (detalleEdad) detalleEdad.textContent = `${usuario.edad} años`
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
      alert("Error de conexión al cargar detalles del usuario")
      cerrarModalUsuarioDetalles()
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
          alert("Curso y tabla específica eliminados exitosamente")
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
        alert("Error al cargar administrador")
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

  // Asignar funciones específicas de la sección cursos-usuarios
  window.verListaUsuarios = verListaUsuarios
  window.verTablaCurso = (idCurso) => {
    window.open(`tabla-curso.html?id=${idCurso}`, "_blank")
  }

  // Cerrar modales al hacer clic fuera
  window.addEventListener("click", (e) => {
    if (e.target === cursoModal) {
      cerrarModalCurso()
    }
    if (e.target === adminModal) {
      cerrarModalAdmin()

      cerrarModalCurso()
    }
    if (e.target === adminModal) {
      cerrarModalAdmin()
    }
    if (e.target === usuarioDetallesModal) {
      cerrarModalUsuarioDetalles()
    }
    if (e.target === listaUsuariosModal) {
      cerrarModalListaUsuarios()
    }
  })

  console.log("=== ADMIN PANEL INICIALIZADO ===")
})
