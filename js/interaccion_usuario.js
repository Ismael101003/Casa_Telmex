window.handleEliminarUsuarioDeCurso = async (idInscripcion, idUsuario, idCurso) => {
  console.log(`Intentando eliminar inscripción: ${idInscripcion} (Usuario: ${idUsuario}, Curso: ${idCurso})`);

  if (!confirm(`¿Estás seguro que deseas eliminar al usuario (ID: ${idUsuario}) del curso (ID: ${idCurso})? Esta acción es irreversible.`)) 
    return;

  try {
    const formData = new FormData();
    formData.append("id", idInscripcion); // The PHP API expects 'id' for id_inscripcion

    const response = await fetch("api/eliminar_inscripcion.php", {
      method: "POST",
      body: formData,
    });

    if (!response.ok) throw new Error("Respuesta del servidor no válida");

    const result = await response.json();

    if (result.exito) {
      alert("Usuario eliminado del curso exitosamente.");

      const listaUsuariosModal = document.getElementById("listaUsuariosModal");
      const currentCourseIdInModal = listaUsuariosModal ? listaUsuariosModal.dataset.currentCourseId : null;

      if (currentCourseIdInModal && typeof window.verListaUsuarios === 'function') {
        window.verListaUsuarios(Number.parseInt(currentCourseIdInModal));
      } else {
        console.warn("No se pudo obtener el ID del curso actual o la función verListaUsuarios no está disponible para refrescar la lista.");
        if (typeof window.cargarCursosConUsuarios === 'function')
          window.cargarCursosConUsuarios();
      }

      if (typeof window.cargarEstadisticas === 'function')
        window.cargarEstadisticas();

    } else {
      alert("Error al eliminar usuario del curso: " + result.mensaje);
    }
  } catch (error) {
    console.error("Error al intentar eliminar la inscripción:", error);
    alert("Ocurrió un error inesperado al intentar eliminar al usuario.");
  }
};
