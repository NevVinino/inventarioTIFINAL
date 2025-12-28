<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Campos del formulario
    $id_persona = $_POST["id_persona"] ?? '';
    $nombre = $_POST["nombre"] ?? '';
    $apellido = $_POST["apellido"] ?? '';
    $correo = $_POST["correo"] ?? '';
    $celular = $_POST["celular"] ?? '';
    $jefe_inmediato = !empty($_POST["jefe_inmediato"]) ? intval($_POST["jefe_inmediato"]) : null;

    $id_tipo_persona = $_POST["id_tipo_persona"] ?? ''; // Changed from id_tipo
    $id_situacion_personal = $_POST["id_situacion_personal"] ?? '';
    $id_localidad = $_POST["id_localidad"] ?? '';
    $id_area = $_POST["id_area"] ?? '';
    $id_empresa = $_POST["id_empresa"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO persona (nombre, apellido, correo, celular, jefe_inmediato,
                id_tipo_persona, id_situacion_personal, id_localidad, id_area, id_empresa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$nombre, $apellido, $correo, $celular, $jefe_inmediato,
                   $id_tipo_persona, $id_situacion_personal, $id_localidad, $id_area, $id_empresa];

    } elseif ($accion === "editar" && !empty($id_persona)) {
        $sql = "UPDATE persona SET nombre = ?, apellido = ?, correo = ?, celular = ?,
                jefe_inmediato = ?, id_tipo_persona = ?, id_situacion_personal = ?, id_localidad = ?,
                id_area = ?, id_empresa = ?
                WHERE id_persona = ?";
        $params = [$nombre, $apellido, $correo, $celular, $jefe_inmediato,
                   $id_tipo_persona, $id_situacion_personal, $id_localidad, $id_area, $id_empresa, $id_persona];

    } elseif ($accion === "eliminar" && !empty($id_persona)) {
        $sql = "DELETE FROM persona WHERE id_persona = ?";
        $params = [$id_persona];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_persona.php?success=1");
        exit;
    } else {
        // Capturar el error
        $errors = sqlsrv_errors();
        $errorMessage = '';
        
        // Verificar si es un error de clave foránea (constraint)
        if ($errors) {
            foreach ($errors as $error) {
                // Código de error de SQL Server para violación de clave foránea
                if ($error['code'] == 547 || strpos($error['message'], 'REFERENCE') !== false || 
                    strpos($error['message'], 'conflicted') !== false) {
                    $errorMessage = 'No se puede eliminar debido a que se encuentra en uso';
                    break;
                } else {
                    $errorMessage = 'Error en la operación';
                }
            }
        }
        
        // Redirigir con mensaje de error
        header("Location: ../views/crud_persona.php?error=" . urlencode($errorMessage));
        exit;
    }
}
?>
