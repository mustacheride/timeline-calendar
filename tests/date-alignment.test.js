/**
 * Day-of-week alignment tests for reference-year mapping.
 * wp_localize_script always stringifies values — getRealYear must coerce to number.
 *
 * Mirrors production getRealYear / getDayOfWeek in assets/calendar.js
 */

// --- production formula under test (keep in sync with calendar.js) ---
function getRealYear(fictionalYear, referenceYearFromSettings) {
    const referenceYear = referenceYearFromSettings != null ? referenceYearFromSettings : 1989;
    return Number(referenceYear) + (fictionalYear - 1);
}

function getDayOfWeek(fictionalYear, month, day, referenceYearFromSettings) {
    const realYear = getRealYear(fictionalYear, referenceYearFromSettings);
    return new Date(realYear, month - 1, day).getDay();
}

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
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

// Reference year comes from WP as a string (as on production)
const REF = '1983';

assertEqual(getRealYear(1, REF), 1983, 'Year 1 maps to 1983 (not string concat 19830)');
assertEqual(getRealYear(0, REF), 1982, 'Year 0 maps to 1982 (not "1983-1")');
assertEqual(getRealYear(2, REF), 1984, 'Year 2 maps to 1984');

assertEqual(DAYS[getDayOfWeek(1, 11, 1, REF)], 'Tue', 'Year 1 Nov 1 is Tuesday (1983)');
assertEqual(DAYS[getDayOfWeek(1, 11, 24, REF)], 'Thu', 'Year 1 Thanksgiving Nov 24 is Thursday');
assertEqual(DAYS[getDayOfWeek(1, 11, 8, REF)], 'Tue', 'Year 1 Nov 8 is Tuesday');
assertEqual(DAYS[getDayOfWeek(0, 11, 25, REF)], 'Thu', 'Year 0 Thanksgiving Nov 25 is Thursday');

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed > 0 ? 1 : 0);
