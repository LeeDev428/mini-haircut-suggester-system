// ===== DASHBOARD JAVASCRIPT =====

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dashboard components
    initializeSidebar();
    initializeStats();
    initializeNotifications();
    initializeCharts();
    initializeTooltips();
    initializeUserManagement();
    initializeQuickActions();
    initializeSearch();
    
    // Auto-refresh data every 30 seconds for admin dashboard
    if (document.querySelector('.admin-layout')) {
        setInterval(refreshAdminData, 30000);
    }
});

// ===== SIDEBAR FUNCTIONALITY =====
function initializeSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar, .admin-sidebar');
    const mainContent = document.querySelector('.main-content, .admin-main');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Add overlay for mobile
            if (window.innerWidth <= 768) {
                toggleSidebarOverlay();
            }
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                removeSidebarOverlay();
            }
        }
    });
    
    // Handle menu item active states
    const menuItems = document.querySelectorAll('.menu-item a');
    const currentPath = window.location.pathname;
    
    menuItems.forEach(item => {
        if (item.getAttribute('href') === currentPath || 
            item.getAttribute('href') === currentPath.split('/').pop()) {
            item.parentElement.classList.add('active');
        }
        
        item.addEventListener('click', function() {
            menuItems.forEach(mi => mi.parentElement.classList.remove('active'));
            this.parentElement.classList.add('active');
        });
    });
}

function toggleSidebarOverlay() {
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(overlay);
        
        setTimeout(() => overlay.style.opacity = '1', 10);
        
        overlay.addEventListener('click', function() {
            document.querySelector('.sidebar, .admin-sidebar').classList.remove('active');
            removeSidebarOverlay();
        });
    }
}

function removeSidebarOverlay() {
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 300);
    }
}

// ===== STATS ANIMATION =====
function initializeStats() {
    const statCards = document.querySelectorAll('.stat-card, .admin-stat-card');
    
    statCards.forEach(card => {
        const statValue = card.querySelector('h3');
        if (statValue) {
            animateNumber(statValue);
        }
    });
    
    // Animate progress bars
    const chartFills = document.querySelectorAll('.chart-fill');
    chartFills.forEach(fill => {
        const width = fill.getAttribute('data-width') || '0%';
        setTimeout(() => {
            fill.style.width = width;
        }, 500);
    });
}

function animateNumber(element) {
    const target = parseInt(element.textContent.replace(/[^\d]/g, ''));
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            element.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString();
        }
    }, 16);
}

// ===== NOTIFICATIONS =====
function initializeNotifications() {
    // Check for new notifications every minute
    setInterval(checkNotifications, 60000);
}

function checkNotifications() {
    // Simulate notification check
    const badges = document.querySelectorAll('.menu-badge');
    badges.forEach(badge => {
        const currentCount = parseInt(badge.textContent) || 0;
        if (Math.random() > 0.8) { // 20% chance of new notification
            badge.textContent = currentCount + 1;
            badge.style.display = 'block';
        }
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===== CHARTS =====
function initializeCharts() {
    const chartItems = document.querySelectorAll('.chart-item');
    
    chartItems.forEach(item => {
        const fill = item.querySelector('.chart-fill');
        const percentage = parseInt(item.getAttribute('data-percentage')) || 0;
        
        if (fill) {
            setTimeout(() => {
                fill.style.width = percentage + '%';
            }, 500);
        }
    });
}

// ===== TOOLTIPS =====
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    setTimeout(() => tooltip.style.opacity = '1', 10);
    
    e.target._tooltip = tooltip;
}

function hideTooltip(e) {
    if (e.target._tooltip) {
        e.target._tooltip.style.opacity = '0';
        setTimeout(() => {
            if (e.target._tooltip) {
                e.target._tooltip.remove();
                delete e.target._tooltip;
            }
        }, 200);
    }
}

// ===== USER MANAGEMENT (Admin) =====
function initializeUserManagement() {
    const userActions = document.querySelectorAll('.user-actions .btn');
    
    userActions.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            const userId = this.getAttribute('data-user-id');
            
            switch(action) {
                case 'view':
                    viewUser(userId);
                    break;
                case 'edit':
                    editUser(userId);
                    break;
                case 'delete':
                    deleteUser(userId);
                    break;
                case 'suspend':
                    suspendUser(userId);
                    break;
            }
        });
    });
}

