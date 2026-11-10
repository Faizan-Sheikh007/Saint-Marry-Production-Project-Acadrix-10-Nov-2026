"""Exercise the ACTUAL Blade JavaScript plus the application's date enhancer.

Run: python tests/Regression/browser_fee_plan.py
Requires Playwright and Chromium. Uses fixture HTML/data, not a live Laravel DB.
"""
from pathlib import Path
import json
import os
import re
import shutil
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[2]
checks = 0


def check(condition, description):
    global checks
    checks += 1
    if not condition:
        raise AssertionError(description)


def fixture_script(view, values):
    source = (ROOT / f"resources/views/fee-assignments/{view}.blade.php").read_text()
    script = source.split("<script>", 1)[1].split("</script>", 1)[0]
    lines = []
    for line in script.splitlines():
        if "@json(" in line:
            var = re.search(r"(?:const|let)\s+(\w+)\s*=", line).group(1)
            start = line.index("@json(")
            pos, depth, quote, escaped = start + 6, 1, None, False
            while depth:
                ch = line[pos]
                if quote:
                    if escaped:
                        escaped = False
                    elif ch == "\\":
                        escaped = True
                    elif ch == quote:
                        quote = None
                elif ch in "'\"":
                    quote = ch
                elif ch == "(":
                    depth += 1
                elif ch == ")":
                    depth -= 1
                pos += 1
            line = line[:start] + json.dumps(values[var]) + line[pos:]
        lines.append(line)
    return "\n".join(lines)


layout = (ROOT / "resources/views/layouts/app.blade.php").read_text()
enhancer = "(function installAcadrixDateControls()" + layout.split("(function installAcadrixDateControls()", 1)[1].split("// ── Notification bell", 1)[0]

tu = {"fee_head_id": 10, "name": "Tuition Fee", "type": "recurring", "amount": 55000}
reg = {"fee_head_id": 11, "name": "Registration Fee", "type": "one_time", "amount": 12500}
templates = {
    "1": {"items": [reg, tu], "total": 67500, "billing_cycle": "semester", "parts_per_cycle": 4, "total_cycles": 8, "cycle_label": "Semester"},
    "2": {"items": [{**tu, "amount": 60000}], "total": 60000, "billing_cycle": "semester", "parts_per_cycle": 4, "total_cycles": 8, "cycle_label": "Semester"},
}
dates = {"2": ["2027-01-15", "2027-02-16", "2027-03-17", "2027-04-18"]}
heads = [{"id": 10, "name": "Tuition Fee", "type": "recurring"}, {"id": 11, "name": "Registration Fee", "type": "one_time"}]
base = {
    "templates": templates, "programTotalCycles": 8,
    "feeHeadsForInstallments": heads, "defaultInstallmentDates": dates,
    "oldInstallmentDates": {}, "oldInstallmentFeeHeads": {}, "oldInstallmentCustomItems": {},
    "nextCycle": 2, "minimumDueDate": "2027-01-01", "chargedFeeHeadIds": [11],
    "oldInstallmentsValue": None, "hasValidationErrors": False,
}


def create_html():
    return '''<form id="fee-plan-form">
    <select name="fee_template_id" id="template-select" required><option value="1" selected>Template One</option><option value="2">Template Two</option></select>
    <div id="template-items-preview"></div><div id="template-items-hint"></div><div id="template-cycle-info"></div>
    <input id="pct-increase" type="hidden" value="0"><input id="pct-scholarship" type="number" value="0"><input id="pct-financial-aid" type="number" value="0">
    <input name="cycle_increment_amount" type="hidden" value="0">
    <input name="generate_scope" type="hidden" value="single">
    <div id="cycle-no-row"><input name="cycle_no" id="cycle-no-input" type="number" readonly value="2"></div>
    <label id="installments-label"></label><input name="installments" type="number" id="installments-input" min="1" max="4" value="4">
    <input type="hidden" name="first_due_date" id="first-due-date">
    <input type="checkbox" id="use-default-installment-dates" checked>
    <div id="installment-calendar-wrap"><div id="installment-calendar-status"></div><div id="installment-calendar"></div></div>
    <button type="button" id="reset-installment-calendar">Reload dates</button>
    <span id="sum-base"></span><span id="sum-scholarship"></span><span id="sum-financial-aid"></span><span id="sum-total"></span><span id="sum-total-label"></span>
    <button type="submit">Save</button></form>'''


