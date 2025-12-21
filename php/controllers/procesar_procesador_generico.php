<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $modelo = $_POST["modelo"] ?? '';
    $generacion = $_POST["generacion"] ?? '';
    $id_procesador_generico = $_POST["id_procesador_generico"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO procesador_generico (modelo, generacion) VALUES (?, ?)";
        $params = [$modelo, $generacion];
    } elseif ($accion === "editar" && !empty($id_procesador_generico)) {
        $sql = "UPDATE procesador_generico SET modelo = ?, generacion = ? WHERE id_procesador_generico = ?";
        $params = [$modelo, $generacion, $id_procesador_generico];
    } elseif ($accion === "eliminar" && !empty($id_procesador_generico)) {
        $sql = "DELETE FROM procesador_generico WHERE id_procesador_generico = ?";
        $params = [$id_procesador_generico];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_procesador_generico.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
