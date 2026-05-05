/* ===========================================
   SIDEBAR TOGGLE - CLEAN JS IMPLEMENTATION
   Professional Police Dashboard Theme
   Uses capture-phase to override existing listeners
   =========================================== */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    function initSidebarToggle() {
        const toggleBtn = document.getElementById('sidebarCollapse');
        const body = document.body;
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        
        // Safety check - exit if elements don't exist
        if (!toggleBtn || !sidebar || !content) {
            console.warn('Sidebar toggle elements not found');
            return;
        }
        
        // Get breakpoint for mobile detection
        const BREAKPOINT = 991;
        
        // Clean up any inline styles from other scripts
        sidebar.style.cssText = '';
        content.style.cssText = '';
        
        /**
         * Toggle sidebar state
         * On mobile: opens/closes as overlay
         * On desktop: collapses/expands sidebar
         */
        function toggleSidebar(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const isMobile = window.innerWidth <= BREAKPOINT;
            
            if (isMobile) {
                // Mobile: Toggle overlay mode
                toggleMobileSidebar();
            } else {
                // Desktop: Toggle collapsed mode
                toggleDesktopSidebar();
            }
        }
        
        /**
         * Desktop sidebar collapse/expand
         */
        function toggleDesktopSidebar() {
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            
            if (isCollapsed) {
                // Expand sidebar
                body.classList.remove('sidebar-collapsed');
                toggleBtn.setAttribute('aria-expanded', 'true');
            } else {
                // Collapse sidebar
                body.classList.add('sidebar-collapsed');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        }
        
        /**
         * Mobile sidebar overlay toggle
         */
        function toggleMobileSidebar() {
            const isOpen = body.classList.contains('sidebar-mobile');
            
            if (isOpen) {
                // Close sidebar
                body.classList.remove('sidebar-mobile');
                toggleBtn.setAttribute('aria-expanded', 'false');
            } else {
                // Open sidebar
                body.classList.add('sidebar-mobile');
                toggleBtn.setAttribute('aria-expanded', 'true');
            }
        }
        
        /**
         * Handle window resize
         * Reset sidebar state based on screen size
         */
        function handleResize() {
            const isMobile = window.innerWidth <= BREAKPOINT;
            
            if (isMobile) {
                // On mobile, ensure collapsed state is removed
                body.classList.remove('sidebar-collapsed');
                body.classList.remove('sidebar-mobile');
            } else {
                // On desktop, ensure mobile state is removed
                body.classList.remove('sidebar-mobile');
            }
        }
        
        /**
         * Close mobile sidebar when clicking outside
         */
        function handleOutsideClick(event) {
            const isMobile = window.innerWidth <= BREAKPOINT;
            
            if (isMobile && body.classList.contains('sidebar-mobile')) {
                // Check if click is outside sidebar and not on toggle button
                const isClickOnSidebar = sidebar.contains(event.target);
                const isClickOnToggle = toggleBtn.contains(event.target);
                
                if (!isClickOnSidebar && !isClickOnToggle) {
                    body.classList.remove('sidebar-mobile');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                }
            }
        }
        
        // Remove any existing event listeners by cloning the button
        const newToggleBtn = toggleBtn.cloneNode(true);
        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
        
        // Use capture-phase event listener (third parameter = true)
        // This ensures our handler runs BEFORE any bubble-phase handlers
        newToggleBtn.addEventListener('click', toggleSidebar, { capture: true, passive: false });
        
        // Window resize - use capture phase
        window.addEventListener('resize', debounce(handleResize, 150), { capture: true });
        
        // Click outside to close (mobile) - use capture phase
        document.addEventListener('click', handleOutsideClick, { capture: true });
        
        // Close mobile sidebar on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && body.classList.contains('sidebar-mobile')) {
                body.classList.remove('sidebar-mobile');
                newToggleBtn.setAttribute('aria-expanded', 'false');
            }
        }, { capture: true });
        
        // Initialize ARIA attribute
        newToggleBtn.setAttribute('aria-expanded', 'true');
        newToggleBtn.setAttribute('aria-controls', 'sidebar');
        
        // Store reference for API functions
        window._sidebarToggleBtn = newToggleBtn;
    }
    
    /**
     * Utility: Debounce function
     * Prevents rapid-fire function calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarToggle);
    } else {
        // DOM already loaded
        initSidebarToggle();
    }
    
    // Also try to init immediately in case DOMContentLoaded already fired
    setTimeout(initSidebarToggle, 0);
    
    /**
     * API Functions
     */
    window.collapseSidebar = function() {
        const body = document.body;
        const toggleBtn = window._sidebarToggleBtn || document.getElementById('sidebarCollapse');
        
        if (window.innerWidth <= 991) {
            body.classList.add('sidebar-mobile');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
        
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
    };
    
    window.expandSidebar = function() {
        const body = document.body;
        const toggleBtn = window._sidebarToggleBtn || document.getElementById('sidebarCollapse');
        
        if (window.innerWidth <= 991) {
            body.classList.remove('sidebar-mobile');
        } else {
            body.classList.add('sidebar-collapsed');
        }
        
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
    };
    
    window.toggleSidebarAPI = function() {
        const body = document.body;
        const toggleBtn = window._sidebarToggleBtn || document.getElementById('sidebarCollapse');
        const isMobileView = window.innerWidth <= 991;
        const isCollapsed = body.classList.contains('sidebar-collapsed');
        const isMobileOpen = body.classList.contains('sidebar-mobile');
        
        if (isMobileView) {
            if (isMobileOpen) {
                body.classList.remove('sidebar-mobile');
            } else {
                body.classList.add('sidebar-mobile');
            }
        } else {
            if (isCollapsed) {
                body.classList.remove('sidebar-collapsed');
            } else {
                body.classList.add('sidebar-collapsed');
            }
        }
        
        if (toggleBtn) {
            const isOpen = isMobileView ? !isMobileOpen : !isCollapsed;
            toggleBtn.setAttribute('aria-expanded', isOpen.toString());
        }
    };
    
    window.isMobileView = function() {
        return window.innerWidth <= 991;
    };
    
    window.isSidebarCollapsed = function() {
        return document.body.classList.contains('sidebar-collapsed');
    };
    
    window.isMobileSidebarOpen = function() {
        return document.body.classList.contains('sidebar-mobile');
    };
    
})();

