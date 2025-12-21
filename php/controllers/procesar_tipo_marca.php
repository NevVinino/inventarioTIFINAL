<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $nombre = $_POST["nombre"] ?? '';
    $id_tipo_marca = $_POST["id_tipo_marca"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO tipo_marca (nombre) VALUES (?)";
        $params = [$nombre];
    } elseif ($accion === "editar" && !empty($id_tipo_marca)) {
        $sql = "UPDATE tipo_marca SET nombre = ? WHERE id_tipo_marca = ?";
        $params = [$nombre, $id_tipo_marca];
    } elseif ($accion === "eliminar" && !empty($id_tipo_marca)) {
        $sql = "DELETE FROM tipo_marca WHERE id_tipo_marca = ?";
        $params = [$id_tipo_marca];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_tipo_marca.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
