<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="vista_admin.php" class="sidebar-header-link">
            <h2><span class="icon">⚙</span> Panel Admin</h2>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Gestión Organizacional -->
        <div class="nav-category">
            <div class="category-header" data-category="organizacional">
                <span class="icon">🏢</span>
                <span>Gestión Organizacional</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="organizacional">
                <a href="crud_usuarios.php" class="nav-item">
                    <span class="icon">👥</span>
                    <span>Crear Usuarios</span>
                </a>
                <a href="crud_persona.php" class="nav-item">
                    <span class="icon">👤</span>
                    <span>Crear Personas</span>
                </a>
                <a href="crud_area.php" class="nav-item">
                    <span class="icon">🗂</span>
                    <span>Crear Áreas</span>
                </a>
                <a href="crud_localidad.php" class="nav-item">
                    <span class="icon">📍</span>
                    <span>Crear Localidad</span>
                </a>
                <a href="crud_empresa.php" class="nav-item">
                    <span class="icon">🏛</span>
                    <span>Crear Empresa</span>
                </a>
                <a href="crud_proveedor.php" class="nav-item">
                    <span class="icon">🚚</span>
                    <span>Crear Proveedor</span>
                </a>
            </div>
        </div>

        <!-- Equipos de Cómputo -->
        <div class="nav-category">
            <div class="category-header" data-category="equipos">
                <span class="icon">💻</span>
                <span>Equipos de Cómputo</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="equipos">
                <a href="crud_laptop.php" class="nav-item">
                    <span class="icon">💻</span>
                    <span>Crear Laptop</span>
                </a>
                <a href="crud_pc.php" class="nav-item">
                    <span class="icon">🖥</span>
                    <span>Crear PC</span>
                </a>
                <a href="crud_servidor.php" class="nav-item">
                    <span class="icon">🗄</span>
                    <span>Crear Servidor</span>
                </a>
            </div>
        </div>

        <!-- Componentes de Hardware -->
        <div class="nav-category">
            <div class="category-header" data-category="hardware">
                <span class="icon">🔧</span>
                <span>Componentes de Hardware</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="hardware">
                <a href="crud_procesador.php" class="nav-item">
                    <span class="icon">⚡</span>
                    <span>Crear Procesador</span>
                </a>
                <a href="crud_ram.php" class="nav-item">
                    <span class="icon">🧠</span>
                    <span>Crear RAM</span>
                </a>
                <a href="crud_almacenamiento.php" class="nav-item">
                    <span class="icon">💾</span>
                    <span>Crear Almacenamiento</span>
                </a>
                <a href="crud_tarjeta_video.php" class="nav-item">
                    <span class="icon">🎮</span>
                    <span>Crear Tarjeta de Video</span>
                </a>
            </div>
        </div>

        <!-- Componentes Genéricos -->
        <div class="nav-category">
            <div class="category-header" data-category="genericos">
                <span class="icon">📦</span>
                <span>Componentes Genéricos</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="genericos">
                <a href="crud_procesador_generico.php" class="nav-item">
                    <span class="icon">⚡</span>
                    <span>Procesador Genérico</span>
                </a>
                <a href="crud_ram_generico.php" class="nav-item">
                    <span class="icon">🧠</span>
                    <span>RAM Genérico</span>
                </a>
                <a href="crud_almacenamiento_generico.php" class="nav-item">
                    <span class="icon">💾</span>
                    <span>Almacenamiento Genérico</span>
                </a>
                <a href="crud_tarjeta_video_generico.php" class="nav-item">
                    <span class="icon">🎮</span>
                    <span>Tarjeta Video Genérico</span>
                </a>
            </div>
        </div>

        <!-- Marcas y Tipos -->
        <div class="nav-category">
            <div class="category-header" data-category="marcas">
                <span class="icon">🏷</span>
                <span>Marcas y Tipos</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="marcas">
                <a href="crud_marca.php" class="nav-item">
                    <span class="icon">🔖</span>
                    <span>Gestión de Marcas por Tipo de Componente</span>
                </a>
                <a href="crud_tipo_marca.php" class="nav-item">
                    <span class="icon">🏷</span>
                    <span>Gestión de Tipos de componente</span> <!-- Antes "Crear Tipo de Marca" -->
                </a>

            </div>
        </div>

        
        <!-- Gestión de Almacén -->
        <div class="nav-category">
            <div class="category-header" data-category="almacen">
                <span class="icon">📦</span>
                <span>Gestión de Almacén</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="almacen">
                <a href="crud_almacen.php" class="nav-item">
                    <span class="icon">🏪</span>
                    <span>Crear Almacén</span>
                </a>
                <a href="crud_historial_almacen.php" class="nav-item">
                    <span class="icon">📋</span>
                    <span>Control de Almacén</span>
                </a>
            </div>
        </div>

        <!-- Asignaciones -->
        <div class="nav-category">
            <div class="category-header" data-category="asignaciones">
                <span class="icon">📝</span>
                <span>Asignaciones</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="asignaciones">
                <a href="crud_asignacion.php" class="nav-item">
                    <span class="icon">👉</span>
                    <span>Asignaciones Activos</span>
                </a>
                <a href="crud_asignacionPeriferico.php" class="nav-item">
                    <span class="icon">🖱</span>
                    <span>Asignaciones Periféricos</span>
                </a>
            </div>
        </div>

        <!-- Reparaciones -->
        <div class="nav-category">
            <div class="category-header" data-category="reparaciones">
                <span class="icon">🔧</span>
                <span>Reparaciones</span>
                <span class="arrow">▼</span>
            </div>
            <div class="category-items" id="reparaciones">
                <a href="crud_reparacion.php" class="nav-item">
                    <span class="icon">🔧</span>
                    <span>Reparaciones Realizadas</span>
                </a>
            </div>
        </div>

        <!-- Historial de Asignaciones -->
        <div class="nav-category">
            <div class="category-header" data-category="historial">
                <span class="icon">📄</span>
                <span>Historial de Asignaciones</span>
                <span class="arrow">▼</span>
            </div>
            
            <div class="category-items" id="historial">
                <a href="crud_historial_asignacion.php" class="nav-item">
                    <span class="icon">📄</span>
                    <span>Historial de Asignaciones</span>
                </a>
            </div>
        </div>

        <!-- Gráficos Echarts -->
        <div class="nav-category">
            <div class="category-header" data-category="graficos">
                <span class="icon">📊</span>
                <span>Gráficos</span>
                <span class="arrow">▼</span>
            </div>
            
            <div class="category-items" id="graficos">
                <a href="dashboard_principal.php" class="nav-item">
                    <span class="icon">📊</span>
                    <span>Gráficos Principales</span>
                </a>
            </div>
        </div>
    </nav>
</aside>

<!-- Overlay para móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>