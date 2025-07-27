/**
 * Timeline Sparkline Calendar
 * Archive.org-style horizontal calendar with year sparklines
 */
class TimelineSparklineCalendar {
    constructor(selector, options = {}) {
        this.selector = selector;
        this.element = document.querySelector(selector);
        
        // Default options - spread options first, then apply defaults for missing properties
        this.options = {
            ...options,
            startYear: options.startYear ?? -2,
            endYear: options.endYear ?? 4,
            yearsPerView: options.yearsPerView ?? 7,
            showNavigation: options.showNavigation ?? true
        };
        
        console.log('SparklineCalendar constructor - Options:', this.options);
        console.log('SparklineCalendar constructor - Selector:', selector);
        console.log('SparklineCalendar constructor - Element found:', this.element);
        console.log('SparklineCalendar constructor - startYear:', this.options.startYear);
        console.log('SparklineCalendar constructor - endYear:', this.options.endYear);
        
        if (!this.element) {
            console.error('SparklineCalendar: Element not found:', selector);
            return;
        }
        
        this.data = {};
        this.globalMaxArticles = 0;
        this.currentYearRange = { 
            start: this.options.startYear, 
            end: this.options.endYear 
        };
        this.yearsPerView = this.options.yearsPerView;
        
        // Modal-related properties
        this.hoverTimeout = null;
        this.modalHoverTimeout = null;
        
        console.log('SparklineCalendar constructor - Initial year range:', this.currentYearRange);
        
        // Initialize
        this.init();
    }
    
    async init() {
        await this.loadData();
        this.render();
        this.bindEvents();
        this.createModal();
    }
    