def renew_html():
    return '''<form id="renew-form"><input name="cycle_no" type="hidden" value="2">
    <input id="renew-cycles-input" type="hidden" name="cycles_to_generate" value="1">
    <input id="renew-installments-input" type="number" name="installments" value="4" min="1" max="4">
    <input id="renew-first-due-date" type="hidden" name="first_due_date">
    <input id="renew-use-default-installment-dates" type="checkbox" checked>
    <div id="renew-installment-calendar"></div><div id="renew-calendar-status"></div>
    <button type="button" id="renew-reset-calendar">Reload dates</button>
    <input type="number" name="increment_amount" value="0"><input type="number" name="scholarship_amount" value="0"><input type="number" name="discount_amount" value="0"><input type="number" name="financial_aid_amount" value="0">
    <button type="submit">Save</button></form>'''


def load(page, view="create", overrides=None, body=None):
    if view == "create":
        values = dict(base)
        html = create_html() if body is None else body
    else:
        values = {"nextCycle": 2, "cycleLabel": "Semester", "minimumDueDate": "2027-01-01",
                  "defaultDates": dates, "oldDates": {}, "hasValidationErrors": False,
                  "feeHeads": heads, "templateItems": [reg, tu], "oldInstallmentFeeHeads": {},
                  "oldInstallmentCustomItems": {}, "previousIncrease": 0}
        html = renew_html()
    values.update(overrides or {})
    script = fixture_script(view, values)
    check("@json" not in script, "all Blade data replaced with explicit test fixtures")
    page.set_content("<!doctype html><html><head></head><body>" + html + "<script>" + enhancer + "</script><script>" + script + "</script></body></html>")
    page.wait_for_timeout(80)


