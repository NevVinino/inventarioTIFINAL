<?php
// Configurar el manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar extensiones de PHP necesarias
if (!extension_loaded('mbstring')) {
    die('Error: La extensión mbstring de PHP no está habilitada. Dompdf la requiere para funcionar.');
}

// Configurar mbstring
mb_internal_encoding('UTF-8');

function generarPDFInventario($datos = []) {
    // Crear contenido HTML para mostrar en navegador como alternativa
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Inventario TI</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #007bff; color: white; }
            .print-btn { margin: 20px 0; }
            @media print {
                .print-btn { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="print-btn">
            <button onclick="window.print()">Imprimir como PDF</button>
            <a href="?accion=exportar" style="margin-left: 10px;">Exportar datos</a>
        </div>
        
        <div class="header">
            <h1>Reporte de Inventario TI</h1>
            <p>Fecha: ' . date('d/m/Y H:i:s') . '</p>
            <p>Total de equipos: ' . count($datos) . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($datos as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['equipo']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['marca']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['modelo']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['estado']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div style="margin-top: 20px;">
            <p><small>Para generar PDF: Use Ctrl+P y seleccione "Guardar como PDF"</small></p>
            <p><small>Este reporte muestra el inventario actual de equipos TI</small></p>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Ejemplo de uso
$datosEjemplo = [
    ['id' => '001', 'equipo' => 'Laptop', 'marca' => 'Dell', 'modelo' => 'Latitude 5520', 'estado' => 'Activo'],
    ['id' => '002', 'equipo' => 'Monitor', 'marca' => 'Samsung', 'modelo' => '24" LED', 'estado' => 'Activo'],
    ['id' => '003', 'equipo' => 'Teclado', 'marca' => 'Logitech', 'modelo' => 'K120', 'estado' => 'Mantenimiento']
];

// Manejar parámetros GET
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'ver';

if ($accion === 'exportar') {
    // Exportar como CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventario_' . date('Y-m-d') . '.csv"');
    
    echo "ID,Equipo,Marca,Modelo,Estado\n";
    foreach ($datosEjemplo as $item) {
        echo '"' . $item['id'] . '","' . $item['equipo'] . '","' . $item['marca'] . '","' . $item['modelo'] . '","' . $item['estado'] . '"' . "\n";
    }
} else {
    // Mostrar reporte HTML
    try {
        $html = generarPDFInventario($datosEjemplo);
        echo $html;
        
    } catch (Exception $e) {
        echo '<h2>Error al generar reporte:</h2>';
        echo '<p>' . $e->getMessage() . '</p>';
    }
}
?>
