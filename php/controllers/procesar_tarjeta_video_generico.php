<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $modelo = $_POST["modelo"] ?? '';
    $memoria = $_POST["memoria"] ?? '';
    $id_tarjeta_video_generico = $_POST["id_tarjeta_video_generico"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO tarjeta_video_generico (modelo, memoria) VALUES (?, ?)";
        $params = [$modelo, $memoria];
    } elseif ($accion === "editar" && !empty($id_tarjeta_video_generico)) {
        $sql = "UPDATE tarjeta_video_generico SET modelo = ?, memoria = ? WHERE id_tarjeta_video_generico = ?";
        $params = [$modelo, $memoria, $id_tarjeta_video_generico];
    } elseif ($accion === "eliminar" && !empty($id_tarjeta_video_generico)) {
        $sql = "DELETE FROM tarjeta_video_generico WHERE id_tarjeta_video_generico = ?";
        $params = [$id_tarjeta_video_generico];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_tarjeta_video_generico.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_tarjeta_video_generico.php?error=No se puede eliminar esta tarjeta de video genérica porque está siendo usada por un activo informático.");
        } else {
            header("Location: ../views/crud_tarjeta_video_generico.php?error=Error al eliminar el registro.");
        }
        exit;
    }
}
