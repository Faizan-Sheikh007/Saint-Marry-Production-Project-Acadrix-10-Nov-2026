"""Test rendered credit-panel fixtures, not live Laravel routes or real student data.
First run: ACADRIX_QA_OUTPUT=/path php tests/Regression/credit_panel_fixture.php
Then: ACADRIX_QA_OUTPUT=/path python tests/Regression/browser_credit_panel.py
"""
from pathlib import Path
import os
import shutil
from playwright.sync_api import sync_playwright

out = Path(os.environ.get('ACADRIX_QA_OUTPUT', '/tmp/acadrix-credit-ui'))
count = 0

def check(condition, label):
    global count
    assert condition, label
    count += 1
    print('PASS:', label)

with sync_playwright() as p:
    executable = os.environ.get('CHROMIUM_EXECUTABLE') or shutil.which('chromium') or shutil.which('chromium-browser')
    browser = p.chromium.launch(**({'executable_path': executable} if executable else {}), headless=True, args=['--no-sandbox'])
    page = browser.new_page(viewport={'width': 570, 'height': 1200})
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    for case in ['available', 'consumed', 'no-receipt', 'older-dues', 'unicode']:
        page.set_content((out / f'credit-{case}.html').read_text())
        check(page.locator('[data-student-credit-panel]').count() == 1, f'{case}: one shared credit panel')
        check(page.locator('.student-credit-value').text_content().strip() == ('Rs. 0.00' if case == 'consumed' else 'Rs. 10,000.00'), f'{case}: precise available credit')
        check(page.locator('.credit-slip-download').count() == (0 if case == 'no-receipt' else 1), f'{case}: receipt link independent of unused credit')
        check(page.locator('[data-student-credit-panel] form').count() == (1 if case == 'older-dues' else 0), f'{case}: apply action only for remaining credit and unpaid dues')
        if case != 'no-receipt':
            check('/payments.extra-receipts.legacy/740/123' in page.locator('.credit-slip-download').get_attribute('href'), f'{case}: student print links to original historical receipt')
        else:
            check('Do not enter the payment again' in page.inner_text('[data-student-credit-panel]'), 'missing receipt does not invent a replacement payment')
        for width in [570, 390, 340]:
            page.set_viewport_size({'width': width, 'height': 1200})
            check(page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), f'{case}: page fits {width}px viewport')
            check(page.locator('[data-student-credit-panel]').evaluate('(e) => e.scrollWidth <= e.clientWidth'), f'{case}: credit panel does not overflow at {width}px')
        if case == 'unicode':
            check(page.locator('script').count() == 0, 'receipt reference rendered as escaped text, not a script')
            check('<script>bad</script>' in page.inner_text('[data-student-credit-panel]'), 'escaped test text preserved visibly')
        if case in ['available', 'consumed']:
            page.set_viewport_size({'width': 570, 'height': 1200})
            page.locator('.fixture').screenshot(path=str(out / f'credit-{case}-preview.png'))
    check(not errors, 'no JavaScript errors in the static fixtures')
    browser.close()
print(f'PASS: {count} browser fixture assertions. No Laravel HTTP requests or database calls tested.')
(out / 'credit-browser-validation.txt').write_text(f'PASS: {count} browser fixture assertions; not a Laravel/database integration test.\n')