    async loadData() {
        const url = new URL('/wp-admin/admin-ajax.php', window.location.origin);
        url.searchParams.set('action', 'timeline_sparkline_data');
        url.searchParams.set('start_year', this.currentYearRange.start);
        url.searchParams.set('end_year', this.currentYearRange.end);
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                this.data = data.data;
                
                // Calculate global maximum article count across all years and months
                this.globalMaxArticles = 0;
                Object.values(this.data).forEach(yearData => {
                    Object.values(yearData).forEach(monthCount => {
                        if (monthCount > this.globalMaxArticles) {
                            this.globalMaxArticles = monthCount;
                        }
                    });
                });
                
                console.log('SparklineCalendar: Global max articles:', this.globalMaxArticles);
                this.render();
            } else {
                console.error('SparklineCalendar: Failed to load data:', data);
            }
        } catch (error) {
            console.error('SparklineCalendar: Error loading data:', error);
        }
    }
    
    render() {
        this.element.innerHTML = '';
        
        // Create main sparkline container with side navigation
        const sparklineContainer = document.createElement('div');
        sparklineContainer.className = 'timeline-sparkline-container';
        
        // Create left navigation button
        if (this.options.showNavigation) {
            const leftNav = document.createElement('button');
            leftNav.className = 'timeline-sparkline-nav-left';
            leftNav.innerHTML = '←';
            leftNav.dataset.direction = 'prev';
            sparklineContainer.appendChild(leftNav);
        }
        
        // Create scrollable container
        const scrollContainer = document.createElement('div');
        scrollContainer.className = 'timeline-sparkline-scroll';
        
        // Create years container
        const yearsContainer = document.createElement('div');
        yearsContainer.className = 'timeline-sparkline-years';
        
        // Render each year in numerical order
        const sortedYears = Object.keys(this.data).sort((a, b) => parseInt(a) - parseInt(b));
        sortedYears.forEach(year => {
            const yearElement = this.createYearElement(year, this.data[year]);
            yearsContainer.appendChild(yearElement);
        });
        
        scrollContainer.appendChild(yearsContainer);
        sparklineContainer.appendChild(scrollContainer);
        
        // Create right navigation button
        if (this.options.showNavigation) {
            const rightNav = document.createElement('button');
            rightNav.className = 'timeline-sparkline-nav-right';
            rightNav.innerHTML = '→';
            rightNav.dataset.direction = 'next';
            sparklineContainer.appendChild(rightNav);
        }
        
        this.element.appendChild(sparklineContainer);
    }
    
    createYearElement(year, monthData) {
        const yearElement = document.createElement('div');
        yearElement.className = 'timeline-sparkline-year';
        yearElement.dataset.year = year;
        
        // Year label
        const yearLabel = document.createElement('div');
        yearLabel.className = 'timeline-sparkline-year-label';
        yearLabel.textContent = `Year ${year}`;
        yearElement.appendChild(yearLabel);
        
        // Sparkline (12 months)
        const sparkline = document.createElement('div');
        sparkline.className = 'timeline-sparkline-months';
        
        for (let month = 1; month <= 12; month++) {
            const monthElement = document.createElement('div');
            monthElement.className = 'timeline-sparkline-month';
            monthElement.dataset.month = month;
            monthElement.dataset.year = year;
            
            const articleCount = monthData[month] || 0;
            if (articleCount > 0) {
                monthElement.classList.add('has-articles');
                monthElement.classList.add('timeline-sparkline-interactive');
                monthElement.dataset.count = articleCount;
                monthElement.style.cursor = 'pointer';
                
                // Calculate dynamic height based on global maximum article count
                // Base height: 8px, Max height: 80px
                const minHeight = 8;
                const maxHeight = 80;
                const height = this.globalMaxArticles > 0 ? 
                    minHeight + ((articleCount / this.globalMaxArticles) * (maxHeight - minHeight)) : 
                    minHeight;
                
                monthElement.style.height = `${height}px`;
                
                // Add tooltip
                const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                   'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                monthElement.title = `${monthNames[month]} ${year}: ${articleCount} article${articleCount !== 1 ? 's' : ''}`;
            } else {
                // Set minimum height for months with no articles
                monthElement.style.height = '8px';
            }
            
            sparkline.appendChild(monthElement);
        }
        
        yearElement.appendChild(sparkline);
        return yearElement;
    }
    
    bindEvents() {
        // Navigation buttons
        this.element.addEventListener('click', (e) => {
            if (e.target.classList.contains('timeline-sparkline-nav-left') || 
                e.target.classList.contains('timeline-sparkline-nav-right')) {
                const direction = e.target.dataset.direction;
                this.navigate(direction);
            }
        });
        
        // Year clicks
        this.element.addEventListener('click', (e) => {
            if (e.target.classList.contains('timeline-sparkline-year-label')) {
                const year = e.target.parentElement.dataset.year;
                this.navigateToYear(year);
            }
        });
        
        // Month clicks - show modal or navigate based on modifier keys
        this.element.addEventListener('click', (e) => {
            if (e.target.classList.contains('timeline-sparkline-month') && e.target.classList.contains('has-articles')) {
                const year = e.target.dataset.year;
                const month = e.target.dataset.month;
                
                // If ctrl/cmd key or right click, navigate directly to month
                if (e.ctrlKey || e.metaKey || e.button === 2) {
                    this.navigateToMonth(year, month);
                } else {
                    // Otherwise show modal
                    e.preventDefault();
                    this.showMonthModal(year, month, e);
                }
            }
        });
        
        // Handle right-click context menu for direct navigation
        this.element.addEventListener('contextmenu', (e) => {
            if (e.target.classList.contains('timeline-sparkline-month') && e.target.classList.contains('has-articles')) {
                const year = e.target.dataset.year;
                const month = e.target.dataset.month;
                
                // Create a temporary link for context menu
                const link = document.createElement('a');
                link.href = `/timeline/${year}/${month}/`;
                link.style.display = 'none';
                document.body.appendChild(link);
                
                // Allow default context menu
                setTimeout(() => document.body.removeChild(link), 100);
            }
        });
        
        // Month hover for modal preview
        this.element.addEventListener('mouseenter', (e) => {
            if (e.target.classList.contains('timeline-sparkline-month') && e.target.classList.contains('has-articles')) {
                const year = e.target.dataset.year;
                const month = e.target.dataset.month;
                this.showMonthModal(year, month, e);
            }
        }, true);
        
        // Month leave - hide modal with delay
        this.element.addEventListener('mouseleave', (e) => {
            if (e.target.classList.contains('timeline-sparkline-month')) {
                // Check if we're moving to the modal
                const relatedTarget = e.relatedTarget;
                if (relatedTarget && (relatedTarget.closest('#sparkline-hover-modal') || relatedTarget.id === 'sparkline-hover-modal')) {
                    return; // Don't hide if moving to modal
                }
                
                // Add a small delay to allow for movement to modal
                this.hoverTimeout = setTimeout(() => {
                    if (!window.isSparklineModalHovered) {
                        this.hideMonthModal();
                    }
                }, 100);
            }
        }, true);
    }
    
    createModal() {
        if (document.getElementById('sparkline-hover-modal')) return;
        
        // Initialize global modal hover state
        if (typeof window.isSparklineModalHovered === 'undefined') {
            window.isSparklineModalHovered = false;
        }
        
        const modal = document.createElement('div');
        modal.id = 'sparkline-hover-modal';
        modal.style.display = 'none';
        modal.style.position = 'fixed';
        modal.style.zIndex = '10000';
        modal.style.background = '#fff';
        modal.style.border = '1px solid #ccc';
        modal.style.borderRadius = '8px';
        modal.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        modal.style.padding = '1rem';
        modal.style.maxWidth = '350px';
        modal.style.maxHeight = '500px';
        modal.style.overflowY = 'auto';
        modal.innerHTML = `
            <div id='sparkline-hover-modal-content'>
                <div id='sparkline-hover-modal-list'></div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add hover handlers to the modal itself
        modal.addEventListener('mouseenter', () => {
            window.isSparklineModalHovered = true;
            // Clear any pending hide timeout
            if (this.hoverTimeout) {
                clearTimeout(this.hoverTimeout);
                this.hoverTimeout = null;
            }
        });
        
        modal.addEventListener('mouseleave', () => {
            window.isSparklineModalHovered = false;
            // Add a small delay before hiding to prevent twitchiness
            this.modalHoverTimeout = setTimeout(() => {
                if (!window.isSparklineModalHovered) {
                    this.hideMonthModal();
                }
            }, 100);
        });
    }
    
    async loadArticlesForMonth(year, month) {
        try {
            const ajaxUrl = window.timelineCalendarSettings?.ajaxUrl || '/wp-admin/admin-ajax.php';
            const nonce = window.timelineCalendarSettings?.nonce || '';
            const url = `${ajaxUrl}?action=timeline_calendar_articles&year=${year}&month=${month}&nonce=${nonce}`;
            const res = await fetch(url);
            const articles = await res.json();
            return Array.isArray(articles) ? articles : [];
        } catch (e) {
            console.error('Error loading articles for month:', e);
            return [];
        }
    }
    
    async showMonthModal(year, month, event) {
        // Clear any existing timeout
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        
        // Clear any modal hover timeout
        if (this.modalHoverTimeout) {
            clearTimeout(this.modalHoverTimeout);
            this.modalHoverTimeout = null;
        }
        
        const modal = document.getElementById('sparkline-hover-modal');
        const listDiv = document.getElementById('sparkline-hover-modal-list');
        
        // Show loading state
        listDiv.innerHTML = '<div style="text-align: center; padding: 1rem; color: #666;">Loading articles...</div>';
        modal.style.display = 'block';
        
        // Load articles for this month
        const articles = await this.loadArticlesForMonth(year, month);
        
        if (articles.length === 0) {
            listDiv.innerHTML = '<div style="text-align: center; padding: 1rem; color: #666;">No articles found for this month.</div>';
            return;
        }
        
        // Sort articles by day, then by time of day, then by title
        const timeOrder = ['Morning', 'Day', 'Afternoon', 'Evening', 'Night'];
        articles.sort((a, b) => {
            const dayA = parseInt(a.timeline_day) || 0;
            const dayB = parseInt(b.timeline_day) || 0;
            if (dayA !== dayB) {
                return dayA - dayB;
            }
            
            // Within the same day, sort by time of day
            const aTimeIndex = a.timeline_time_of_day ? timeOrder.indexOf(a.timeline_time_of_day) : -1;
            const bTimeIndex = b.timeline_time_of_day ? timeOrder.indexOf(b.timeline_time_of_day) : -1;
            
            // Articles without time of day come first
            if (aTimeIndex === -1 && bTimeIndex !== -1) return -1;
            if (aTimeIndex !== -1 && bTimeIndex === -1) return 1;
            if (aTimeIndex === -1 && bTimeIndex === -1) {
                return a.title.localeCompare(b.title);
            }
            
            // Then sort by time of day order
            if (aTimeIndex !== bTimeIndex) {
                return aTimeIndex - bTimeIndex;
            }
            
            // Finally by title
            return a.title.localeCompare(b.title);
        });
        
        // Format the month header
        const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const monthName = monthNames[parseInt(month) - 1];
        const monthTitle = `${monthName}, Year ${year}`;
        const articleCount = articles.length;
        const articleText = articleCount === 1 ? 'article' : 'articles';
        
        let html = `<h4 style='margin: 0 0 0.75rem 0; color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 0.5rem; text-align: center;'>
            <a href='/timeline/${year}/${month}/' style='color: #333; text-decoration: none;' onmouseover='this.style.color="#0066cc"' onmouseout='this.style.color="#333"'>${monthTitle}</a>
            <div style='font-size: 0.8rem; color: #666; font-weight: normal; margin-top: 0.25rem;'>${articleCount} ${articleText}</div>
        </h4>`;
        
        // Group articles by day
        const articlesByDay = {};
        articles.forEach(article => {
            const day = parseInt(article.timeline_day) || 1;
            if (!articlesByDay[day]) {
                articlesByDay[day] = [];
            }
            articlesByDay[day].push(article);
        });
        
        // Render articles in a compact format
        const sortedDays = Object.keys(articlesByDay).sort((a, b) => parseInt(a) - parseInt(b));
        
        html += '<div style="margin: 0;">';
        
        for (const day of sortedDays) {
            const dayArticles = articlesByDay[day];
            
            dayArticles.forEach((article, index) => {
                const timeBadge = article.timeline_time_of_day ? `<span style='background: #0066cc; color: white; padding: 0.1rem 0.4rem; border-radius: 8px; font-size: 0.7rem; margin-left: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;'>${article.timeline_time_of_day}</span>` : '';
                
                html += `<div style='padding: 0.4rem 0; border-bottom: 1px solid #f5f5f5; display: flex; align-items: flex-start; gap: 0.75rem;'>`;
                
                // Date column (fixed width)
                html += `<div style='flex-shrink: 0; width: 60px; font-size: 0.85rem; color: #666; font-weight: 500;'>
                    <a href='/timeline/${year}/${month}/${day}/' style='color: #666; text-decoration: none;' onmouseover='this.style.color="#0066cc"' onmouseout='this.style.color="#666"'>
                        ${monthName} ${day}
                    </a>
                </div>`;
                
                // Article title column (flexible width) with time badge
                html += `<div style='flex: 1; min-width: 0; display: flex; justify-content: space-between; align-items: center;'>
                    <a href='${article.permalink}' style='color: #0066cc; text-decoration: none; font-size: 0.9rem; line-height: 1.3; flex: 1;' onmouseover='this.style.textDecoration="underline"' onmouseout='this.style.textDecoration="none"'>${article.title}</a>
                    ${timeBadge}
                </div>`;
                
                html += '</div>';
            });
        }
        
        html += '</div>';
        
        listDiv.innerHTML = html;
        
        // Position the modal near the month element
        const rect = event.target.getBoundingClientRect();
        
        // Show modal first to get its actual dimensions
        modal.style.display = 'block';
        const modalRect = modal.getBoundingClientRect();
        const modalWidth = modalRect.width;
        const modalHeight = modalRect.height;
        
        // Center horizontally over the month
        let left = rect.left + (rect.width / 2) - (modalWidth / 2);
        
        // Position below the month with a small offset
        let top = rect.bottom + 10;
        
        // If not enough space below, position above the month
        if (top + modalHeight > window.innerHeight - 10) {
            top = rect.top - modalHeight - 10;
        }
        
        // Ensure modal stays within viewport bounds
        left = Math.max(10, Math.min(left, window.innerWidth - modalWidth - 10));
        top = Math.max(10, Math.min(top, window.innerHeight - modalHeight - 10));
        
        modal.style.left = left + 'px';
        modal.style.top = top + 'px';
        modal.style.display = 'block';
    }
    
    hideMonthModal() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        
        if (this.modalHoverTimeout) {
            clearTimeout(this.modalHoverTimeout);
            this.modalHoverTimeout = null;
        }
        
        window.isSparklineModalHovered = false;
        
        const modal = document.getElementById('sparkline-hover-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async navigate(direction) {
        const yearsPerView = this.yearsPerView; // Use the configured years per view
        
        if (direction === 'prev') {
            this.currentYearRange.start -= yearsPerView;
            this.currentYearRange.end -= yearsPerView;
        } else {
            this.currentYearRange.start += yearsPerView;
            this.currentYearRange.end += yearsPerView;
        }
        
        await this.loadData();
        this.render();
    }
    
    navigateToYear(year) {
        window.location.href = `/timeline/${year}/`;
    }
    
    navigateToMonth(year, month) {
        window.location.href = `/timeline/${year}/${month}/`;
    }
}

// Make the class globally available
window.TimelineSparklineCalendar = TimelineSparklineCalendar;

// Auto-initialize if script is loaded (only for overview page)
// TEMPORARILY DISABLED FOR TESTING
/*
document.addEventListener('DOMContentLoaded', function() {
    const sparklineContainer = document.getElementById('timeline-sparkline-calendar');
    // Only auto-initialize if we're on the overview page (timeline/ without year)
    if (sparklineContainer && window.location.pathname === '/timeline/' || window.location.pathname === '/timeline') {
        new TimelineSparklineCalendar('#timeline-sparkline-calendar');
    }
});
*/ 