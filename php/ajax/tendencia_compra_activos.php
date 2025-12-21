<?php
include("../includes/conexion.php");

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

$result = [
    'Laptop' => [],
    'PC' => [],
    'Servidor' => []
];

foreach (['Laptop', 'PC', 'Servidor'] as $tipo) {
    $tabla = strtolower($tipo);
    $sql = "SELECT precioCompra, fechaCompra
            FROM $tabla
            WHERE MONTH(fechaCompra) = $mes
              AND YEAR(fechaCompra) = $anio
              AND precioCompra IS NOT NULL";
    $res = sqlsrv_query($conn, $sql);
    $precios = [];
    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        // Manejo seguro de fechaCompra (puede ser DateTime o string)
        if ($row['fechaCompra'] instanceof DateTime) {
            $fecha = $row['fechaCompra']->format('Y-m-d');
        } else {
            $fecha = date('Y-m-d', strtotime($row['fechaCompra']));
        }
        $precios[] = [
            'precio' => floatval($row['precioCompra']),
            'fecha' => $fecha
        ];
    }
    $result[$tipo] = $precios;
}

header('Content-Type: application/json');
echo json_encode($result);
