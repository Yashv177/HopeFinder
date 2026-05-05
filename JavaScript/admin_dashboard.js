/**
 * Admin Dashboard Frontend Integration
 * Real-time notifications, activity logs, profile management
 * 
 * Handles:
 * - Real-time notification polling
 * - Activity log display
 * - Profile management
 * - Password change
 * - Logout functionality
 */

class AdminDashboard {
    constructor() {
        this.notificationPollInterval = 5000; // 5 seconds
        this.activityPollInterval = 10000; // 10 seconds
        this.notificationPollerId = null;
        this.activityPollerId = null;
        this.init();
    }

    /**
     * Initialize the dashboard
     */
    init() {
        this.setupEventListeners();
        this.startNotificationPolling();
        this.startActivityPolling();
        this.loadProfileData();
        
        // Log dashboard access
        this.logActivity('dashboard_accessed', 'Admin Dashboard Loaded');
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Notification badge click
        const notifBadge = document.getElementById('notificationBadge');
        if (notifBadge) {
            notifBadge.addEventListener('click', () => this.openNotifications());
        }

        // Mark all as read button
        const markAllBtn = document.getElementById('markAllNotificationsBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => this.markAllNotificationsAsRead());
        }

        // Profile update form
        const profileForm = document.getElementById('profileUpdateForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileUpdate(e));
        }

        // Password change form
        const passwordForm = document.getElementById('passwordChangeForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => this.handlePasswordChange(e));
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
    }

    /**
     * Start real-time notification polling
     */
    startNotificationPolling() {
        // Initial load
        this.fetchNotifications();
        
        // Poll every N seconds
        this.notificationPollerId = setInterval(() => {
            this.fetchNotifications();
        }, this.notificationPollInterval);
    }

    /**
     * Start activity log polling
     */
    startActivityPolling() {
        // Initial load
        this.fetchActivityLogs();
        
        // Poll every N seconds
        this.activityPollerId = setInterval(() => {
            this.fetchActivityLogs();
        }, this.activityPollInterval);
    }

    /**
     * Fetch notifications from API
     */
    async fetchNotifications() {
        try {
            const response = await fetch('api/get_notifications.php?limit=20');
            const result = await response.json();

            if (result.success) {
                this.updateNotificationUI(result.data);
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    /**
     * Update notification UI
     */
    updateNotificationUI(notifications) {
        const notificationsContainer = document.getElementById('notificationsContainer');
        
        if (!notificationsContainer) return;

        if (notifications.length === 0) {
            notificationsContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 2rem; color: rgba(255,255,255,0.2);"></i>
                    <p class="text-muted mt-3">No notifications yet</p>
                </div>
            `;
            return;
        }

        let html = '';
        notifications.forEach(notif => {
            const icon = this.getNotificationIcon(notif.type);
            const badge = notif.status === 'unread' ? 
                '<span class="badge bg-primary ms-2">New</span>' : '';
            const unreadClass = notif.status === 'unread' ? 'unread' : '';
            
            html += `
                <div class="notification-item ${unreadClass}" data-notif-id="${notif.notif_id}">
                    <div class="d-flex align-items-start">
                        <div class="notif-icon">${icon}</div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1">${escapeHtml(notif.message)}</p>
                                    <small class="text-muted">${formatDate(notif.created_at)}</small>
                                </div>
                                ${badge}
                            </div>
                            ${notif.link ? `<a href="${escapeHtml(notif.link)}" class="btn btn-sm btn-outline-primary mt-2">View</a>` : ''}
                        </div>
                        ${notif.status === 'unread' ? `
                            <button class="btn btn-sm btn-ghost ms-2" onclick="adminDashboard.markNotificationAsRead(${notif.notif_id})">
                                <i class="bi bi-check2"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        });

        notificationsContainer.innerHTML = html;
    }

    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        const icons = {
            'registration': '<i class="bi bi-person-plus"></i>',
            'report': '<i class="bi bi-file-earmark"></i>',
            'match': '<i class="bi bi-hand-thumbs-up"></i>',
            'verification': '<i class="bi bi-shield-check"></i>',
            'found': '<i class="bi bi-check-circle"></i>',
            'status': '<i class="bi bi-info-circle"></i>',
            'alert': '<i class="bi bi-exclamation-triangle"></i>'
        };
        return icons[type] || '<i class="bi bi-bell"></i>';
    }

    /**
     * Update unread notification count
     */
    async updateUnreadCount() {
        try {
            const response = await fetch('api/get_unread_count.php');
            const result = await response.json();

            if (result.success) {
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    if (result.unread_count > 0) {
                        badge.textContent = result.unread_count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Error updating unread count:', error);
        }
    }

    /**
     * Mark single notification as read
     */
    async markNotificationAsRead(notifId) {
        try {
            const response = await fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ notif_id: notifId })
            });

            const result = await response.json();

            if (result.success) {
                const element = document.querySelector(`[data-notif-id="${notifId}"]`);
                if (element) {
                    element.classList.remove('unread');
                }
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllNotificationsAsRead() {
        try {
            const response = await fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ mark_all: true })
            });

            const result = await response.json();

            if (result.success) {
                document.querySelectorAll('.notification-item.unread').forEach(el => {
                    el.classList.remove('unread');
                });
                this.updateUnreadCount();
                this.showToast('All notifications marked as read', 'success');
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    /**
     * Open notifications modal/panel
     */
    openNotifications() {
        const modal = document.getElementById('notificationsModal');
        if (modal) {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    }

    /**
     * Fetch activity logs from API
     */
    async fetchActivityLogs() {
        try {
            const response = await fetch('api/get_activity_logs.php?limit=20');
            const result = await response.json();

            if (result.success) {
                this.updateActivityLogsUI(result.data);
            }
        } catch (error) {
            console.error('Error fetching activity logs:', error);
        }
    }

    /**
     * Update activity logs UI
     */
    updateActivityLogsUI(logs) {
        const logsContainer = document.getElementById('activityLogsContainer');
        
        if (!logsContainer) return;

        if (logs.length === 0) {
            logsContainer.innerHTML = `
                <div class="text-center py-5">
                    <p class="text-muted">No activity logs</p>
                </div>
            `;
            return;
        }

        let html = '<table class="table table-sm table-dark">';
        html += '<thead><tr><th>Time</th><th>Action</th><th>IP Address</th></tr></thead>';
        html += '<tbody>';

        logs.forEach(log => {
            html += `
                <tr>
                    <td><small>${formatDate(log.created_at)}</small></td>
                    <td><small>${escapeHtml(log.action)}</small></td>
                    <td><small>${escapeHtml(log.ip_address)}</small></td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        logsContainer.innerHTML = html;
    }

    /**
     * Load user profile data
     */
    async loadProfileData() {
        try {
            const response = await fetch('api/get_profile.php');
            const result = await response.json();

            if (result.success) {
                this.populateProfileForm(result.data);
            }
        } catch (error) {
            console.error('Error loading profile data:', error);
        }
    }

    /**
     * Populate profile form with data
     */
    populateProfileForm(profile) {
        if (profile.fullname) {
            const fullnameInput = document.getElementById('profileFullname');
            if (fullnameInput) fullnameInput.value = profile.fullname;
        }

        if (profile.email) {
            const emailInput = document.getElementById('profileEmail');
            if (emailInput) {
                emailInput.value = profile.email;
                emailInput.disabled = true;
            }
        }

        if (profile.phone) {
            const phoneInput = document.getElementById('profilePhone');
            if (phoneInput) phoneInput.value = profile.phone;
        }

        if (profile.mobile_number) {
            const mobileInput = document.getElementById('profileMobile');
            if (mobileInput) mobileInput.value = profile.mobile_number;
        }

        if (profile.role) {
            const roleSpan = document.getElementById('profileRole');
            if (roleSpan) roleSpan.textContent = profile.role.toUpperCase();
        }
    }

    /**
     * Handle profile update
     */
    async handleProfileUpdate(e) {
        e.preventDefault();

        const fullname = document.getElementById('profileFullname')?.value;
        const phone = document.getElementById('profilePhone')?.value;
        const mobile = document.getElementById('profileMobile')?.value;

        if (!fullname) {
            this.showToast('Full name is required', 'error');
            return;
        }

        try {
            const response = await fetch('api/update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fullname: fullname,
                    phone: phone,
                    mobile_number: mobile
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showToast(result.message, 'success');
                this.logActivity('profile_updated', 'Profile information updated');
            } else {
                this.showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            this.showToast('Failed to update profile', 'error');
        }
    }

    /**
     * Handle password change
     */
    async handlePasswordChange(e) {
        e.preventDefault();

        const oldPassword = document.getElementById('oldPassword')?.value;
        const newPassword = document.getElementById('newPassword')?.value;
        const confirmPassword = document.getElementById('confirmPassword')?.value;

        if (!oldPassword || !newPassword || !confirmPassword) {
            this.showToast('All fields are required', 'error');
            return;
        }

        if (newPassword !== confirmPassword) {
            this.showToast('New passwords do not match', 'error');
            return;
        }

        if (newPassword.length < 8) {
            this.showToast('New password must be at least 8 characters', 'error');
            return;
        }

        try {
            const response = await fetch('api/change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showToast(result.message, 'success');
                document.getElementById('passwordChangeForm').reset();
                this.logActivity('password_changed', 'Password changed');
                
                // Close modal if exists
                const modal = document.getElementById('passwordModal');
                if (modal) {
                    bootstrap.Modal.getInstance(modal)?.hide();
                }
            } else {
                this.showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            this.showToast('Failed to change password', 'error');
        }
    }

    /**
     * Log activity
     */
    async logActivity(action, details = null) {
        try {
            await fetch('api/log_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    details: details
                })
            });
        } catch (error) {
            console.error('Error logging activity:', error);
        }
    }

    /**
     * Logout user
     */
    logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Create toast element
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'}" 
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        // Add to container
        const container = document.getElementById('toastContainer');
        if (container) {
            container.insertAdjacentHTML('beforeend', toastHtml);
            
            // Initialize and show toast
            const toastElement = container.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove after hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
    }

    /**
     * Destroy dashboard (cleanup)
     */
    destroy() {
        if (this.notificationPollerId) {
            clearInterval(this.notificationPollerId);
        }
        if (this.activityPollerId) {
            clearInterval(this.activityPollerId);
        }
    }
}

/**
 * Escape HTML entities
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) {
        return 'Just now';
    } else if (diffMins < 60) {
        return `${diffMins}m ago`;
    } else if (diffHours < 24) {
        return `${diffHours}h ago`;
    } else if (diffDays < 7) {
        return `${diffDays}d ago`;
    } else {
        return date.toLocaleDateString();
    }
}

// Initialize dashboard when DOM is ready
let adminDashboard;
document.addEventListener('DOMContentLoaded', () => {
    adminDashboard = new AdminDashboard();
});
