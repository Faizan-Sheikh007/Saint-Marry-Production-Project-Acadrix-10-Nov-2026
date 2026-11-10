"""Static synthetic fixtures only; not a Laravel/browser session test."""
import asyncio, json, os
from pathlib import Path
from playwright.async_api import async_playwright

async def main():
    root=Path(os.environ.get('ACADRIX_QA_OUTPUT','/tmp/acadrix-report-status'))
    checks=[]
    def check(value,label):
        assert value,label
        checks.append(label)
    async with async_playwright() as p:
        browser=await p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox','--allow-file-access-from-files'])
        page=await browser.new_page(viewport={'width':1200,'height':1000})
        errors=[]
        page.on('pageerror',lambda e:errors.append(str(e)))
        await page.set_content((root/'paper.html').read_text())
        check(await page.locator('.letterhead img').count()==0,'question paper has no logo')
        check(await page.locator('.inst-name').count()==0,'question paper has no institution name block')
        check(await page.locator('.doc-name').count()==1,'one examination heading')
        check('question paper' in (await page.locator('.doc-name').inner_text()).lower(),'question-paper heading preserved')
        for label in ['Program','Session','Semester / Part','Subject','Total Marks']:
            check(label in await page.locator('.details-grid').inner_text(),f'{label} printed')
        await page.wait_for_function('document.body.classList.contains("is-paginated")')
        check(await page.locator('.paper-pages .q-list li').count()==6,'question rows retained')
        check(await page.locator('.metadata-line').count()==2,'two metadata lines only')
        check(await page.locator('.page-navigation').count()==0,'single page has no page numbers')
        check(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'),'paper has no horizontal page overflow')
        await page.screenshot(path=str(root/'paper-preview.png'),full_page=True)
        await page.pdf(path=str(root/'paper-preview.pdf'),prefer_css_page_size=True,print_background=True,display_header_footer=False)
        await page.set_viewport_size({'width':1360,'height':900})
        await page.set_content((root/'status-report.html').read_text())
        check(await page.locator('tbody tr').count()==8,'all eight status rows retained')
        for status in ['active','inactive','suspended','graduated','dropped','transferred','terminated','left']:
            cell=page.locator(f'[data-student-status="{status}"]')
            check(await cell.count()==1,f'{status} appears once')
            check(await cell.is_visible(),f'{status} visible')
            check(status.capitalize() in await cell.inner_text(),f'{status} label matches saved value')
        left=page.locator('tbody tr').filter(has=page.locator('[data-student-status="left"]'))
        check('Paid' in await left.inner_text(),'Left remains separate from Paid')
        check(await page.locator('thead th').count()==15,'fee table header alignment')
        for row in await page.locator('tbody tr').all():
            check(await row.locator('td').count()==15,'fee row column alignment')
        check(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'),'report has no horizontal page overflow')
        check(await page.locator('[data-student-status="left"]').evaluate('(e)=>e.scrollWidth<=e.clientWidth+1'),'Left status badge not clipped')
        await page.screenshot(path=str(root/'status-report-preview.png'),full_page=True)
        await page.pdf(path=str(root/'status-report-preview.pdf'),prefer_css_page_size=True,print_background=True,display_header_footer=False)
        check(not errors,'no browser JavaScript errors')
        await browser.close()
    (root/'browser-validation.json').write_text(json.dumps({'checks_passed':len(checks),'checks':checks,'limitations':'Static limited-renderer fixtures, not authenticated Laravel pages.'},indent=2))
    print(f'PASS: {len(checks)} static Chromium checks.')

if __name__=='__main__':asyncio.run(main())
