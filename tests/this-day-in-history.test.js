/**
 * This Day in History AJAX URL resolution.
 * Production bug: shortcode referenced undefined timelineCalendarAjax.
 */

function resolveThisDayAjaxUrl(globals, phpFallback) {
    if (globals.timelineCalendarSettings && globals.timelineCalendarSettings.ajaxUrl) {
        return globals.timelineCalendarSettings.ajaxUrl;
    }
    if (typeof globals.timelineCalendarAjax !== 'undefined' && globals.timelineCalendarAjax.ajaxurl) {
        return globals.timelineCalendarAjax.ajaxurl;
    }
    return phpFallback;
}

let passed = 0;
let failed = 0;

function assertEqual(actual, expected, message) {
    if (actual === expected) {
        passed++;
        console.log('PASS:', message);
    } else {
        failed++;
        console.error('FAIL:', message, '| expected', expected, 'got', actual);
    }
}

const fallback = 'https://batmanistired.com/wp-admin/admin-ajax.php';

assertEqual(
    resolveThisDayAjaxUrl({
        timelineCalendarSettings: { ajaxUrl: 'https://example.com/wp-admin/admin-ajax.php' }
    }, fallback),
    'https://example.com/wp-admin/admin-ajax.php',
    'uses timelineCalendarSettings.ajaxUrl when present'
);

assertEqual(
    resolveThisDayAjaxUrl({}, fallback),
    fallback,
    'falls back when no globals defined (does not throw)'
);

// Old buggy access throws when timelineCalendarAjax is missing
let threw = false;
try {
    const g = {};
    // eslint-disable-next-line no-undef
    void g.timelineCalendarAjax.ajaxurl;
} catch (e) {
    threw = true;
}
assertEqual(threw, true, 'accessing missing timelineCalendarAjax.ajaxurl throws (old bug)');

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed > 0 ? 1 : 0);
