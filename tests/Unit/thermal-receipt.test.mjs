import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../resources/js/thermal-receipt.js', import.meta.url), 'utf8');

function openReceipt({ autoPrint = false, height = 600, readyState = 'complete' } = {}) {
    const listeners = new Map();
    const styles = [];
    let printCount = 0;
    const location = { href: `https://salon.test/visits/1/receipt/80mm${autoPrint ? '?print=1' : ''}` };
    const body = { dataset: { autoPrint: String(autoPrint) } };
    runInNewContext(source, {
        URL,
        requestAnimationFrame: (callback) => callback(),
        document: {
            querySelector: () => ({ getBoundingClientRect: () => ({ height }) }),
            createElement: () => ({ textContent: '' }),
            head: { append: (style) => styles.push(style) },
            fonts: { ready: Promise.resolve() },
            readyState,
            body,
        },
        window: {
            location,
            history: { state: null, replaceState: (_state, _title, url) => { location.href = url.href; } },
            addEventListener: (event, handler) => listeners.set(event, handler),
            print: () => { printCount++; },
        },
    });
    return { listeners, styles, location, body, printCount: () => printCount };
}

// No printer or external service is contacted by these tests.
test('checkout opens printing once and removes the trigger before a refresh', async () => {
    const page = openReceipt({ autoPrint: true });
    await new Promise(setImmediate);
    assert.equal(page.printCount(), 1);
    assert.equal(new URL(page.location.href).searchParams.has('print'), false);
    assert.equal(page.body.dataset.autoPrint, 'false');
    const refreshedPage = openReceipt();
    await new Promise(setImmediate);
    assert.equal(refreshedPage.printCount(), 0);
});

test('preview does not print automatically and long receipts get enough paper', async () => {
    const page = openReceipt({ height: 4000 });
    await new Promise(setImmediate);
    assert.equal(page.printCount(), 0);
    const dimensions = page.styles[0].textContent.match(/size: (\d+)mm (\d+)mm/);
    assert.equal(Number(dimensions[1]), 80);
    assert.ok(Number(dimensions[2]) > 4000 * 25.4 / 96);
});

test('automatic printing waits for page loading to finish', async () => {
    const page = openReceipt({ autoPrint: true, readyState: 'interactive' });
    await new Promise(setImmediate);
    assert.equal(page.printCount(), 0);
    await page.listeners.get('load')();
    assert.equal(page.printCount(), 1);
});
