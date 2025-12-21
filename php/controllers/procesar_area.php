<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    
    $id_area = $_POST["id_area"] ?? null;
    $nombre = $_POST["nombre"] ?? '';
        
    if ($accion === "crear") {
        $sql = "INSERT INTO area (nombre)
                VALUES (?)";
        $params = [$nombre];

    } elseif ($accion === "editar" && !empty($id_area)) {
        $sql = "UPDATE area SET nombre = ?
                WHERE id_area = ?";
        $params = [$nombre, $id_area];
                
    } elseif ($accion === "eliminar" && !empty($id_area)) {
        $sql = "DELETE FROM area WHERE id_area = ?";
        $params = [$id_area];

    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);


    
    if ($stmt) {
        header("Location: ../views/crud_area.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_area.php?error=area_en_uso");
        } else {
            header("Location: ../views/crud_area.php?error=general");
        }
        exit;
    }
}

?>