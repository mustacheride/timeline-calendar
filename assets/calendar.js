class TimelineCalendar {
    constructor(root = null, year = null, month = null) {
        this.root = root || document.getElementById('timeline-calendar-root');
        this.currentYear = year !== null ? year : 0;
        this.currentMonth = month !== null ? month : 1;
        this.articles = [];
        this.selectedDay = null;
        this.isStatic = !!root; // If root is passed, treat as static (no navigation)
        this.hoverTimeout = null;
        this.modalHoverTimeout = null;
        
        // Store the calendar instance on the root element for year view updates
        if (this.root) {
            this.root.timelineCalendar = this;
        }
        
        this.init();
    }

    // Convert fictional year to real year (Year 1 = reference year)
    getRealYear(fictionalYear) {
        const referenceYear = window.timelineCalendarSettings ? window.timelineCalendarSettings.referenceYear : 1989;
        return referenceYear + (fictionalYear - 1);
    }

    // Get the day of the week for a given fictional date
    getDayOfWeek(fictionalYear, month, day) {
        const realYear = this.getRealYear(fictionalYear);
        const date = new Date(realYear, month - 1, day);
        return date.getDay(); // 0 = Sunday, 1 = Monday, etc.
    }

    // Get the first day of the month (0 = Sunday, 1 = Monday, etc.)
    getFirstDayOfMonth(fictionalYear, month) {
        return this.getDayOfWeek(fictionalYear, month, 1);
    }
    async init() {
        await this.loadArticlesForMonth();
        this.renderCalendar();
        if (!this.isStatic) {
            this.attachNavHandlers();
            this.createModal();
        } else {
            this.createModal();
        }
    }
    getMonthDays(year, month) {
        const monthLengths = [31,28,31,30,31,30,31,31,30,31,30,31];
        const days = [];
        const daysInMonth = monthLengths[month-1];
        for (let i = 1; i <= daysInMonth; i++) {
            days.push(i);
        }
        return days;
    }
    renderCalendar() {
        const root = this.root;
        if (!root) return;
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const days = this.getMonthDays(this.currentYear, this.currentMonth);
        const firstDayOfWeek = this.getFirstDayOfMonth(this.currentYear, this.currentMonth);
        
        let html = '';
        if (!this.isStatic) {
            html += `<div class='calendar-nav'>
                <button id='cal-prev-year'>&lt;&lt;</button>
                <button id='cal-prev-month'>&lt;</button>
                <span>${months[this.currentMonth-1]}, Year ${this.currentYear}</span>
                <button id='cal-next-month'>&gt;</button>
                <button id='cal-next-year'>&gt;&gt;</button>
            </div>`;
        }
        html += "<div class='timeline-calendar'>";
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDayOfWeek; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        // Add the days of the month
        for (let d of days) {
            const articlesForDay = this.articles.filter(a => parseInt(a.timeline_day) === d);
            const articleCount = articlesForDay.length;
            const dayClass = articleCount ? ' day-has-articles' : '';
            const dataAttr = articleCount ? ` data-article-count="${articleCount}"` : '';
            html += `<div class='calendar-day${dayClass}' data-day='${d}'${dataAttr}>${d}</div>`;
        }
        html += "</div>";
        root.innerHTML = html;
        this.attachDayHandlers();
    }
    attachNavHandlers() {
        document.addEventListener('click', async e => {
            if (e.target.id === 'cal-prev-year') {
                this.currentYear--;
                await this.loadArticlesForMonth();
                this.renderCalendar();
            } else if (e.target.id === 'cal-next-year') {
                this.currentYear++;
                await this.loadArticlesForMonth();
                this.renderCalendar();
            } else if (e.target.id === 'cal-prev-month') {
                this.currentMonth--;
                if (this.currentMonth < 1) {
                    this.currentMonth = 12;
                    this.currentYear--;
                }
                await this.loadArticlesForMonth();
                this.renderCalendar();
            } else if (e.target.id === 'cal-next-month') {
                this.currentMonth++;
                if (this.currentMonth > 12) {
                    this.currentMonth = 1;
                    this.currentYear++;
                }
                await this.loadArticlesForMonth();
                this.renderCalendar();
            }
        });
    }
    attachDayHandlers() {
        this.root.querySelectorAll('.calendar-day[data-day]').forEach(day => {
            day.addEventListener('click', e => {
                this.selectedDay = day.getAttribute('data-day');
                this.navigateToDayView(this.selectedDay);
            });
            
            // Mouse enter for day
            day.addEventListener('mouseenter', e => {
                this.selectedDay = day.getAttribute('data-day');
                this.showHoverModal(this.selectedDay, e);
            });
            
            // Mouse leave for day
            day.addEventListener('mouseleave', e => {
                // Check if we're moving to the modal
                const relatedTarget = e.relatedTarget;
                if (relatedTarget && (relatedTarget.closest('#calendar-hover-modal') || relatedTarget.id === 'calendar-hover-modal')) {
                    return; // Don't hide if moving to modal
                }
                
                // Add a small delay to allow for movement to modal
                this.hoverTimeout = setTimeout(() => {
                    if (!window.isModalHovered) {
                        this.hideHoverModal();
                    }
                }, 50);
            });
        });
    }
    async loadArticlesForMonth() {
        try {
            const res = await fetch(`/wp-admin/admin-ajax.php?action=timeline_calendar_articles&year=${this.currentYear}&month=${this.currentMonth}`);
            this.articles = await res.json();
        } catch (e) {
            this.articles = [];
        }
    }
    createModal() {
        if (document.getElementById('calendar-hover-modal')) return;
        
        // Initialize global modal hover state
        if (typeof window.isModalHovered === 'undefined') {
            window.isModalHovered = false;
        }
        
        const modal = document.createElement('div');
        modal.id = 'calendar-hover-modal';
        modal.style.display = 'none';
        modal.style.position = 'fixed';
        modal.style.zIndex = '10000';
        modal.style.background = '#fff';
        modal.style.border = '1px solid #ccc';
        modal.style.borderRadius = '8px';
        modal.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        modal.style.padding = '1rem';
        modal.style.maxWidth = '300px';
        modal.style.maxHeight = '400px';
        modal.style.overflowY = 'auto';
        modal.innerHTML = `
            <div id='calendar-hover-modal-content'>
                <div id='calendar-hover-modal-list'></div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add hover handlers to the modal itself
        modal.addEventListener('mouseenter', () => {
            window.isModalHovered = true;
            // Clear any pending hide timeout
            if (this.hoverTimeout) {
                clearTimeout(this.hoverTimeout);
                this.hoverTimeout = null;
            }
        });
        
        modal.addEventListener('mouseleave', () => {
            window.isModalHovered = false;
            // Add a small delay before hiding to prevent twitchiness
            this.modalHoverTimeout = setTimeout(() => {
                if (!window.isModalHovered) {
                    this.hideHoverModal();
                }
            }, 100);
        });
    }
    showHoverModal(day, event) {
        const articlesForDay = this.articles.filter(a => parseInt(a.timeline_day) === parseInt(day));
        if (articlesForDay.length === 0) return;
        
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
        
        const modal = document.getElementById('calendar-hover-modal');
        const listDiv = document.getElementById('calendar-hover-modal-list');
        
        // Sort articles by time of day, then alphabetically
        const timeOrder = ['Morning', 'Day', 'Afternoon', 'Evening', 'Night'];
        articlesForDay.sort((a, b) => {
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
        
        // Format the date properly
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const monthName = months[this.currentMonth - 1];
        const dateTitle = `${monthName} ${day}, Year ${this.currentYear}`;
        
        let html = `<h4 style='margin: 0 0 0.5rem 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;'><a href='/timeline/${this.currentYear}/${this.currentMonth}/${day}/' style='color: #333; text-decoration: none;' onmouseover='this.style.textDecoration="underline"' onmouseout='this.style.textDecoration="none"'>${dateTitle}</a></h4>`;
        html += '<ul style="list-style: none; padding: 0; margin: 0;">';
        for (const art of articlesForDay) {
            const timeBadge = art.timeline_time_of_day ? `<span style='background: #0066cc; color: white; padding: 0.1rem 0.4rem; border-radius: 8px; font-size: 0.7rem; margin-left: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;'>${art.timeline_time_of_day}</span>` : '';
            html += `<li style='padding: 0.25rem 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;'>
                <a href='${art.permalink}' style='color: #0066cc; text-decoration: none; font-size: 0.9rem; flex: 1;' onmouseover='this.style.textDecoration="underline"' onmouseout='this.style.textDecoration="none"'>${art.title}</a>
                ${timeBadge}
            </li>`;
        }
        html += '</ul>';
        listDiv.innerHTML = html;
        
        // Position the modal below the day element
        const rect = event.target.getBoundingClientRect();
        
        // Show modal first to get its actual dimensions
        modal.style.display = 'block';
        const modalRect = modal.getBoundingClientRect();
        const modalWidth = modalRect.width;
        const modalHeight = modalRect.height;
        
        // Center horizontally over the day
        let left = rect.left + (rect.width / 2) - (modalWidth / 2);
        
        // Position below the day with a small offset
        let top = rect.bottom + 10;
        
        // If not enough space below, position above the day
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
    hideHoverModal() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        
        if (this.modalHoverTimeout) {
            clearTimeout(this.modalHoverTimeout);
            this.modalHoverTimeout = null;
        }
        
        window.isModalHovered = false;
        
        const modal = document.getElementById('calendar-hover-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    navigateToDayView(day) {
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const monthName = months[this.currentMonth - 1].toLowerCase();
        const dayViewUrl = `/timeline/${this.currentYear}/${this.currentMonth}/${day}/`;
        window.location.href = dayViewUrl;
    }
    handleDayClick(day) {
        // No longer used
    }
    showArticlePreview(day) {
        console.log('Preview for day', day, 'Year', this.currentYear, 'Month', this.currentMonth);
    }
}
// Expose TimelineCalendar globally
if (typeof window !== 'undefined') {
    window.TimelineCalendar = TimelineCalendar;
    
    // Only auto-initialize if we're not on a timeline page (to avoid conflicts)
    // TEMPORARILY DISABLED FOR TESTING
    /*
    document.addEventListener('DOMContentLoaded', () => {
        const path = window.location.pathname;
        if (document.getElementById('timeline-calendar-root') && !path.startsWith('/timeline/')) {
            new TimelineCalendar();
        }
    });
    */
} 