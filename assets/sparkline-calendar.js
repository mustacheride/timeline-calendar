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
        this.currentYearRange = { 
            start: this.options.startYear, 
            end: this.options.endYear 
        };
        this.yearsPerView = this.options.yearsPerView;
        
        console.log('SparklineCalendar constructor - Initial year range:', this.currentYearRange);
        
        // Initialize
        this.init();
    }
    
    async init() {
        await this.loadData();
        this.render();
        this.bindEvents();
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
        
        // Create header (without navigation controls)
        const header = document.createElement('div');
        header.className = 'timeline-sparkline-header';
        
        if (this.options.showNavigation) {
            header.innerHTML = `
                <h2>Timeline Overview</h2>
                <span class="timeline-sparkline-range">Years ${this.currentYearRange.start} - ${this.currentYearRange.end}</span>
            `;
        } else {
            header.innerHTML = `
                <h2>Year ${this.currentYearRange.start} Overview</h2>
            `;
        }
        this.element.appendChild(header);
        
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
                monthElement.dataset.count = articleCount;
                
                // Add tooltip
                const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                   'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                monthElement.title = `${monthNames[month]} ${year}: ${articleCount} article${articleCount !== 1 ? 's' : ''}`;
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
        
        // Month clicks
        this.element.addEventListener('click', (e) => {
            if (e.target.classList.contains('timeline-sparkline-month')) {
                const year = e.target.dataset.year;
                const month = e.target.dataset.month;
                this.navigateToMonth(year, month);
            }
        });
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