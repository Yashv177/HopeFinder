/**
 * AI Matches Display Component
 * Handles fetching and displaying AI matched data in the dashboard
 */

class AIMatchesManager {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.allMatches = [];
        this.init();
    }

    /**
     * Initialize the AI Matches manager
     */
    init() {
        this.setupEventListeners();
        this.loadAIMatches();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Add any event listeners for AI matches here
        // Example: filter buttons, sort buttons, etc.
    }

    /**
     * Load AI matches from API
     */
    async loadAIMatches() {
        try {
            const response = await fetch('api/get_ai_matches.php?limit=100');
            const result = await response.json();

            if (result.success) {
                this.allMatches = result.data || [];
                this.displayAIMatches(this.allMatches);
            } else {
                this.displayError('Failed to load AI matches');
            }
        } catch (error) {
            console.error('Error loading AI matches:', error);
            this.displayError('Error loading AI matches');
        }
    }

    /**
     * Display AI matches in table
     */
    displayAIMatches(matches) {
        const table = document.getElementById('aiMatchesTable');
        
        if (!table) return;

        if (matches.length === 0) {
            table.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox"></i> No AI matches found
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        matches.forEach(match => {
            const statusBadge = this.getStatusBadge(match.status);
            const date = new Date(match.match_date).toLocaleDateString();
            
            html += `
                <tr>
                    <td><strong>#${match.match_id}</strong></td>
                    <td>Report #${match.report_id}</td>
                    <td>Found #${match.found_id}</td>
                    <td>
                        <div class="progress" style="height: 20px; width: 100px;">
                            <div class="progress-bar" role="progressbar" style="width: ${match.match_percent}%;" 
                                 aria-valuenow="${match.match_percent}" aria-valuemin="0" aria-valuemax="100">
                                ${match.match_percent}%
                            </div>
                        </div>
                    </td>
                    <td>${statusBadge}</td>
                    <td><small class="text-muted">${date}</small></td>
                </tr>
            `;
        });

        table.innerHTML = html;
    }

    /**
     * Get status badge HTML
     */
    getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning text-dark">Pending</span>',
            'approved': '<span class="badge bg-success">Approved</span>',
            'rejected': '<span class="badge bg-danger">Rejected</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Display error message
     */
    displayError(message) {
        const table = document.getElementById('aiMatchesTable');
        if (table) {
            table.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle"></i> ${message}
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Refresh AI matches
     */
    refresh() {
        this.loadAIMatches();
    }
}

// Initialize AI Matches Manager when document is ready
let aiMatchesManager;
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('aiMatchesTable')) {
        aiMatchesManager = new AIMatchesManager();
    }
});
