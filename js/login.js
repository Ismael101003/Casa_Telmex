document.addEventListener("DOMContentLoaded", () => {
  console.log("=== INICIANDO PÁGINA PRINCIPAL ===")

  // Cargar estadísticas al iniciar
  cargarEstadisticas()

  // Event listeners para los botones principales
  const nuevoRegistroBtn = document.querySelector('.card[onclick*="registro"]')
  const verUsuariosBtn = document.querySelector('.card[onclick*="usuarios"]')
  const administracionBtn = document.querySelector('.card[onclick*="admin-login"]')

  if (nuevoRegistroBtn) {
    nuevoRegistroBtn.addEventListener("click", () => {
      window.location.href = "registro.html"
    })
  }

  if (verUsuariosBtn) {
    verUsuariosBtn.addEventListener("click", () => {
      window.location.href = "usuarios.html"
    })
  }

  if (administracionBtn) {
    administracionBtn.addEventListener("click", () => {
      window.location.href = "admin-login.html"
    })
  }
})

async function cargarEstadisticas() {
  console.log("Cargando estadísticas de la página principal...")

  try {
    // Cargar usuarios registrados
    const responseUsuarios = await fetch("api/obtener_usuarios.php")
    const usuarios = await responseUsuarios.json()

    // Cargar cursos disponibles
    const responseCursos = await fetch("api/obtener_cursos.php")
    const dataCursos = await responseCursos.json()

    // Actualizar contador de usuarios
    const totalUsuarios = Array.isArray(usuarios) ? usuarios.length : 0
    const usuariosElement = document.querySelector(".stat-number")
    if (usuariosElement) {
      usuariosElement.textContent = totalUsuarios
    }

    // Actualizar contador de cursos disponibles
    let totalCursos = 0
    if (dataCursos.exito && Array.isArray(dataCursos.cursos)) {
      // Contar solo cursos activos
      totalCursos = dataCursos.cursos.filter((curso) => curso.activo == 1).length
    }

    const cursosElements = document.querySelectorAll(".stat-number")
    if (cursosElements.length > 1) {
      cursosElements[1].textContent = totalCursos
    }

    console.log(`Estadísticas actualizadas: ${totalUsuarios} usuarios, ${totalCursos} cursos disponibles`)
  } catch (error) {
    console.error("Error al cargar estadísticas:", error)

    // Mostrar valores por defecto en caso de error
    const statNumbers = document.querySelectorAll(".stat-number")
    if (statNumbers.length >= 2) {
      statNumbers[0].textContent = "0"
      statNumbers[1].textContent = "0"
    }
  }
}

// Función para actualizar estadísticas periódicamente
setInterval(cargarEstadisticas, 30000) // Actualizar cada 30 segundos
