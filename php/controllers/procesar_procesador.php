<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $modelo = $_POST["modelo"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $generacion = $_POST["generacion"] ?? '';
    $nucleos = $_POST["nucleos"] ?? '';
    $hilos = $_POST["hilos"] ?? '';
    $part_number = $_POST["part_number"] ?? '';
    $id_procesador = $_POST["id_procesador"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO procesador (modelo, id_marca, generacion, nucleos, hilos, part_number) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$modelo, $id_marca, $generacion, $nucleos, $hilos, $part_number];
    } elseif ($accion === "editar" && !empty($id_procesador)) {
        $sql = "UPDATE procesador SET modelo = ?, id_marca = ?, generacion = ?, nucleos = ?, hilos = ?, part_number = ? WHERE id_procesador = ?";
        $params = [$modelo, $id_marca, $generacion, $nucleos, $hilos, $part_number, $id_procesador];
    } elseif ($accion === "eliminar" && !empty($id_procesador)) {
        $sql = "DELETE FROM procesador WHERE id_procesador = ?";
        $params = [$id_procesador];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_procesador.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_procesador.php?error=No se puede eliminar este procesador porque está siendo usado por uno o más activos informáticos.");
        } else {
            header("Location: ../views/crud_procesador.php?error=Error al eliminar el registro.");
        }
        exit;
    }
}
