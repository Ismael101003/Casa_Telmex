// js/edad-validation.js

function mostrarOcultarDocumentosDerechohabiente() {
  const derechohabiencia = document.getElementById("tipoAfiliacion").value

  // Elementos que se muestran/ocultan según derechohabiencia
  const elementosAdd = ["addDocCedulaAfiliacion"]
  const elementosUpdate = ["updateDocCedulaAfiliacion"]

  const elementosAddIniciales = ["addDocIdentificacion", "addDocComprobanteDomicilio", "addDocCurp"]
  const elementosUpdateIniciales = ["updateDocIdentificacion", "updateDocComprobanteDomicilio", "updateDocCurp"]

  const todosElementosAdd = elementosAddIniciales.concat(elementosAdd)
  const todosElementosUpdate = elementosUpdateIniciales.concat(elementosUpdate)
 const updateDocFichaRegistro = document.getElementById("updateDocFichaRegistro")
const updateDocPermisoSalida = document.getElementById("updateDocPermisoSalida")
  if (derechohabiencia === "sin_afiliacion") {
    todosElementosAdd.forEach((id) => {
      const elemento = document.getElementById(id)
      if (elemento) {
        elemento.style.display = "block"
      }
    })

    todosElementosUpdate.forEach((id) => {
      const elemento = document.getElementById(id)
      if (elemento) {
        elemento.style.display = "none"
      }
    })
  } else {
    todosElementosAdd.forEach((id) => {
      const elemento = document.getElementById(id)
      if (elemento) {
        elemento.style.display = "none"
      }
    })

    todosElementosUpdate.forEach((id) => {
      const elemento = document.getElementById(id)
      if (elemento) {
        elemento.style.display = "block"
      }
    })
  }
}

function cargarDatosUsuario() {
  // Obtener el ID del usuario de la URL
  const urlParams = new URLSearchParams(window.location.search)
  const idUsuario = urlParams.get("id")

  // Realizar una solicitud AJAX para obtener los datos del usuario
  fetch(`obtener_usuario.php?id=${idUsuario}`)
    .then((response) => response.json())
    .then((usuario) => {
      // Llenar los campos del formulario con los datos del usuario
      document.getElementById("nombre").value = usuario.nombre
      document.getElementById("apellidoPaterno").value = usuario.apellido_paterno
      document.getElementById("apellidoMaterno").value = usuario.apellido_materno
      document.getElementById("fechaNacimiento").value = usuario.fecha_nacimiento
      document.getElementById("curp").value = usuario.curp
      document.getElementById("telefono").value = usuario.telefono
      document.getElementById("email").value = usuario.email
      document.getElementById("domicilio").value = usuario.domicilio
      document.getElementById("codigoPostal").value = usuario.codigo_postal
      document.getElementById("estado").value = usuario.estado


      

      // Cargar los checkboxes de documentos
      document.getElementById("updateDocIdentificacion").checked = usuario.doc_identificacion == 1
      document.getElementById("updateDocComprobanteDomicilio").checked = usuario.doc_comprobante_domicilio == 1
      document.getElementById("updateDocCurp").checked = usuario.doc_curp == 1
      document.getElementById("updateDocCedula").checked = usuario.doc_cedula_afiliacion == 1

      // Mostrar u ocultar los documentos según la derechohabiencia
      mostrarOcultarDocumentosDerechohabiente()
    })
    .catch((error) => console.error("Error al obtener los datos del usuario:", error))
}

// Esperar a que el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
  // Cargar los datos del usuario al cargar la página
  cargarDatosUsuario()

  // Agregar un evento de escucha al campo de tipo de afiliación
  document.getElementById("tipoAfiliacion").addEventListener("change", mostrarOcultarDocumentosDerechohabiente)
})
