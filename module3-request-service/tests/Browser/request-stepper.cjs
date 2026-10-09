// Run with Node and Playwright; PLAYWRIGHT_MODULE can point to an existing installation.
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const assert=require('node:assert/strict');
const path=require('node:path');
(async()=>{
 const browser=await chromium.launch({headless:true,channel:process.env.BROWSER_CHANNEL||'msedge'});
 try {
  const page=await browser.newPage();page.setDefaultTimeout(10000);
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  let pending;let requests=0;
  const fixture=`<form id="request-stepper"><nav class="uni-stepper" hidden><button type="button" data-go-step="0">Type</button><button type="button" data-go-step="1">Content</button><button type="button" data-go-step="2">Files</button><button type="button" data-go-step="3">Confirm</button></nav>
  <section data-step="0"><select id="department_id" name="department_id" required><option value="1">Department</option></select><select id="support_type_id" name="support_type_id" required><option value="">Choose</option><option value="1">Type</option></select><p id="catalog-form-status"></p></section>
  <section data-step="1"><input name="title" required minlength="10"><textarea name="content" required minlength="20"></textarea><input type="radio" name="priority" value="normal" checked><div id="catalog-form-fields"></div></section>
  <section data-step="2"><div id="attachments_block"><input id="attachments" type="file" multiple></div><div id="attachment-previews"></div></section>
  <section data-step="3"><div id="request-review"></div><button type="submit">Submit</button></section>
  <div class="uni-step-controls" hidden><button type="button" id="step-back">Back</button><button type="button" id="step-next">Next</button></div></form><script id="catalog-form-old" type="application/json">{}</script><script id="catalog-form-errors" type="application/json">{}</script>`;
  await page.route('http://form.test/**',route=>{if(route.request().url().includes('/request-forms/')){requests++;pending=route;}else return route.fulfill({contentType:'text/html',body:fixture});});
  await page.goto('http://form.test/');
  await page.addScriptTag({path:path.resolve(__dirname,'../../public/js/catalog-form.js')});
  await page.addScriptTag({path:path.resolve(__dirname,'../../public/js/request-stepper.js')});
  await page.selectOption('#support_type_id','1');
  await page.waitForFunction(()=>document.querySelector('#step-next').disabled);
  assert(await page.locator('#catalog-form-status').isVisible());
  assert.match(await page.locator('#step-next').innerText(),/Đang tải/);
  while(!pending)await new Promise(r=>setTimeout(r,10));
  await pending.fulfill({status:503,contentType:'application/json',body:'{}'});
  await page.getByRole('button',{name:'Thử lại',exact:true}).waitFor();
  assert(await page.locator('#catalog-form-status').isVisible());
  assert(!(await page.locator('#step-next').isDisabled()));
  pending=null;await page.getByRole('button',{name:'Thử lại',exact:true}).click();
  while(!pending)await new Promise(r=>setTimeout(r,10));
  await pending.fulfill({contentType:'application/json',body:JSON.stringify({data:{fields:[{field_key:'class',label:'Lớp',field_type:'text',is_required:true}]}})});
  await page.waitForFunction(()=>document.querySelector('#support_type_id').dataset.formState==='ready');
  await page.locator('#step-next').click();
  assert(await page.locator('[data-step="1"]').isVisible());
  await page.locator('input[name="title"]').fill('Xin xác nhận sinh viên');
  await page.locator('textarea').fill('Em cần giấy xác nhận để hoàn thiện hồ sơ vay vốn.');
  await page.locator('#step-next').click();assert(await page.locator('[data-step="1"]').isVisible());assert(await page.locator('[role="alert"]').isVisible());
  await page.locator('#catalog-class').fill('DH12');await page.locator('#step-next').click();assert(await page.locator('[data-step="2"]').isVisible());
  await page.locator('#step-next').click();assert(await page.locator('[data-step="3"]').isVisible());assert.match(await page.locator('#request-review').innerText(),/DH12/);
  await page.evaluate(()=>{window.sent=false;document.querySelector('form').addEventListener('submit',event=>{window.sent=!event.defaultPrevented;event.preventDefault();});});
  await page.getByRole('button',{name:'Submit',exact:true}).click();assert(await page.evaluate(()=>window.sent));assert.equal(requests,2);assert.deepEqual(errors,[]);
  console.log('PASS loading feedback, visible retry, recovery, required dynamic field validation, all four steps and submit.');
 } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
