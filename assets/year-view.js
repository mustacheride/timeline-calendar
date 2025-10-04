class TimelineYearView {
    constructor() {
        this.currentYear = this.getDefaultYear();
        this.availableYears = [];
        this.yearListElement = document.getElementById('timeline-year-list');
        this.yearCalendarElement = document.querySelector('.timeline-year-calendar');
        this.init();
    }

    // Get the default year based on plugin settings
    getDefaultYear() {
        const allowYearZero = window.timelineCalendarSettings ? window.timelineCalendarSettings.allowYearZero : false;
        return allowYearZero ? 0 : 1;
    }

    async init() {
        await this.loadAvailableYears();
        
        // Set current year from URL parameter or data attribute
        const urlParams = new URLSearchParams(window.location.search);
        const urlYear = urlParams.get('timeline_year');
        if (urlYear !== null) {
            this.currentYear = parseInt(urlYear);
        } else if (this.yearCalendarElement) {
            const dataYear = this.yearCalendarElement.getAttribute('data-current-year');
            if (dataYear) {
                this.currentYear = parseInt(dataYear);
            }
        }
        
        this.renderYearNavigation();
        this.attachEventHandlers();
        this.highlightCurrentYear();
    }

    async loadAvailableYears() {
        try {
            const response = await fetch('/wp-admin/admin-ajax.php?action=timeline_calendar_years');
            this.availableYears = await response.json();
            if (this.availableYears.length > 0) {
                this.currentYear = parseInt(this.availableYears[0]);
            }
        } catch (error) {
            console.error('Failed to load years:', error);
            this.availableYears = [this.getDefaultYear()];
            this.currentYear = this.getDefaultYear();
        }
    }

    renderYearNavigation() {
        if (!this.yearListElement) return;

        let html = '';
        this.availableYears.forEach(year => {
            const yearInt = parseInt(year);
            html += `<div class="timeline-year-item" data-year="${yearInt}">${yearInt}</div>`;
        });
        this.yearListElement.innerHTML = html;
    }

    attachEventHandlers() {
        // Year navigation buttons
        const prevBtn = document.getElementById('timeline-year-prev');
        const nextBtn = document.getElementById('timeline-year-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.navigateYear(-1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.navigateYear(1));
        }

        // Year item clicks
        if (this.yearListElement) {
            this.yearListElement.addEventListener('click', (e) => {
                if (e.target.classList.contains('timeline-year-item')) {
                    const year = parseInt(e.target.getAttribute('data-year'));
                    this.selectYear(year);
                }
            });
        }
    }

    navigateYear(direction) {
        const currentIndex = this.availableYears.indexOf(this.currentYear.toString());
        let newIndex = currentIndex + direction;
        
        if (newIndex < 0) {
            newIndex = this.availableYears.length - 1;
        } else if (newIndex >= this.availableYears.length) {
            newIndex = 0;
        }

        const newYear = parseInt(this.availableYears[newIndex]);
        this.selectYear(newYear);
    }

    selectYear(year) {
        this.currentYear = year;
        this.highlightCurrentYear();
        this.updateYearCalendar();
        this.scrollToYear(year);
        
        // Update URL without page reload
        const url = new URL(window.location);
        url.searchParams.set('timeline_year', year);
        window.history.pushState({}, '', url);
    }

    highlightCurrentYear() {
        // Remove previous highlights
        document.querySelectorAll('.timeline-year-item').forEach(item => {
            item.classList.remove('active');
        });

        // Highlight current year
        const currentYearItem = document.querySelector(`[data-year="${this.currentYear}"]`);
        if (currentYearItem) {
            currentYearItem.classList.add('active');
        }
    }

    scrollToYear(year) {
        const yearItem = document.querySelector(`[data-year="${year}"]`);
        if (yearItem && this.yearListElement) {
            const container = this.yearListElement.parentElement;
            const itemLeft = yearItem.offsetLeft;
            const containerWidth = container.offsetWidth;
            const itemWidth = yearItem.offsetWidth;
            
            container.scrollLeft = itemLeft - (containerWidth / 2) + (itemWidth / 2);
        }
    }

    updateYearCalendar() {
        if (!this.yearCalendarElement) return;

        // Update the data attribute
        this.yearCalendarElement.setAttribute('data-current-year', this.currentYear);

        // Update all month calendars
        const monthCalendars = this.yearCalendarElement.querySelectorAll('.timeline-calendar-root');
        monthCalendars.forEach(calendar => {
            const month = parseInt(calendar.getAttribute('data-month'));
            calendar.setAttribute('data-year', this.currentYear);
            
            // Reinitialize the calendar for the new year
            if (calendar.timelineCalendar) {
                calendar.timelineCalendar.currentYear = this.currentYear;
                calendar.timelineCalendar.loadArticlesForMonth().then(() => {
                    calendar.timelineCalendar.renderCalendar();
                });
            }
        });
    }
}

// Initialize year view when DOM is ready
// TEMPORARILY DISABLED FOR TESTING
/*
if (typeof window !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Only initialize if we have the proper year view structure and we're on a year view page
        if (document.querySelector('.timeline-year-view') && document.querySelector('.timeline-year-calendar')) {
            // Check if we're on a year view page (timeline/year/)
            const path = window.location.pathname;
            if (path.match(/^\/timeline\/\d+\/$/)) {
                new TimelineYearView();
            }
        }
    });
}
*/ 
