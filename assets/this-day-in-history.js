/**
 * This Day in History — loads articles matching today's month/day across timeline years.
 * Kept out of the shortcode HTML so wpautop cannot inject <p> tags into the JS.
 */
(function () {
    function loadThisDayInHistory() {
        const container = document.getElementById('timeline-this-day-container');
        const subtitle = document.getElementById('timeline-this-day-subtitle');
        const content = document.getElementById('timeline-this-day-content');
        if (!container || !subtitle || !content) {
            return;
        }

        const now = new Date();
        const month = now.getMonth() + 1;
        const day = now.getDate();
        const monthNames = [
            '', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        subtitle.textContent = monthNames[month] + ' ' + day + ' throughout the timeline';

        const formData = new FormData();
        formData.append('action', 'timeline_this_day_in_history');
        formData.append('month', month);
        formData.append('day', day);

        const settings = (typeof timelineCalendarSettings !== 'undefined')
            ? timelineCalendarSettings
            : null;
        if (settings && settings.nonce) {
            formData.append('nonce', settings.nonce);
        }

        const ajaxUrl = (settings && settings.ajaxUrl)
            ? settings.ajaxUrl
            : '/wp-admin/admin-ajax.php';

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    content.innerHTML = data.data.html;
                } else {
                    content.innerHTML =
                        '<div class="timeline-this-day-empty"><p>No timeline articles found for ' +
                        monthNames[month] + ' ' + day + '.</p></div>';
                }
            })
            .catch(function (error) {
                console.error('Error loading This Day in History:', error);
                content.innerHTML =
                    '<div class="timeline-this-day-error"><p>Error loading timeline articles.</p></div>';
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadThisDayInHistory);
    } else {
        loadThisDayInHistory();
    }
})();
