/**
 * Police Dashboard Cards
 * Fetches stats from get_police_dashboard_stats.php
 * Updates existing card values with real database data
 */

(function() {
    'use strict';

    const API_URL = 'php/get_police_dashboard_stats.php';
    const CARD_IDS = {
        totalReports: 'total-reports',
        pendingVerification: 'pending-verification',
        verifiedCases: 'verified-cases',
        matchesFound: 'matches-found'
    };

    /**
     * Show loading state on stat cards
     */
    function showLoading() {
        Object.values(CARD_IDS).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.dataset.original = el.textContent;
                el.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }
        });
    }

    /**
     * Hide loading, restore or animate value
     */
    function hideLoading() {
        Object.values(CARD_IDS).forEach(id => {
            const el = document.getElementById(id);
            if (el && el.dataset.original) {
                el.textContent = el.dataset.original;
                delete el.dataset.original;
            }
        });
    }

    /**
     * Animate counter from 0 to target
     */
    function animateCounter(elementId, target, duration) {
        const el = document.getElementById(elementId);
        if (!el) return;

        const start = 0;
        const startTime = performance.now();

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (target - start) * ease);
            el.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }

        requestAnimationFrame(step);
    }

    /**
     * Fetch dashboard stats and update cards
     */
    async function loadCards() {
        showLoading();

        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Network error');

            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'API error');

            const stats = data.stats;

            animateCounter(CARD_IDS.totalReports, stats.total_reports || 0, 1200);
            animateCounter(CARD_IDS.pendingVerification, stats.pending_cases || 0, 1200);
            animateCounter(CARD_IDS.verifiedCases, stats.verified_cases || 0, 1200);
            animateCounter(CARD_IDS.matchesFound, stats.found_cases || 0, 1200);

            // Update count labels under status chart
            const pendingCount = document.getElementById('pending-count');
            const verifiedCount = document.getElementById('verified-count');
            const matchedCount = document.getElementById('matched-count');

            if (pendingCount) pendingCount.textContent = stats.pending_cases || 0;
            if (verifiedCount) verifiedCount.textContent = stats.verified_cases || 0;
            if (matchedCount) matchedCount.textContent = stats.found_cases || 0;

        } catch (error) {
            hideLoading();
            // Silently fail — keep static fallback values
        }
    }

    // Load on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadCards);
    } else {
        loadCards();
    }

    // Expose for manual refresh
    window.refreshDashboardCards = loadCards;
})();
