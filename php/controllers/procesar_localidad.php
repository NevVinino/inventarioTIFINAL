<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    
    $localidad_nombre = $_POST["localidad_nombre"] ?? '';
    $id_localidad = $_POST["id_localidad"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO localidad (localidad_nombre) VALUES (?)";
        $params = [$localidad_nombre];

    } elseif ($accion === "editar" && !empty($id_localidad)) {
        $sql = "UPDATE localidad SET localidad_nombre = ? WHERE id_localidad = ?";
        $params = [$localidad_nombre, $id_localidad];

    } elseif ($accion === "eliminar" && !empty($id_localidad)) {
        $sql = "DELETE FROM localidad WHERE id_localidad = ?";
        $params = [$id_localidad];

    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_localidad.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_localidad.php?error=No se puede eliminar esta localidad porque se encuentra en uso.");
        } else {
            header("Location: ../views/crud_localidad.php?error=Error al eliminar el registro.");
        }
        exit;
    }


}


?>