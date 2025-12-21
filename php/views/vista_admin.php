<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
</head>
<body>
    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <!-- Contenido principal -->
    <main class="main-content" id="mainContent">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2>Panel de Administración</h2>
                <p>Bienvenido al sistema de inventario de TI</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="icon">🏢</span>
                    </div>
                    <div class="stat-content">
                        <h3>Gestión Organizacional</h3>
                        <p>Usuarios, personas, áreas, localidades y empresas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="icon">💻</span>
                    </div>
                    <div class="stat-content">
                        <h3>Equipos de Cómputo</h3>
                        <p>Laptops, PCs y servidores</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="icon">🔧</span>
                    </div>
                    <div class="stat-content">
                        <h3>Componente de Hardware</h3>
                        <p>Procesadores, RAM, almacenamiento y tarjetas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="icon">📝</span>
                    </div>
                    <div class="stat-content">
                        <h3>Asignaciones</h3>
                        <p>Activos, periféricos y reparaciones</p>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">
                    <a href="crud_laptop.php" class="action-btn">
                        <span class="icon">💻</span>
                        <span>Crear Laptop</span>
                    </a>
                    <a href="crud_pc.php" class="action-btn">
                        <span class="icon">🖥</span>
                        <span>Crear PC</span>
                    </a>
                    <a href="crud_asignacion.php" class="action-btn">
                        <span class="icon">👉</span>
                        <span>Asignar Equipo</span>
                    </a>
                    <a href="crud_reparacion.php" class="action-btn">
                        <span class="icon">🔧</span>
                        <span>Reparaciones</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>
