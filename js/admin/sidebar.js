// JavaScript para funcionalidad del sidebar
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Detectar si estamos en vista_admin.php o en páginas CRUD
    const currentPage = window.location.pathname;
    const isAdminDashboard = currentPage.includes('vista_admin.php');
    
    // Configuración inicial del sidebar según la página (SIN ANIMACIÓN)
    if (!isAdminDashboard) {
        // Desactivar transiciones temporalmente para evitar animación al cargar
        sidebar.style.transition = 'none';
        mainContent.style.transition = 'none';
        
        // En páginas CRUD: sidebar cerrado por defecto para no interferir con tablas
        sidebar.classList.add('sidebar-collapsed');
        mainContent.classList.add('main-expanded');
        
        // Reactivar transiciones después de un pequeño delay
        setTimeout(function() {
            sidebar.style.transition = '';
            mainContent.style.transition = '';
        }, 50);
    }
    // En vista_admin.php: sidebar abierto por defecto (comportamiento normal)

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('main-expanded');
            
            // Para móviles
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('sidebar-mobile-open');
                sidebarOverlay.classList.toggle('overlay-active');
            }
        });
    }

    // Toggle categories (acordeón)
    document.querySelectorAll('.category-header').forEach(header => {
        header.addEventListener('click', function() {
            const categoryId = this.dataset.category;
            const categoryItems = document.getElementById(categoryId);
            const arrow = this.querySelector('.arrow');
            
            if (categoryItems && arrow) {
                categoryItems.classList.toggle('category-expanded');
                arrow.classList.toggle('arrow-rotated');
            }
        });
    });

    // Sidebar overlay for mobile (cerrar al hacer clic en overlay)
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('sidebar-mobile-open');
            this.classList.remove('overlay-active');
        });
    }

    // Cerrar sidebar en móvil al hacer clic en un enlace
    document.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('sidebar-mobile-open');
                sidebarOverlay.classList.remove('overlay-active');
            }
        });
    });

    // Responsive: ajustar sidebar al cambiar tamaño de ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // En pantallas grandes, quitar clases de móvil
            sidebar.classList.remove('sidebar-mobile-open');
            sidebarOverlay.classList.remove('overlay-active');
        }
    });
});