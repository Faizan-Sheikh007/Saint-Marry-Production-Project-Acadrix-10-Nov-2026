"""Browser checks of static rendered view fixtures; not a live Laravel application."""
import asyncio, json, os
from pathlib import Path
from playwright.async_api import async_playwright

async def main():
    root=Path(os.environ.get('ACADRIX_QA_OUTPUT','/tmp/acadrix-student-status'))
    checks=[]
    def check(value,label):
        assert value,label
        checks.append(label)
    async with async_playwright() as p:
        browser=await p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox','--allow-file-access-from-files'])
        page=await browser.new_page(viewport={'width':1920,'height':430},device_scale_factor=1)
        errors=[]
        page.on('pageerror',lambda e:errors.append(str(e)))
        await page.set_content((root/'directory.html').read_text())
        left=page.locator('tr[data-student-id="1"]'); active=page.locator('tr[data-student-id="2"]')
        check(await left.count()==1,'left row present')
        check(await left.locator('input.student-select').is_disabled(),'left checkbox disabled')
        check(await active.locator('input.student-select').is_enabled(),'active checkbox enabled')
        check(await left.locator('td').count()==12,'left row aligned to 12 headers')
        check(await page.locator('thead th').count()==12,'student status column included')
        check('Left' in await left.inner_text(),'left label visible')
        check('Paid' in await left.inner_text(),'financial status retained separately')
        check(await left.locator('button[disabled]').count()==2,'edit and ID card disabled')
        check(await left.locator('form[data-student-reactivation] button').is_enabled(),'reactivation enabled')
        check(await left.locator('a[href*="students.show"]').count()==1,'read only view enabled')
        check(await left.locator('a[href*="students.edit"]').count()==0,'no live edit URL on locked row')
        check(await active.locator('a[href*="students.edit"]').count()==1,'active edit usable')
        check(await page.locator('[data-profile-locked]').count()==0,'directory not globally locked by last student')
        await page.screenshot(path=str(root/'directory-preview.png'),full_page=True)
        await page.set_viewport_size({'width':1280,'height':800})
        await page.set_content((root/'directory.html').read_text())
        check(await page.evaluate('document.querySelector(".table-wrap").scrollWidth >= document.querySelector(".table-wrap").clientWidth'),'narrow table scrollable')
        await page.set_viewport_size({'width':1440,'height':550})
        await page.set_content((root/'profile.html').read_text())
        check('Student status: Left' in await page.inner_text('body'),'locked banner prominent')
        check(await page.locator('form[action*="receive"] button').is_disabled(),'payment mutation disabled')
        check(await page.locator('form[action*="receive"] input').is_disabled(),'payment field disabled')
        check(await page.locator('a[href$="edit"]').get_attribute('aria-disabled')=='true','edit navigation disabled')
        check(await page.locator('a[href$="print"]').get_attribute('aria-disabled') is None,'historical printing retained')
        check(await page.locator('[data-student-reactivation] button').is_enabled(),'admin can still reactivate')
        check(await page.locator('[data-student-reactivation] input[name="_token"]').is_enabled(),'reactivation CSRF retained')
        await page.screenshot(path=str(root/'profile-preview.png'),full_page=True)
        await page.set_content((root/'account-banner.html').read_text())
        check(await page.locator('[data-student-reactivation]').count()==0,'accountant has no reactivation button')
        check(not errors,'no JavaScript runtime errors')
        await browser.close()
    result={'checks_passed':len(checks),'checks':checks,'limitations':'Static synthetic fixtures, limited Blade translator. Not an authenticated Laravel or database integration test.'}
    (root/'browser-validation.json').write_text(json.dumps(result,indent=2))
    print(f'PASS: {len(checks)} static Chromium UI checks.')

asyncio.run(main())
