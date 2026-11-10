"""Static browser/PDF tests of the actual paired print views rendered by the fixture.
This does NOT exercise Laravel HTTP, Blade, authentication or the database.
First run receipt_office_fixture.php with the same ACADRIX_QA_OUTPUT directory.
"""
from pathlib import Path
import os, re, shutil
import fitz
from playwright.sync_api import sync_playwright

out = Path(os.environ.get('ACADRIX_QA_OUTPUT', '/tmp/acadrix-receipt-office'))
count = 0
results = []

def check(value, label):
    global count
    assert value, label
    count += 1

with sync_playwright() as p:
    browser = p.chromium.launch(executable_path=shutil.which('chromium'), headless=True, args=['--no-sandbox'])
    page = browser.new_page(viewport={'width': 1500, 'height': 1000})
    errors = []
    page.on('pageerror', lambda e: errors.append(str(e)))
    page.set_content((out / 'history-preview.html').read_text(), wait_until='load')
    for action in ['Print All 3 Copies']:
        links = page.get_by_role('link', name=action, exact=True)
        check(links.count() == 4, action + ' retained for both documents in both history tables')
        check(all(x == '_blank' for x in links.evaluate_all('(els)=>els.map(e=>e.target)')), action + ' opens original record in a tab')
    check(page.get_by_text('one A4 landscape page', exact=True).count() == 1, 'history explains combined printing')
    page.screenshot(path=str(out / 'history-preview.png'), full_page=True)

    for entry in ['student', 'office']:
        for case in ['preview', 'standard', 'fractional', 'legacy', 'unicode', 'long', 'long-declaration', 'sparse']:
            label = f'{entry}/{case}'
            page.set_content((out / f'{entry}-{case}.html').read_text(), wait_until='load')
            page.evaluate('document.fonts.ready')
            check(page.locator('.receipt').count() == 3, label + ': exactly three copies')
            check(page.locator('.copy-tag').all_text_contents() == ['Student Copy', 'Bank Copy', 'Office Copy — Office Use Only'], label + ': student, bank, office order')
            check(page.locator('.r-receipt-no').all_text_contents() == ['PAID', 'PAID', 'PAID'], label + ': all copies clearly paid')
            check(page.locator('#print-payment-sheet').count() == 1, label + ': one combined print action')
            check(page.locator('form').count() == 0, label + ': no payment submit form')
            check(page.locator('.print-measure').count() == 0, label + ': measurement clone removed')
            check(page.locator('[data-copy="office"] .student-login').count() == 0, label + ': office copy excludes login')
            check(page.locator('[data-copy="student"] .student-login').count() == (0 if case == 'sparse' else 1), label + ': optional login only on student copy')
            check(page.locator('.legacy-note').count() == (3 if case == 'legacy' else 0), label + ': legacy limitations on all copies')
            check(page.locator('img').evaluate_all('(els)=>els.every(e=>e.complete&&e.naturalWidth>0)'), label + ': logos load')
            amount = 'PKR 50,000.00' if case in ['standard', 'legacy'] else ('PKR 5.25' if case == 'fractional' else 'PKR 5.00')
            check(page.locator('.r-total-box span:last-child').all_text_contents() == [amount, amount, amount], label + ': original amount, not unused credit')
            if case == 'unicode':
                check(page.evaluate('typeof window.__injected') == 'undefined', label + ': escaped user input does not execute')
                check(page.get_by_text('علی احمد', exact=True).count() == 3, label + ': Unicode name on all copies')
            for width in [1500, 900, 390, 320]:
                page.set_viewport_size({'width': width, 'height': 1000})
                check(page.evaluate('document.documentElement.scrollWidth<=window.innerWidth'), label + f': screen fits {width}px')
                check(page.locator('.receipt').evaluate_all('(els)=>els.every(e=>e.scrollWidth<=e.clientWidth+1)'), label + f': no horizontal clipping at {width}px')
            page.set_viewport_size({'width': 1200, 'height': 1100})
            if case == 'preview':
                page.screenshot(path=str(out / f'{entry}-screen.png'), full_page=True)
            page.emulate_media(media='print')
            page.evaluate("window.dispatchEvent(new Event('beforeprint'))")
            check(not page.locator('.voucher-toolbar').is_visible(), label + ': toolbar excluded from paper')
            check(page.locator('.receipts-row').evaluate('(e)=>getComputedStyle(e).flexDirection') == 'row', label + ': side-by-side in print')
            scale = float(page.locator('#extra-payment-sheet').evaluate('(e)=>e.style.getPropertyValue("--extra-print-scale")'))
            check(0 < scale <= 1, label + ': valid fit-to-page scale')
            pdf_path = out / f'{entry}-{case}.pdf'
            page.pdf(path=str(pdf_path), prefer_css_page_size=True, print_background=True, display_header_footer=False)
            with fitz.open(pdf_path) as doc:
                check(len(doc) == 1, label + f': ONE physical page, got {len(doc)}')
                pg = doc[0]
                check(abs(pg.rect.width - 841.89) < 2 and abs(pg.rect.height - 595.28) < 2, label + ': A4 landscape dimensions')
                text = pg.get_text()
                check('STUDENT COPY' in text.upper() and 'BANK COPY' in text.upper() and 'OFFICE COPY' in text.upper(), label + ': all three labels in same PDF page')
                check(text.count(amount) == 6, label + ': all original amount rows and totals retained')
                if case != 'sparse':
                    check(text.count('PREVIEW / NOT ISSUED') == 3, label + ': all examples marked unissued')
                if case == 'long':
                    check(len(re.findall(r'A\s+long\s+payment\s+note', text)) == 210, label + ': every long note retained on all three copies')
                    check(text.count('disappearing.') == 210, label + ': end of each note retained')
                if case == 'long-declaration':
                    check(len(re.findall(r'PREVIEW\s+DECLARATION:', text)) == 42, label + ': declaration not truncated')
                words = pg.get_text('words')
                check(not any(w[0] < 0 or w[1] < 0 or w[2] > pg.rect.width + 1 or w[3] > pg.rect.height + 1 for w in words), label + ': all PDF text within page bounds')
                if case == 'preview':
                    pg.get_pixmap(matrix=fitz.Matrix(1.5, 1.5)).save(str(out / f'{entry}-pdf-render.png'))
            results.append(f'{label}: 1 A4 landscape page; three copies; fit scale={scale:.4f}')
            page.emulate_media(media='screen')

    # Paid transaction receipts are the ONLY two-copy payment-collection document.
    for sample, expected, forbidden in [
        ('received-paid-preview', ['STUDENT COPY', 'OFFICE COPY'], 'BANK COPY'),
        ('fee-summary-preview', ['STUDENT COPY', 'BANK COPY', 'OFFICE COPY'], 'NOT_A_REAL_COPY_LABEL'),
    ]:
        page.set_viewport_size({'width': 1500, 'height': 1000})
        page.set_content((out / f'{sample}.html').read_text(), wait_until='load')
        actual = page.locator('.copy-label, .copy-badge').all_text_contents()
        check([label.upper() for label in actual] == expected, sample + ': correct copy labels and order')
        page.emulate_media(media='print')
        css_grid = page.locator('.paper-sheet, .copies').first.evaluate('(e)=>getComputedStyle(e).gridTemplateColumns')
        check(len(css_grid.split('px')) - 1 == len(expected), sample + ': print uses correct number of columns')
        pdf_path = out / f'{sample}-test.pdf'
        page.pdf(path=str(pdf_path), prefer_css_page_size=True, print_background=True, display_header_footer=False)
        with fitz.open(pdf_path) as doc:
            check(len(doc) == 1, sample + ': normal sample fits one A4 page')
            text = doc[0].get_text().upper()
            check(all(x in text for x in expected), sample + ': all copy labels survive PDF rendering')
            check(forbidden not in text, sample + ': no extra copy labels')
            if sample == 'received-paid-preview':
                check(text.count('RS. 25,200.00') == 4, 'paid receiving document uses original amount in each of two copies')
                check(text.count('RS. 22,000.00') == 2 and text.count('RS. 3,000.00') == 2 and text.count('RS. 200.00') == 2, 'receipt voucher allocations printed once per copy')
            else:
                check(text.count('PKR 45,000') == 3, 'fee summary repeats exact fee plan on three copies')
            words=doc[0].get_text('words')
            check(not any(w[0] < 0 or w[1] < 0 or w[2] > doc[0].rect.width + 1 or w[3] > doc[0].rect.height + 1 for w in words), sample + ': PDF text stays inside page')
        page.emulate_media(media='screen')

    # Explicitly simulate print media at a narrow viewport; not a real browser print-dialog test.
    page.emulate_media(media='print')
    page.set_viewport_size({'width': 390, 'height': 844})
    page.set_content((out / 'student-long-declaration.html').read_text(), wait_until='load')
    page.pdf(path=str(out / 'mobile-print.pdf'), prefer_css_page_size=True, print_background=True, display_header_footer=False)
    with fitz.open(out / 'mobile-print.pdf') as doc:
        check(len(doc) == 1 and doc[0].rect.width > doc[0].rect.height, 'printing from narrow viewport still one landscape page')
        check(len(re.findall(r'PREVIEW\s+DECLARATION:', doc[0].get_text())) == 42, 'mobile print retains all three declarations')
    check(not errors, 'no JavaScript errors')
    browser.close()

result = f'PASS: {count} static browser/PDF assertions. NOT Laravel/HTTP/database integration.\n' + '\n'.join(results) + '\n'
(out / 'browser-validation.txt').write_text(result)
print(result)
