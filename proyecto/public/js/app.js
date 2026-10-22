/**
 * JAVASCRIPT PRINCIPAL E INTERACTIVO - AULAVIRTUAL SALLE
 * Instituto La Salle Ciénaga Magdalena
 */

document.addEventListener('DOMContentLoaded', () => {
    initThemeManager();
    initSidebarToggle();
    initSidebarNavigation();
    initTableSearch();
});

/**
 * Gestor de Modo Claro / Modo Oscuro con LocalStorage
 */
function initThemeManager() {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const savedTheme = localStorage.getItem('salle_theme') || 'light';
    
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('salle_theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }
}

function updateThemeIcon(theme) {
    const icon = document.querySelector('#themeToggleBtn i');
    if (icon) {
        if (theme === 'dark') {
            icon.className = 'fas fa-sun text-warning';
        } else {
            icon.className = 'fas fa-moon text-secondary';
        }
    }
}

/**
 * Responsive Mobile Sidebar Toggle
 */
function initSidebarToggle() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.app-sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
}

/**
 * Navegación Interactiva por Pestañas/Secciones desde el Sidebar
 */
function initSidebarNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a[href^="#"]');
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href').substring(1);
            if (!targetId) return;

            const targetElement = document.getElementById(targetId);
            
            // Actualizar clase activa en el sidebar
            sidebarLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Si existen secciones de clase .tab-section, alternar visibilidad
            const tabSections = document.querySelectorAll('.tab-section');
            if (tabSections.length > 0) {
                let foundTarget = false;
                tabSections.forEach(section => {
                    if (section.id === targetId) {
                        section.style.display = 'block';
                        section.classList.add('active-tab');
                        foundTarget = true;
                    } else {
                        section.style.display = 'none';
                        section.classList.remove('active-tab');
                    }
                });

                if (foundTarget) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
            }

            // Si no se usó tab-section, hacer scroll suave al elemento
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

/**
 * Filtro de Búsqueda en Tiempo Real para Tablas
 */
function initTableSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const tableId = input.getAttribute('data-table-search');
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('keyup', () => {
            const term = input.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });
}

/**
 * Función de utilidad para notificaciones Toast / Alertas interactivas
 */
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 show mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body font-heading">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(toastEl);
    setTimeout(() => toastEl.remove(), 4000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1090';
    document.body.appendChild(container);
    return container;
}
