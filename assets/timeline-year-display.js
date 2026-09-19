/**
 * Shared year display / URL helpers for Timeline Calendar.
 * Keep formulas in sync with tests/year-display-mode.test.js and PHP helpers.
 */
(function (global) {
    function settings() {
        return global.timelineCalendarSettings || {};
    }

    function referenceYear() {
        return Number(settings().referenceYear != null ? settings().referenceYear : 1989);
    }

    function isAbsoluteMode() {
        return settings().yearDisplayMode === 'absolute';
    }

    function fictionalToAbsolute(fictionalYear) {
        return referenceYear() + (Number(fictionalYear) - 1);
    }

    function absoluteToFictional(absoluteYear) {
        return Number(absoluteYear) - referenceYear() + 1;
    }

    function formatYear(fictionalYear) {
        if (isAbsoluteMode()) {
            return String(fictionalToAbsolute(fictionalYear));
        }
        return 'Year ' + Number(fictionalYear);
    }

    function yearForUrl(fictionalYear) {
        if (isAbsoluteMode()) {
            return fictionalToAbsolute(fictionalYear);
        }
        return Number(fictionalYear);
    }

    function yearFromUrl(urlYear) {
        const y = Number(urlYear);
        if (!isAbsoluteMode()) {
            return y;
        }
        if (Math.abs(y) <= 100) {
            return y;
        }
        return absoluteToFictional(y);
    }

    function timelinePath(fictionalYear, month, day, slug) {
        let path = '/timeline/';
        if (fictionalYear === undefined || fictionalYear === null || fictionalYear === '') {
            return path;
        }
        path += yearForUrl(fictionalYear) + '/';
        if (month !== undefined && month !== null && month !== '') {
            path += Number(month) + '/';
            if (day !== undefined && day !== null && day !== '') {
                path += Number(day) + '/';
                if (slug) {
                    path += slug + '/';
                }
            }
        }
        return path;
    }

    global.TimelineYearDisplay = {
        fictionalToAbsolute: fictionalToAbsolute,
        absoluteToFictional: absoluteToFictional,
        formatYear: formatYear,
        yearForUrl: yearForUrl,
        yearFromUrl: yearFromUrl,
        timelinePath: timelinePath,
        isAbsoluteMode: isAbsoluteMode
    };
})(typeof window !== 'undefined' ? window : globalThis);
