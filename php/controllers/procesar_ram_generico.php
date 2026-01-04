<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $capacidad = $_POST["capacidad"] ?? '';
    $id_ram_generico = $_POST["id_ram_generico"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO RAM_generico (capacidad) VALUES (?)";
        $params = [$capacidad];
    } elseif ($accion === "editar" && !empty($id_ram_generico)) {
        $sql = "UPDATE RAM_generico SET capacidad = ? WHERE id_ram_generico = ?";
        $params = [$capacidad, $id_ram_generico];
    } elseif ($accion === "eliminar" && !empty($id_ram_generico)) {
        $sql = "DELETE FROM RAM_generico WHERE id_ram_generico = ?";
        $params = [$id_ram_generico];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_ram_generico.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_ram_generico.php?error=No se puede eliminar esta RAM genérica porque está siendo usada por un activo informático.");
        } else {
            header("Location: ../views/crud_ram_generico.php?error=Error al eliminar el registro.");
        }
        exit;
    }
}
    
