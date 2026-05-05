// Real-time Notification System
class NotificationSystem {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.sound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq+4Qi1fUtM1cG6pk6Y0l8G4pk1Z8RFR6...'); // Short beep
        this.init();
    }

    init() {
        this.loadNotifications();
        setInterval(() => this.loadNotifications(), 5000); // Poll every 5s
        
        // Event listeners
        document.getElementById('markAllReadBtn')?.addEventListener('click', () => this.markAllRead());
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-item')) {
                this.markRead(e.target.closest('.notification-item').dataset.id);
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=list'
            });
            
            const data = await response.json();
            
            if (data.unread_count !== this.unreadCount) {
                this.unreadCount = data.unread_count;
                this.updateBadge();
                if (data.unread_count > this.unreadCount) {
                    this.playSound();
                }
            }
            
            this.notifications = data.notifications;
            this.renderNotifications();
            
        } catch (error) {
            console.error('Notification load error:', error);
        }
    }

    updateBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'inline' : 'none';
        }
    }

    renderNotifications() {
        const container = document.getElementById('notificationsDropdown');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<div class="dropdown-item text-center text-muted p-3">No new notifications</div>';
            return;
        }

        container.innerHTML = this.notifications.map(notif => `
            <a href="${notif.link || '#'}" class="dropdown-item notification-item p-3 border-bottom" data-id="${notif.notif_id}">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-3">
                        <span class="badge bg-${notif.priority_color} rounded-pill fs-6 px-2 py-1">${notif.priority.toUpperCase()}</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold mb-1">${notif.message}</div>
                        <small class="text-muted">${new Date(notif.created_at).toLocaleString()}</small>
                    </div>
                </div>
            </a>
        `).join('');
    }

    async markRead(notifId) {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST', 
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&notif_id=${notifId}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications(); // Refresh
            }
        } catch (error) {
            console.error('Mark read error:', error);
        }
    }

    async markAllRead() {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            });
            
            await response.json();
            this.loadNotifications();
        } catch (error) {
            console.error('Mark all read error:', error);
        }
    }

    playSound() {
        this.sound.currentTime = 0;
        this.sound.play().catch(() => {}); // Ignore audio policy errors
    }
}

// Initialize globally
let notificationSystem;
document.addEventListener('DOMContentLoaded', () => {
    notificationSystem = new NotificationSystem();
});

