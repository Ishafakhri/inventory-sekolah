document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-open');
        });
    }

    // Auto-open sidebar on desktop
    if (window.innerWidth > 768) {
        sidebar.classList.add('active');
        mainContent.classList.add('sidebar-open');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
                mainContent.classList.remove('sidebar-open');
            }
        }
    });

    // Animate stats cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
});

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Add animation
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'translateY(-30px) scale(0.95)';
            
            setTimeout(() => {
                modalContent.style.transition = 'all 0.3s ease';
                modalContent.style.opacity = '1';
                modalContent.style.transform = 'translateY(0) scale(1)';
            }, 10);
        }
        
        // Focus first input
        setTimeout(() => {
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        }, 300);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transition = 'all 0.3s ease';
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'translateY(-30px) scale(0.95)';
        }
        
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        const modalId = e.target.id;
        if (modalId) {
            closeModal(modalId);
        }
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="flex"]');
        openModals.forEach(modal => {
            if (modal.id) {
                closeModal(modal.id);
            }
        });
    }
});

// Prevent modal content clicks from closing modal
document.addEventListener('click', function(e) {
    if (e.target.closest('.modal-content')) {
        e.stopPropagation();
    }
});

// Enhanced scroll behavior for modals
function initModalScrollBehavior() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            // Smooth scrolling
            modalContent.style.scrollBehavior = 'smooth';
            
            // Add scroll indicators if content is scrollable
            modalContent.addEventListener('scroll', function() {
                const header = this.querySelector('.modal-header');
                const footer = this.querySelector('.modal-footer');
                
                if (this.scrollTop > 0) {
                    header?.classList.add('scrolled');
                } else {
                    header?.classList.remove('scrolled');
                }
                
                if (this.scrollTop + this.clientHeight >= this.scrollHeight - 5) {
                    footer?.classList.add('at-bottom');
                } else {
                    footer?.classList.remove('at-bottom');
                }
            });
        }
    });
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    initModalScrollBehavior();
    
    // Sidebar toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-open');
        });
    }

    // Auto-open sidebar on desktop
    if (window.innerWidth > 768) {
        sidebar.classList.add('active');
        mainContent.classList.add('sidebar-open');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
                mainContent.classList.remove('sidebar-open');
            }
        }
    });

    // Animate stats cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
});

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Search functionality
function setupSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let match = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
                
                row.style.display = match ? '' : 'none';
            }
        });
    }
}