with sync_playwright() as p:
    executable = os.environ.get("CHROMIUM_PATH") or shutil.which("chromium") or shutil.which("chromium-browser")
    browser = p.chromium.launch(**({"executable_path": executable} if executable else {}), headless=True, args=["--no-sandbox"])
    page = browser.new_page()
    errors = []
    page.on("pageerror", lambda error: errors.append(str(error)))
    load(page)
    native = 'input[type="date"].installment-date-input'
    visible = 'input[type="text"][data-date-format]'
    check(page.locator(native).count() == 4, "one semester with four native date inputs")
    check(page.locator(visible).count() == 4, "four DD/MM/YYYY mirrors")
    check(page.locator('.installment-cycle-title').inner_text() == 'Semester 2', "calendar clearly labels semester 2")
    check(page.locator('#cycle-no-input').input_value() == '2', "readonly target remains semester 2")
    check(page.locator('#cycle-no-input').evaluate('(e)=>e.readOnly'), "cycle number cannot be edited normally")
    check(page.locator(native).first.get_attribute('min') == '2027-01-01', "calendar disables months belonging to earlier semesters")
    check(page.locator(native).first.get_attribute('max') is None, "no erroneous current-year-only maximum")
    check(page.locator(visible).first.input_value() == '15/01/2027', "localized display still DD/MM/YYYY")
    check(page.locator('#first-due-date').input_value() == '2027-01-15', "hidden anchor starts with ISO value")
    check(page.evaluate("document.querySelector('.installment-date-input').type") == 'text', "old class-only selector really targets a display clone")
    check(page.evaluate("new FormData(document.querySelector('form')).getAll('installment_dates[2][]')") == dates['2'], "only correctly keyed native ISO dates are posted")
    check(page.locator('#sum-total').inner_text() == 'Rs. 55,000.00', "one-time already-charged fee is not seeded again")

    page.locator(visible).first.fill('16/01/2027')
    page.locator(visible).first.press('Tab')
    check(page.locator('#first-due-date').input_value() == '2027-01-16', "day greater than 12 is copied as ISO, not localized text")
    check(page.locator('form').evaluate('(e)=>e.checkValidity()'), "valid next-year date passes browser validity")
    page.locator('#template-select').select_option('2')
    check(page.locator('.installment-cycle-title').inner_text() == 'Semester 2', "changing templates cannot restart at semester 1")
    check(page.locator('#first-due-date').input_value() == '2027-01-16', "changing template preserves edited dates")
    check(page.locator(native).count() == 4, "template switch still creates only one cycle")
    check(page.locator('#sum-total').inner_text() == 'Rs. 60,000.00', "replacement template amounts seeded correctly")

    page.locator(visible).first.fill('31/12/2026')
    page.locator(visible).first.press('Tab')
    check(not page.locator('form').evaluate('(e)=>e.checkValidity()'), "typed earlier-semester date is invalid too")
    check('01/01/2027' in page.locator(visible).first.evaluate('(e)=>e.validationMessage'), "range message names the first allowed date")
    page.locator(visible).first.fill('16/01/2027')
    page.locator(visible).first.press('Tab')
    page.locator(visible).first.fill('31/02/2027')
    page.locator(visible).first.press('Tab')
    check(not page.locator('form').evaluate('(e)=>e.checkValidity()'), "impossible typed date rejected")
    page.locator(visible).first.fill('16/01/2027')
    page.locator(visible).first.press('Tab')
    page.locator('#installments-input').fill('2')
    check(page.locator(native).count() == 2, "changing count creates two installments, not more semesters")
    check(page.locator('#sum-total').inner_text() == 'Rs. 60,000.00', "installment re-split retains template total")

    load(page, overrides={'nextCycle': 1, 'minimumDueDate': None, 'defaultInstallmentDates': {'1': ['2026-09-16']}, 'chargedFeeHeadIds': []})
    check(page.locator('.installment-cycle-title').inner_text() == 'Semester 1', "first admission still starts semester 1")
    page.locator(visible).first.fill('16/09/2026')
    page.locator(visible).first.press('Tab')
    check(page.locator('#first-due-date').input_value() == '2026-09-16', "today's date round-trips without format error")
    check(page.locator('form').evaluate('(e)=>e.checkValidity()'), "today is valid when there are no previous plans")

    load(page, overrides={'defaultInstallmentDates': {'2': ['2026-12-15', '2027-01-20']}})
    check(page.locator(native).first.input_value() == '', "reserved default is left blank instead of reusing old semester")
    check(page.locator(native).nth(1).input_value() == '2027-01-20', "filtering default preserves second installment position")
    page.locator('#reset-installment-calendar').click()
    check(page.locator(native).first.input_value() == '', "reload saved dates cannot restore a blocked month")

    load(page, overrides={'oldInstallmentsValue': 2, 'hasValidationErrors': True,
                          'oldInstallmentDates': {'2': ['2027-03-10', '2027-04-10']},
                          'oldInstallmentFeeHeads': {'2': {'1': {'10': 11111}, '2': {'10': 22222}}}},
         body=create_html().replace('max="4" value="4"', 'max="4" value="2"'))
    check(page.locator(native).first.input_value() == '2027-03-10', "validation redirect preserves operator's date")
    check(page.locator('#sum-total').inner_text() == 'Rs. 33,333.00', "validation redirect preserves edited fee amounts")
    load(page, body='<div>Program complete. No form.</div>')
    check(page.locator('input[type="date"]').count() == 0, "completed-program screen safely has no calendar")

    load(page, view='renew')
    renew_native = 'input[type="date"].renew-installment-date-input'
    check(page.locator(renew_native).count() == 4, "renewal also renders exactly one cycle")
    check(page.locator('.installment-cycle-title').inner_text() == 'Semester 2', "renewal uses resolved next cycle")
    check(page.locator(renew_native).first.get_attribute('min') == '2027-01-01', "renewal date boundary matches create")
    page.locator(visible).first.fill('16/01/2027')
    page.locator(visible).first.press('Tab')
    check(page.locator('#renew-first-due-date').input_value() == '2027-01-16', "renewal hidden anchor remains ISO after a typed date")
    page.locator('#renew-cycles-input').evaluate("e=>{e.value='7';e.dispatchEvent(new Event('input',{bubbles:true}));}")
    check(page.locator('.installment-cycle-title').count() == 1, "renewal JS cannot render bulk cycles from changed hidden input")
    check(page.locator('#renew-cycles-input').input_value() == '1', "renewal cycle count forced to one")
    check(not errors, 'no JavaScript errors: ' + '; '.join(errors))
    browser.close()

print(f"PASS: {checks} real-Chromium fee-calendar regression checks (fixture data, no live DB).")
