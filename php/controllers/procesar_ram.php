<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $capacidad = $_POST["capacidad"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $tipo = $_POST["tipo"] ?? '';
    $frecuencia = $_POST["frecuencia"] ?? '';
    $serial_number = $_POST["serial_number"] ?? '';
    $id_ram = $_POST["id_ram"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO RAM (capacidad, id_marca, tipo, frecuencia, serial_number) VALUES (?, ?, ?, ?, ?)";
        $params = [$capacidad, $id_marca, $tipo, $frecuencia, $serial_number];
    } elseif ($accion === "editar" && !empty($id_ram)) {
        $sql = "UPDATE RAM SET capacidad = ?, id_marca = ?, tipo = ?, frecuencia = ?, serial_number = ? WHERE id_ram = ?";
        $params = [$capacidad, $id_marca, $tipo, $frecuencia, $serial_number, $id_ram];
    } elseif ($accion === "eliminar" && !empty($id_ram)) {
        // Eliminar registros relacionados en slot_activo_ram
        $sqlRelacionados = "DELETE FROM slot_activo_ram WHERE id_ram = ?";
        $stmtRelacionados = sqlsrv_query($conn, $sqlRelacionados, [$id_ram]);

        if ($stmtRelacionados) {
            // Eliminar el registro principal en RAM
            $sql = "DELETE FROM ram WHERE id_ram = ?";
            $params = [$id_ram];
        } else {
            die("Error al eliminar registros relacionados:<br>" . print_r(sqlsrv_errors(), true));
        }
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_ram.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if (strpos($errors[0]['message'], 'REFERENCE constraint') !== false) {
            header("Location: ../views/crud_ram.php?error=No se puede eliminar esta RAM porque está siendo usada por uno o más activos informáticos.");
        } else {
            header("Location: ../views/crud_ram.php?error=Error al eliminar el registro.");
        }
        exit;
    }

}