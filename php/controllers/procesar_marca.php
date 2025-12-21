<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $nombre = $_POST["nombre"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $id_tipo_marca = $_POST["id_tipo_marca"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO marca (nombre, id_tipo_marca) VALUES (?, ?)";
        $params = [$nombre, $id_tipo_marca];
    } elseif ($accion === "editar" && !empty($id_marca)) {
        $sql = "UPDATE marca SET nombre = ?, id_tipo_marca = ? WHERE id_marca = ?";
        $params = [$nombre, $id_tipo_marca, $id_marca];
    } elseif ($accion === "eliminar" && !empty($id_marca)) {
        $sql = "DELETE FROM marca WHERE id_marca = ?";
        $params = [$id_marca];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_marca.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}