// Unit tests for value parsing and the Bulk Edit check: node tests/values.test.js
// The functions are read straight from fc-studio.html, so the test always checks the app's code.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const html = fs.readFileSync(path.join(__dirname, '..', 'fc-studio.html'), 'utf8');
const fn = name => { const i = html.indexOf(`function ${name}(`); return html.slice(i, html.indexOf('\n}\n', i) + 3); };
const { importNumberValue, importParseDate, bulkPropHas, bulkParseValue } = new Function(
  'const INT32_MIN = -2147483648, INT32_MAX = 2147483647;\n' +
  ['importNormNumber', 'importNumberValue', 'importParseDate', 'bulkPropHas', 'formatDate', 'bulkParseValue'].map(fn).join('\n') +
  '\nreturn { importNumberValue, importParseDate, bulkPropHas, bulkParseValue };')();
const num = importNumberValue, date = importParseDate;
const t = (name, f) => { try { f(); console.log('PASS', name); } catch (e) { console.log('FAIL', name, '\n ', e.message); process.exitCode = 1; } };
const throws = (f, re) => assert.throws(f, re);
t('integer ok', () => { assert.equal(num('42', 1), 42); assert.equal(num('-7', 1), -7); assert.equal(num("1'000", 1), 1000); });
t('integer rejects 12abc', () => throws(() => num('12abc', 1), /not a whole number/));
t('integer rejects 1.5', () => throws(() => num('1.5', 1), /not a whole number/));
t('integer range', () => { assert.equal(num('2147483647', 1), 2147483647); throws(() => num('2147483648', 1), /outside/); });
t('decimal ok incl. German comma', () => { assert.equal(num('5,5', 2), 5.5); assert.equal(num('1.234,5', 2), 1234.5); assert.equal(num('1e3', 2), 1000); });
t('decimal rejects 1.2.3 and text', () => { throws(() => num('1.2.3', 2), /not a number/); throws(() => num('abc', 2), /not a number/); });
t('decimal too large', () => throws(() => num('1e400', 2), /too large/));
t('date plain', () => assert.equal(date('2026-10-13', 'YYYY-MM-DD'), '2026-10-13T00:00:00.000Z'));
t('date offset +02:00 (review case)', () => assert.equal(date('2026-10-13 12:00+02:00', 'YYYY-MM-DD'), '2026-10-13T10:00:00.000Z'));
t('date ISO T with Z', () => assert.equal(date('2026-10-13T12:00:00Z', 'YYYY-MM-DD'), '2026-10-13T12:00:00.000Z'));
t('date -0500 compact', () => assert.equal(date('2026-10-13T12:00-0500', 'YYYY-MM-DD'), '2026-10-13T17:00:00.000Z'));
t('date DD.MM.YYYY with time and offset', () => assert.equal(date('13.10.2026 08:30 +01:00', 'DD.MM.YYYY'), '2026-10-13T07:30:00.000Z'));
t('date without zone is UTC', () => assert.equal(date('2026-10-13 12:00', 'YYYY-MM-DD'), '2026-10-13T12:00:00.000Z'));
t('date invalid day', () => throws(() => date('2026-02-30', 'YYYY-MM-DD'), /invalid/));
t('date invalid hour', () => throws(() => date('2026-10-13 25:00', 'YYYY-MM-DD'), /invalid/));
t('unit checked: 1 mL is not 1 L', () => { assert.equal(bulkPropHas({ decimalValue: 1, unit: 'mL' }, 'decimalValue', 1, 'L'), false); assert.equal(bulkPropHas({ decimalValue: 1, unit: 'L' }, 'decimalValue', 1, 'L'), true); });
t('text compare unaffected', () => assert.equal(bulkPropHas({ stringValue: 'a ' }, 'stringValue', 'a'), true));
t('bulk integer range', () => throws(() => bulkParseValue(1, '3000000000'), /range/));
