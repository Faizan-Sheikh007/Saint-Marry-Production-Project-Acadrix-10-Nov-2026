"""Static browser fixtures for the 2026-10-29 update. Not live Laravel integration.
Requires Python Playwright and Chromium. Set CHROMIUM_EXECUTABLE if needed.
ACADRIX_QA_OUTPUT optionally chooses where preview/test output is written.
"""
from pathlib import Path
import re,json,os,tempfile,shutil
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parents[2]
out=Path(os.environ.get('ACADRIX_QA_OUTPUT') or tempfile.mkdtemp(prefix='acadrix-qa-'))
out.mkdir(parents=True,exist_ok=True)
count=0

def check(value,label):
 global count
 assert value,label
 count+=1
 print('PASS:',label)

with sync_playwright() as p:
 executable=os.environ.get('CHROMIUM_EXECUTABLE') or shutil.which('chromium') or shutil.which('chromium-browser')
 browser=p.chromium.launch(**({'executable_path':executable} if executable else {}),headless=True,args=['--no-sandbox'])
 page=browser.new_page(viewport={'width':1100,'height':900})
 errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 source=(root/'resources/views/lms/quizzes/form.blade.php').read_text()
 script=re.search(r'<script>(.*?)</script>',source,re.S).group(1)
 script=re.sub(r'@php.*?@endphp','',script,flags=re.S)
 questions=[{'question':'Which option is correct?','type':'mcq','marks':2,'options':['A','B'],'correct_answer':'B'}, {'question':'True or false?','type':'true_false','marks':3,'options':[],'correct_answer':'true'}]
 script=script.replace('@json($initialQuizQuestions)',json.dumps(questions))
 template=re.search(r'<template.*?</template>',source,re.S).group()
 html='<form id="quizForm"><input id="totalMarksField"><div id="questionsWrap"></div></form>'+template+'<script>'+script+'</script>'
 page.goto('about:blank');page.set_content(html)
 check(page.locator('#questionsWrap .q-block').count()==2,'edit form restores saved questions')
 check(page.locator('#totalMarksField').input_value()=='5','restored marks sum correctly')
 check(page.locator('.q-options').nth(1).evaluate('(e)=>e.style.display')=='none','true/false hides MCQ options')
 page.evaluate('addQuestion()');check(page.locator('#questionsWrap .q-block').count()==3,'Add Question inserts a question')
 page.locator('.q-marks').nth(2).fill('4');check(page.locator('#totalMarksField').input_value()=='9','changing marks recomputes total')
 page.locator('.q-block').nth(0).locator('button').click();check(page.locator('.q-index').all_text_contents()==['1','2'],'removing a question renumbers remaining questions')
 page.locator('.q-question').nth(1).fill('New question');page.locator('.q-answer').nth(1).fill('Yes')
 page.locator('.q-options').nth(1).locator('input').nth(0).fill('Yes');page.locator('.q-options').nth(1).locator('input').nth(1).fill('No')
 page.evaluate("document.getElementById('quizForm').addEventListener('submit',e=>e.preventDefault()); document.getElementById('quizForm').dispatchEvent(new Event('submit',{cancelable:true}))")
 inputs=page.locator('[data-quiz-generated]').count()
 check(inputs==10,'serializes two questions and two MCQ options')
 page.evaluate("document.getElementById('quizForm').dispatchEvent(new Event('submit',{cancelable:true}))")
 check(page.locator('[data-quiz-generated]').count()==inputs,'repeat submit does not duplicate generated payload fields')
 check(page.locator('[name="questions[0][type]"]').input_value()=='true_false','serialized question types stay aligned')
 # Real timer JS with controlled server remaining time (not full Laravel rendering).
 source=(root/'resources/views/lms/quizzes/take.blade.php').read_text()
 timer=re.search(r'<script>(.*?)</script>',source,re.S).group(1).replace('{{ $remainingSeconds }}','1')
 page.goto('about:blank');page.set_content('<span id="timerText"></span><form id="quizTakeForm"></form><script>window.submitted=0;HTMLFormElement.prototype.submit=function(){window.submitted++}</script><script>'+timer+'</script>')
 check(page.locator('#timerText').text_content()=='0:01','timer begins at server remaining time')
 page.wait_for_timeout(1250)
 check(page.evaluate('window.submitted')==1,'timer submits exactly once on expiry')
 timer=timer.replace('let remaining = 1','let remaining = 0')
 page.goto('about:blank');page.set_content('<span id="timerText"></span><form id="quizTakeForm"></form><script>window.submitted=0;HTMLFormElement.prototype.submit=function(){window.submitted++}</script><script>'+timer+'</script>')
 page.wait_for_timeout(1100)
 check(page.evaluate('window.submitted')==1,'already expired attempt submits once, without starting a second timer')
 # Real Admin teacher/course filter JavaScript.
 source=(root/'resources/views/admin/activities/index.blade.php').read_text()
 script=re.search(r'<script>(.*?)</script>',source,re.S).group(1).replace("@json(old('activity_tab'))",'null')
 page.goto('about:blank');page.set_content('''<form data-learning-request><select data-task-teacher><option value="">Teacher</option><option value="1">T1</option><option value="2">T2</option></select><select data-task-course><option value="">Subject</option><option value="11" data-teacher="1">S1</option><option value="22" data-teacher="2">S2</option></select></form><script>'''+script+'</script>')
 check(page.locator('[data-task-course] option:disabled').count()==2,'no teacher selected: course choices disabled')
 page.locator('[data-task-teacher]').select_option('1');check(page.locator('[data-task-course] option[value="11"]').is_enabled(),'teacher 1 subject enabled')
 check(page.locator('[data-task-course] option[value="22"]').is_disabled(),'other teacher subject remains disabled')
 page.locator('[data-task-course]').select_option('11');page.locator('[data-task-teacher]').select_option('2')
 check(page.locator('[data-task-course]').input_value()=='','changing teacher clears previous incompatible subject')
 check(page.locator('[data-task-course] option[value="22"]').is_enabled(),'teacher 2 subject enabled after switch')
 check(not errors,'no JavaScript runtime errors in tested fixtures')
 # Header preview using actual template CSS and actual header markup with fixture values.
 paper=(root/'resources/views/exam-papers/print.blade.php').read_text();css=re.search(r'<style>(.*?)</style>',paper,re.S).group(1)
 header=paper[paper.index('<div class="sheet">'):paper.index('  @if($examPaper->instructions)')]
 header=re.sub(r'@if\(\$logoPath\).*?@endif','',header,flags=re.S)
 values={"$instName":"SAINT MARY’S COLLEGE OF MEDICAL & ADVANCE STUDIES", "$exam->name ?? 'Examination'":"MID TERM EXAM FALL 2026", "$exam->program->name ?? $exam->schoolClass->program->name ?? 'Not assigned'":"BS (Hons.) Human Nutrition and Dietetics", "$exam->session_label":"2023–2027", "$exam->schoolClass->section_label ?? '—'":"Semester 5", "$exam->subject->name ?? '—'":"Micronutrients in Human Nutrition", "$examPaper->teacher->name ?? '—'":"Fatima Shahid", "optional($exam->exam_date)->format('d M, Y') ?? '—'":"02 Dec, 2026", "$exam->time_label ?? '—'":"10:00 AM – 12:30 PM", "$exam->total_marks ?? '—'":"50"}
 import html as h
 header=re.sub(r'{{\s*(.*?)\s*}}',lambda m:h.escape(values[m.group(1)]),header)
 body=''
 for title,marks,qs in [('Section A — Short Questions','10',['Define food and nutrition security and identify its major dimensions.','Differentiate between protein-energy malnutrition and hidden hunger.','Explain two major causes of worldwide post-harvest food losses.','How do per-capita income and purchasing power influence food consumption?','State two ways in which WTO trade regulations may affect food availability and prices.']),('Section B — Long Questions','20',['Discuss the current global food situation and the major challenges to food and nutrition security.','Explain the contribution of cereals, legumes, roots, tubers, and animal-source foods to global food and nutrition security.']),('Section C — Multiple Choice Questions','20',['Which of the following is a major component of food security?','The Green Revolution primarily involved the introduction of:'])]:
  body+=f'<div class="section-title"><span>{title}</span><span>{marks}</span></div><ul class="q-list">'
  for i,q in enumerate(qs,1):body+=f'<li><span class="qnum">{i}.</span><span class="qtext">{h.escape(q)}</span><span class="qmarks">[{2 if "Short" in title else 10 if "Long" in title else 2}]</span></li>'
  body+='</ul>'
 html='<!doctype html><html><head><meta charset="utf-8"><style>'+css+'</style></head><body>'+header+body+'</div></div></body></html>'
 (out/'paper-layout-fixture.html').write_text(html)
 page.set_viewport_size({'width':1000,'height':1200});page.goto('about:blank');page.set_content(html)
 check(page.locator('.doc-name').count()==1,'paper preview has one visible exam heading')
 check('Main Campus' not in page.inner_text('body'),'paper preview omits Main Campus')
 check('2023–2027' in page.inner_text('.details-grid'),'paper preview displays session')
 check('BS (Hons.)' in page.inner_text('.details-grid'),'paper preview displays program')
 check(not page.locator('.sign-block,.foot-row').count(),'paper preview has no signature area')
 check(page.locator('.sheet').evaluate('(e)=>e.scrollWidth<=e.clientWidth'),'paper preview has no horizontal overflow')
 page.locator('.sheet').screenshot(path=str(out/'paper-layout-preview.png'))
 browser.close()
print(f'PASS: {count} browser fixture assertions. Fixtures exercise real inline JS/template CSS, not Laravel routes or live DB data.')
(out/'browser-checks.txt').write_text(f'PASS: {count} browser fixture assertions. No JavaScript runtime errors.\nFixtures are static and do not establish Laravel integration correctness.\n')
