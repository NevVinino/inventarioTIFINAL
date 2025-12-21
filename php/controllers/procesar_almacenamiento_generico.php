<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $capacidad = $_POST["capacidad"] ?? '';
    $tipo = $_POST["tipo"] ?? '';
    $id_almacenamiento_generico = $_POST["id_almacenamiento_generico"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO almacenamiento_generico (capacidad, tipo) VALUES (?, ?)";
        $params = [$capacidad, $tipo];
    } elseif ($accion === "editar" && !empty($id_almacenamiento_generico)) {
        $sql = "UPDATE almacenamiento_generico SET capacidad = ?, tipo = ? WHERE id_almacenamiento_generico = ?";
        $params = [$capacidad, $tipo, $id_almacenamiento_generico];
    } elseif ($accion === "eliminar" && !empty($id_almacenamiento_generico)) {
        $sql = "DELETE FROM almacenamiento_generico WHERE id_almacenamiento_generico = ?";
        $params = [$id_almacenamiento_generico];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_almacenamiento_generico.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
