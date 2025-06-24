class NotificationSystem {
    constructor() {
        this.isDropdownOpen = false;
        this.checkInterval = 300000; // 5 minutes
        this.init();
    }
    
    init() {
        this.createNotificationBell();
        this.bindEvents();
        this.startPeriodicCheck();
        this.loadNotifications();
    }
    
    createNotificationBell() {
        const userInfo = document.querySelector('.user-info');
        if (userInfo) {
            const notificationBell = document.createElement('div');
            notificationBell.className = 'notification-bell';
            notificationBell.innerHTML = `
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h4>Notifikasi Stok</h4>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="no-notifications">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Memuat notifikasi...</p>
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="items.php">Lihat Semua Barang</a>
                    </div>
                </div>
            `;
            userInfo.insertBefore(notificationBell, userInfo.firstChild);
        }
    }
    
    bindEvents() {
        const notificationBell = document.querySelector('.notification-bell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationBell) {
            notificationBell.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            if (this.isDropdownOpen) {
                this.closeDropdown();
            }
        });
        
        if (notificationDropdown) {
            notificationDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }
    
    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (this.isDropdownOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.classList.add('show');
        this.isDropdownOpen = true;
        this.loadNotifications();
    }
    
    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.classList.remove('show');
        this.isDropdownOpen = false;
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php');
            const data = await response.json();
            this.updateNotificationBadge(data.low_stock_count + data.out_of_stock_count);
            this.renderNotifications(data);
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.renderErrorState();
        }
    }
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    renderNotifications(data) {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;
        
        let html = '';
        
        // Out of stock items (priority)
        data.out_of_stock_items.forEach(item => {
            html += this.createNotificationItem(
                item,
                'danger',
                'fas fa-exclamation-circle',
                'Stok Habis',
                `${item.item_name} telah habis`,
                '0 ' + item.unit
            );
        });
        
        // Low stock items
        data.low_stock_items.forEach(item => {
            if (item.quantity > 0) { // Exclude out of stock items
                html += this.createNotificationItem(
                    item,
                    'warning',
                    'fas fa-exclamation-triangle',
                    'Stok Menipis',
                    `${item.item_name} stok menipis`,
                    `${item.quantity} ${item.unit} tersisa`
                );
            }
        });
        
        if (html === '') {
            html = `
                <div class="no-notifications">
                    <i class="fas fa-check-circle"></i>
                    <p>Semua stok aman</p>
                </div>
            `;
        }
        
        notificationList.innerHTML = html;
    }
    
    createNotificationItem(item, type, icon, title, description, stockInfo) {
        return `
            <div class="notification-item">
                <div class="notification-icon-wrapper">
                    <div class="notification-icon-item ${type}">
                        <i class="${icon}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${title}</div>
                        <div class="notification-desc">${description}</div>
                        <span class="notification-stock ${type}">${stockInfo}</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderErrorState() {
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            notificationList.innerHTML = `
                <div class="no-notifications">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Gagal memuat notifikasi</p>
                </div>
            `;
        }
    }
    
    showAlert(title, message, type = 'warning') {
        const alert = document.createElement('div');
        alert.className = `notification-alert ${type}`;
        alert.innerHTML = `
            <div class="notification-alert-header">
                <div class="notification-alert-icon ${type}">
                    <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
                </div>
                <div class="notification-alert-title">${title}</div>
                <button class="notification-alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
            <div class="notification-alert-body">${message}</div>
        `;
        
        document.body.appendChild(alert);
        
        // Show with animation
        setTimeout(() => alert.classList.add('show'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }
    
    startPeriodicCheck() {
        setInterval(() => {
            this.loadNotifications();
        }, this.checkInterval);
    }
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.user-info')) {
        window.notificationSystem = new NotificationSystem();
    }
});
