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
            startYear: options.startYear ?? this.getDefaultStartYear(),
            endYear: options.endYear ?? this.getDefaultEndYear(),
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
        this.minYearBound = null;
        this.maxYearBound = null;
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
    
    getDefaultStartYear() {
        // Get plugin settings from localized script data
        if (window.timelineCalendarSettings) {
            const { allowYearZero, allowNegativeYears } = window.timelineCalendarSettings;
            if (allowNegativeYears && allowYearZero) {
                return -2;
            } else if (allowNegativeYears && !allowYearZero) {
                return -2; // Start at -2, but we'll filter out Year 0 in the render
            } else if (allowYearZero) {
                return 0;
            } else {
                return 1; // When Year 0 is disabled, start from Year 1
            }
        }
        return 1; // Default to year 1 if settings not available
    }
    
    getDefaultEndYear() {
        // Get plugin settings from localized script data
        if (window.timelineCalendarSettings) {
            const { allowYearZero, allowNegativeYears } = window.timelineCalendarSettings;
            if (allowNegativeYears && allowYearZero) {
                return 4;
            } else if (allowNegativeYears && !allowYearZero) {
                return 5; // Extend to 5 to compensate for skipping Year 0
            } else if (allowYearZero) {
                return 6;
            } else {
                return 7; // When Year 0 is disabled, end at Year 7 (6 years from Year 1)
            }
        }
        return 7; // Default to year 7 if settings not available
    }
    
    isYearAllowed(year) {
        // Get plugin settings from localized script data
        if (window.timelineCalendarSettings) {
            const { allowYearZero, allowNegativeYears } = window.timelineCalendarSettings;
            
            if (year === 0) {
                return allowYearZero;
            } else if (year < 0) {
                return allowNegativeYears;
            } else {
                return true; // Positive years are always allowed
            }
        }
        return year >= 1; // Default: only positive years allowed
    }
    
    // Convert fictional year to real year (consistent with calendar.js)
    // The reference year alignment is fixed and doesn't change based on Year 0 setting
    getRealYear(fictionalYear) {
        // wp_localize_script stringifies values — Number() prevents "1983"+0 => "19830"
        const referenceYear = Number(
            window.timelineCalendarSettings ? window.timelineCalendarSettings.referenceYear : 1989
        );
        
        // Fixed mapping: Year 1 always maps to reference year
        // Year 0 (if it exists) maps to reference year - 1
        // Year 2 maps to reference year + 1, etc.
        return referenceYear + (fictionalYear - 1);
    }
    
    checkForArticlesBeyondRange() {
        const maxBound = this.getMaxYearBound();
        return maxBound > this.currentYearRange.end;
    }

    checkForArticlesBeforeRange() {
        const minBound = this.getMinYearBound();
        return minBound < this.currentYearRange.start;
    }

    getMinYearBound() {
        if (this.minYearBound !== null && !Number.isNaN(this.minYearBound)) {
            return this.minYearBound;
        }
        return this.getDefaultStartYear();
    }

    getMaxYearBound() {
        if (this.maxYearBound !== null && !Number.isNaN(this.maxYearBound)) {
            return this.maxYearBound;
        }
        return this.currentYearRange.end;
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
                // New payload: { sparkline, min_year, max_year }; keep backward compat
                const payload = data.data || {};
                if (payload.sparkline) {
                    this.data = payload.sparkline;
                    this.minYearBound = parseInt(payload.min_year, 10);
                    this.maxYearBound = parseInt(payload.max_year, 10);
                } else {
                    this.data = payload;
                }
                
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
            
            // Disable prev when nothing earlier exists in the timeline
            if (!this.checkForArticlesBeforeRange()) {
                leftNav.disabled = true;
                leftNav.classList.add('timeline-nav-disabled');
                leftNav.title = 'No earlier timeline articles';
            }
            sparklineContainer.appendChild(leftNav);
        }
        
        // Create scrollable container
        const scrollContainer = document.createElement('div');
        scrollContainer.className = 'timeline-sparkline-scroll';
        
        // Create years container
        const yearsContainer = document.createElement('div');
        yearsContainer.className = 'timeline-sparkline-years';
        
        // Render years in the current window only (numerical order)
        const sortedYears = Object.keys(this.data).sort((a, b) => parseInt(a) - parseInt(b));
        sortedYears.forEach(year => {
            const yearNum = parseInt(year, 10);
            if (yearNum < this.currentYearRange.start || yearNum > this.currentYearRange.end) {
                return;
            }
            if (!this.isYearAllowed(yearNum)) {
                return;
            }
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
            
            // Disable next when nothing later exists in the timeline
            if (!this.checkForArticlesBeyondRange()) {
                rightNav.disabled = true;
                rightNav.classList.add('timeline-nav-disabled');
                rightNav.title = 'No more timeline articles available';
            }
            
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
        yearLabel.textContent = window.TimelineYearDisplay
            ? TimelineYearDisplay.formatYear(year)
            : `Year ${year}`;
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
                monthElement.style.height = '0px';
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
                
                // Don't navigate if button is disabled
                if (e.target.disabled) {
                    return;
                }
                
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
                link.href = window.TimelineYearDisplay
                    ? TimelineYearDisplay.timelinePath(year, month)
                    : `/timeline/${year}/${month}/`;
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
        modal.className = 'timeline-hover-modal';
        modal.style.display = 'none';
        modal.style.position = 'fixed';
        modal.style.zIndex = '10000';
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
        listDiv.innerHTML = '<div class="timeline-hover-modal-status">Loading articles...</div>';
        modal.style.display = 'block';
        
        // Load articles for this month
        const articles = await this.loadArticlesForMonth(year, month);
        
        if (articles.length === 0) {
            listDiv.innerHTML = '<div class="timeline-hover-modal-status">No articles found for this month.</div>';
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
        const monthTitle = `${monthName}, ${window.TimelineYearDisplay ? TimelineYearDisplay.formatYear(year) : 'Year ' + year}`;
        const monthPath = window.TimelineYearDisplay
            ? TimelineYearDisplay.timelinePath(year, month)
            : `/timeline/${year}/${month}/`;
        const articleCount = articles.length;
        const articleText = articleCount === 1 ? 'article' : 'articles';
        
        let html = `<h4 class="timeline-hover-modal-title">
            <a href="${monthPath}">${monthTitle}</a>
            <div class="timeline-hover-modal-count">${articleCount} ${articleText}</div>
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
        
        html += '<div class="timeline-hover-modal-rows">';
        
        for (const day of sortedDays) {
            const dayArticles = articlesByDay[day];
            
            dayArticles.forEach((article) => {
                const timeBadge = article.timeline_time_of_day
                    ? `<span class="timeline-hover-modal-badge">${article.timeline_time_of_day}</span>`
                    : '';
                const dayPath = window.TimelineYearDisplay
                    ? TimelineYearDisplay.timelinePath(year, month, day)
                    : `/timeline/${year}/${month}/${day}/`;
                
                html += `<div class="timeline-hover-modal-row">`;
                html += `<div class="timeline-hover-modal-date"><a href="${dayPath}">${monthName} ${day}</a></div>`;
                html += `<div class="timeline-hover-modal-article">`;
                html += `<a class="timeline-hover-modal-link" href="${article.permalink}">${article.title}</a>`;
                if (timeBadge) {
                    html += `<div class="timeline-hover-modal-badge-wrap">${timeBadge}</div>`;
                }
                html += `</div></div>`;
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
        const yearsPerView = this.yearsPerView;
        const span = this.currentYearRange.end - this.currentYearRange.start;
        
        if (direction === 'prev') {
            if (!this.checkForArticlesBeforeRange()) {
                console.log('No earlier timeline articles');
                return;
            }
            let newStart = this.currentYearRange.start - yearsPerView;
            let newEnd = this.currentYearRange.end - yearsPerView;
            const minBound = this.getMinYearBound();
            if (newStart < minBound) {
                newStart = minBound;
                newEnd = minBound + span;
            }
            this.currentYearRange.start = newStart;
            this.currentYearRange.end = newEnd;
            await this.loadData();
        } else {
            if (!this.checkForArticlesBeyondRange()) {
                console.log('No more timeline articles available beyond current range');
                return;
            }
            let newStart = this.currentYearRange.start + yearsPerView;
            let newEnd = this.currentYearRange.end + yearsPerView;
            const maxBound = this.getMaxYearBound();
            if (newEnd > maxBound && newStart > maxBound) {
                return;
            }
            if (newEnd > maxBound) {
                newEnd = maxBound;
                newStart = maxBound - span;
            }
            this.currentYearRange.start = newStart;
            this.currentYearRange.end = newEnd;
            await this.loadData();
        }
    }
    
    navigateToYear(year) {
        window.location.href = window.TimelineYearDisplay
            ? TimelineYearDisplay.timelinePath(year)
            : `/timeline/${year}/`;
    }
    
    navigateToMonth(year, month) {
        window.location.href = window.TimelineYearDisplay
            ? TimelineYearDisplay.timelinePath(year, month)
            : `/timeline/${year}/${month}/`;
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
