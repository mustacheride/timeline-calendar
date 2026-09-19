/**
 * Year display mode helpers — keep in sync with timeline-calendar.php / calendar.js.
 *
 * relative: labels "Year 1", URLs /timeline/1/
 * absolute: labels "1983", URLs /timeline/1983/  (storage stays fictional)
 */

function fictionalToAbsolute(fictionalYear, referenceYear) {
    return Number(referenceYear) + (Number(fictionalYear) - 1);
}

function absoluteToFictional(absoluteYear, referenceYear) {
    return Number(absoluteYear) - Number(referenceYear) + 1;
}

function isAbsoluteDisplayMode(mode) {
    return mode === 'absolute';
}

function formatTimelineYear(fictionalYear, mode, referenceYear) {
    if (isAbsoluteDisplayMode(mode)) {
        return String(fictionalToAbsolute(fictionalYear, referenceYear));
    }
    return `Year ${fictionalYear}`;
}

function yearForUrl(fictionalYear, mode, referenceYear) {
    if (isAbsoluteDisplayMode(mode)) {
        return fictionalToAbsolute(fictionalYear, referenceYear);
    }
    return Number(fictionalYear);
}

/**
 * Resolve a year segment from a URL into the fictional timeline year used in meta storage.
 * In absolute mode, path years are calendar years. Legacy small fictional URLs still accepted.
 */
function yearFromUrl(urlYear, mode, referenceYear) {
    const y = Number(urlYear);
    if (!isAbsoluteDisplayMode(mode)) {
        return y;
    }
    // Legacy relative URL while in absolute mode: |year| <= 100 treated as fictional
    if (Math.abs(y) <= 100) {
        return y;
    }
    return absoluteToFictional(y, referenceYear);
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

const REF = '1983';

assertEqual(fictionalToAbsolute(1, REF), 1983, 'fictional 1 -> 1983');
assertEqual(fictionalToAbsolute(2, REF), 1984, 'fictional 2 -> 1984');
assertEqual(fictionalToAbsolute(0, REF), 1982, 'fictional 0 -> 1982');
assertEqual(fictionalToAbsolute(-1, REF), 1981, 'fictional -1 -> 1981');

assertEqual(absoluteToFictional(1983, REF), 1, '1983 -> fictional 1');
assertEqual(absoluteToFictional(1984, REF), 2, '1984 -> fictional 2');
assertEqual(absoluteToFictional(1982, REF), 0, '1982 -> fictional 0');

assertEqual(formatTimelineYear(1, 'relative', REF), 'Year 1', 'relative label');
assertEqual(formatTimelineYear(1, 'absolute', REF), '1983', 'absolute label');
assertEqual(formatTimelineYear(-2, 'absolute', REF), '1980', 'absolute negative fictional');

assertEqual(yearForUrl(1, 'relative', REF), 1, 'relative URL segment');
assertEqual(yearForUrl(1, 'absolute', REF), 1983, 'absolute URL segment');

assertEqual(yearFromUrl(1, 'relative', REF), 1, 'relative inbound');
assertEqual(yearFromUrl(1983, 'absolute', REF), 1, 'absolute inbound 1983');
assertEqual(yearFromUrl(1, 'absolute', REF), 1, 'legacy fictional inbound in absolute mode');
assertEqual(yearFromUrl(1984, 'absolute', REF), 2, 'absolute inbound 1984');

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed > 0 ? 1 : 0);