function viewUser(userId) {
    // Simulate user view
    showNotification(`Viewing user ${userId}`, 'info');
}

function editUser(userId) {
    // Simulate user edit
    showNotification(`Editing user ${userId}`, 'info');
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        // Simulate user deletion
        showNotification(`User ${userId} deleted`, 'success');
        
        // Remove user from list
        const userItem = document.querySelector(`[data-user-id="${userId}"]`).closest('.user-item');
        if (userItem) {
            userItem.style.opacity = '0';
            setTimeout(() => userItem.remove(), 300);
        }
    }
}

function suspendUser(userId) {
    if (confirm('Are you sure you want to suspend this user?')) {
        // Simulate user suspension
        showNotification(`User ${userId} suspended`, 'success');
    }
}

// ===== QUICK ACTIONS =====
function initializeQuickActions() {
    const actionButtons = document.querySelectorAll('.action-btn, .quick-action-item');
    
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.style.pointerEvents = 'none';
            
            // Simulate action
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
                
                const action = this.getAttribute('data-action');
                if (action) {
                    showNotification(`${action} completed!`, 'success');
                }
            }, 1000);
        });
    });
}

// ===== SEARCH FUNCTIONALITY =====
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const searchableItems = document.querySelectorAll('.searchable');
            
            searchableItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

// ===== REFRESH ADMIN DATA =====
function refreshAdminData() {
    // Simulate data refresh for admin dashboard
    const statValues = document.querySelectorAll('.admin-stat-card h3');
    
    statValues.forEach(stat => {
        const currentValue = parseInt(stat.textContent.replace(/[^\d]/g, ''));
        const change = Math.floor(Math.random() * 10) - 5; // Random change -5 to +5
        const newValue = Math.max(0, currentValue + change);
        
        if (change !== 0) {
            stat.textContent = newValue.toLocaleString();
            
            // Update trend indicators
            const trendElement = stat.closest('.admin-stat-card').querySelector('.stat-trend');
            if (trendElement) {
                trendElement.className = 'stat-trend ' + (change > 0 ? 'up' : change < 0 ? 'down' : 'stable');
                trendElement.innerHTML = `<i class="fas fa-arrow-${change > 0 ? 'up' : change < 0 ? 'down' : 'right'}"></i> ${Math.abs(change)}`;
            }
        }
    });
}

// ===== STYLE INTERACTIONS =====
function initializeStyleInteractions() {
    const styleItems = document.querySelectorAll('.style-item');
    
    styleItems.forEach(item => {
        item.addEventListener('click', function() {
            const styleId = this.getAttribute('data-style-id');
            const styleName = this.querySelector('h4').textContent;
            
            // Show style details
            showStyleModal(styleId, styleName);
        });
    });
}

function showStyleModal(styleId, styleName) {
    const modal = document.createElement('div');
    modal.className = 'style-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        border-radius: 15px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    `;
    
    modalContent.innerHTML = `
        <h2>${styleName}</h2>
        <p>Style details and recommendations would go here...</p>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-primary" onclick="this.closest('.style-modal').remove()">Save Style</button>
            <button class="btn btn-outline" onclick="this.closest('.style-modal').remove()">Close</button>
        </div>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    setTimeout(() => {
        modal.style.opacity = '1';
        modalContent.style.transform = 'scale(1)';
    }, 10);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.opacity = '0';
            modalContent.style.transform = 'scale(0.9)';
            setTimeout(() => modal.remove(), 300);
        }
    });
}

// ===== UTILITY FUNCTIONS =====
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return date.toLocaleDateString();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== EXPORT FUNCTIONS =====
window.DashboardJS = {
    showNotification,
    showStyleModal,
    refreshAdminData,
    formatNumber,
    formatDate
};
