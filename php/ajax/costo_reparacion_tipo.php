<?php
include("../includes/conexion.php");

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

$costos = [
    'Laptop' => 0,
    'PC' => 0,
    'Servidor' => 0
];
$cantidades = [
    'Laptop' => 0,
    'PC' => 0,
    'Servidor' => 0
];

foreach (['Laptop', 'PC', 'Servidor'] as $tipo) {
    $join = '';
    $where = '';
    if ($tipo === 'Laptop') {
        $join = 'LEFT JOIN laptop l ON a.id_laptop = l.id_laptop';
        $where = 'a.id_laptop IS NOT NULL';
    } elseif ($tipo === 'PC') {
        $join = 'LEFT JOIN pc p ON a.id_pc = p.id_pc';
        $where = 'a.id_pc IS NOT NULL';
    } else {
        $join = 'LEFT JOIN servidor s ON a.id_servidor = s.id_servidor';
        $where = 'a.id_servidor IS NOT NULL';
    }
    $sql = "SELECT SUM(r.costo) as total, COUNT(*) as cantidad
            FROM reparacion r
            INNER JOIN activo a ON r.id_activo = a.id_activo
            $join
            WHERE $where
                AND MONTH(r.fecha) = $mes
                AND YEAR(r.fecha) = $anio";
    $res = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $costos[$tipo] = floatval($row['total'] ?? 0);
    $cantidades[$tipo] = intval($row['cantidad'] ?? 0);
}

header('Content-Type: application/json');
echo json_encode([
    'costos' => $costos,
    'cantidades' => $cantidades
]);
