// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    
    if (menuToggle && sidebar && mainContent) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('expanded');
            
            // Store sidebar state in localStorage
            const isMinimized = sidebar.classList.contains('minimized');
            localStorage.setItem('sidebarMinimized', isMinimized);
        });
        
        // Restore sidebar state from localStorage
        const sidebarMinimized = localStorage.getItem('sidebarMinimized');
        if (sidebarMinimized === 'true') {
            sidebar.classList.add('minimized');
            mainContent.classList.add('expanded');
        }
    }
});
