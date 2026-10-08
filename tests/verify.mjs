import fs from 'node:fs';
import path from 'node:path';
import {spawn,spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';
import {openBrowser} from '../tools/browser.mjs';
const root=path.dirname(path.dirname(fileURLToPath(import.meta.url))),results=`${root}/tests/results`;
fs.mkdirSync(results,{recursive:true});
const dbName=`money_test_${Date.now()}`;
const env={...Object.fromEntries(Object.entries(process.env).map(([k,v])=>[k.toUpperCase(),v])),MONEY_DB_NAME:dbName,MONEY_URL:'http://127.0.0.1:8086',MONEY_STORAGE:`C:/dev/money-runtime/${dbName}`};
const install=spawnSync('C:/xampp/php/php.exe',[`${root}/tools/install.php`],{env,encoding:'utf8',windowsHide:true});
if(install.status!==0)throw new Error(install.stderr||install.stdout);
const server=spawn('C:/xampp/php/php.exe',['-d','upload_max_filesize=10M','-d','post_max_size=12M','-S','127.0.0.1:8086','router.php'],{cwd:root,env,windowsHide:true,stdio:['ignore',fs.openSync(`${results}/server.log`,'w'),fs.openSync(`${results}/server-errors.log`,'w')]});
const base='http://127.0.0.1:8086/index.php?r=';
const browser=await openBrowser();
let checks=0;const errors=[];
const ok=(value,message)=>{assert.ok(value,message);checks++;console.log(`PASS ${message}`)};
const route=r=>base+r;
async function context(){return browser.newContext({viewport:{width:1440,height:960}})}
async function get(ctx,r){const res=await ctx.request.get(route(r));return {res,html:await res.text()}}
async function token(ctx,r='dashboard'){const {html}=await get(ctx,r);const match=html.match(/name="_token" value="([a-f0-9]+)"/);assert.ok(match,'CSRF token on '+r);return match[1]}
async function post(ctx,r,form={},from='dashboard'){const res=await ctx.request.post(route(r),{form:{_token:await token(ctx,from),...form}});return {res,html:await res.text()}}
const today=new Date().toISOString().slice(0,10),month=today.slice(0,7),password='MoneyTestPassword!26';
try {
 for(let n=0;n<30;n++){try{await fetch(route('home'));break}catch{await new Promise(r=>setTimeout(r,200))}}
 const demoEnv={...env,MONEY_DEMO_PASSWORD:'123'};
 const seed=spawnSync('C:/xampp/php/php.exe',[`${root}/tools/seed-demo.php`],{env:demoEnv,encoding:'utf8',windowsHide:true});
 ok(seed.status===0&&JSON.parse(seed.stdout).password==='123','Demo seed creates the documented example password');
 const demo=await context();
 let demoResult=await post(demo,'login',{email:'demo@money.local',password:'123'},'login');
 ok(demoResult.res.url().includes('r=dashboard')&&demoResult.html.includes('Forma Studio'),'Demo sign-in works with password 123');
 const repeatedSeed=spawnSync('C:/xampp/php/php.exe',[`${root}/tools/seed-demo.php`],{env:demoEnv,encoding:'utf8',windowsHide:true});
 ok(repeatedSeed.status===0&&JSON.parse(repeatedSeed.stdout).existing,'Repeated demo seed preserves existing records');
 const resetDemo=spawnSync('C:/xampp/php/php.exe',[`${root}/tools/seed-demo.php`,'--reset-password'],{env:demoEnv,encoding:'utf8',windowsHide:true});
 ok(resetDemo.status===0&&JSON.parse(resetDemo.stdout).password_reset,'Existing demo password can be reset explicitly');
 demoResult=await get(demo,'dashboard');ok(demoResult.res.url().includes('r=login'),'Demo password reset invalidates existing sessions');
 demoResult=await post(demo,'login',{email:'demo@money.local',password:'123'},'login');ok(demoResult.res.url().includes('r=dashboard'),'Demo signs in after password reset');
 await demo.close();
 const a=await context(),b=await context(),employee=await context();
 let result=await post(a,'register',{name:'Test Owner',email:'owner@example.test',company:'Test Studio',currency:'EUR',password,password_confirmation:password},'register');
 ok(result.res.url().includes('r=dashboard'),'Registration creates workspace and signs in');
 ok(result.html.includes('EUR 0.00'),'New workspace starts with zero balances');
 result=await a.request.post(route('entry&type=expense'),{form:{description:'Invalid'}});ok(result.status()===419,'CSRF required for writes');
 result=await get(b,'dashboard');ok(result.res.url().includes('r=login'),'Private routes require authentication');
 result=await post(a,'contacts',{name:'Example Customer',email:'customer@example.test',registration_number:'123',address:'10 High Street'});ok(result.html.includes('Contact saved.'),'Contact saved');
 const contactId=result.html.match(/r=contacts&amp;id=(\d+)/)[1];
 result=await post(a,'entry&type=expense',{description:'Office supplies',amount:'123.45',tax_amount:'21.42',entry_date:today,paid_on:today,status:'paid',reference:'EXP-001',contact_id:contactId});
 const expenseId=new URL(result.res.url()).searchParams.get('id');ok(expenseId&&result.html.includes('Expense saved.'),'Expense persisted with exact decimal amount');
 result=await post(a,'entry&type=income',{description:'Consulting payment',amount:'1000.10',tax_amount:'0',entry_date:today,paid_on:today,status:'paid',reference:'INC-001'});
 const incomeId=new URL(result.res.url()).searchParams.get('id');ok(incomeId&&result.html.includes('Income saved.'),'Income saved independently');
 result=await get(a,'expenses');ok(result.html.includes('Office supplies')&&!result.html.includes('Consulting payment'),'Expenses excludes income');
 result=await get(a,'income');ok(result.html.includes('Consulting payment')&&!result.html.includes('Office supplies'),'Income excludes expenses');
 result=await get(a,'dashboard');ok(result.html.includes('EUR 876.65'),'Dashboard computes paid income minus paid expenses');
 result=await post(a,'entry&type=expense',{description:'Pending review',amount:'55.00',tax_amount:'0',entry_date:today,status:'pending'});
 const pendingId=new URL(result.res.url()).searchParams.get('id');
 result=await get(a,'dashboard');ok(result.html.includes('EUR 876.65'),'Unpaid entries excluded from cash-flow balance');
 result=await post(a,'entry-action',{id:pendingId,action:'paid',paid_on:today});ok(result.res.status()===422,'Expense cannot be paid before approval');
 result=await post(a,'entry-action',{id:pendingId,action:'reject',review_note:'Please attach receipt'});ok(result.html.includes('Please attach receipt')&&result.html.includes('Rejected'),'Expense can be rejected with a reason');
 result=await post(a,'entry&id='+pendingId,{description:'Pending review corrected',amount:'55.00',tax_amount:'0',entry_date:today,status:'pending'});ok(result.html.includes('Expense saved.'),'Rejected expense can be corrected and resubmitted');
 result=await post(a,'entry-action',{id:pendingId,action:'approve'});ok(result.html.includes('Approved'),'Approval workflow works');
 result=await post(a,'entry&id='+expenseId,{description:'Tamper',amount:'1',tax_amount:'0',entry_date:today,status:'draft'});ok(result.res.status()===422,'Paid entries locked against edits');
 result=await post(a,'entry&type=expense',{description:'Bad amount',amount:'1.005',tax_amount:'0',entry_date:today,status:'draft'});ok(result.res.status()===422,'Excess monetary precision rejected');
 result=await post(a,'entry&type=expense',{description:'Bad tax',amount:'10.00',tax_amount:'11.00',entry_date:today,status:'draft'});ok(result.res.status()===422,'Tax cannot exceed total');
 const upload=await a.request.post(route('documents'),{multipart:{_token:await token(a),entry_id:expenseId,document:{name:'receipt.png',mimeType:'image/png',buffer:Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=','base64')}}});
 const uploadHtml=await upload.text();const documentId=uploadHtml.match(/r=document-download&amp;id=(\d+)/)?.[1];ok(documentId&&uploadHtml.includes('Document uploaded.'),'Valid document upload and linking');
 let response=await a.request.get(route('document-download&id='+documentId));ok(response.status()===200&&response.headers()['content-type']==='image/png','Authenticated document download');
 response=await a.request.post(route('documents'),{multipart:{_token:await token(a),document:{name:'shell.php',mimeType:'application/x-php',buffer:Buffer.from('<?php echo "unsafe";')}}});ok(response.status()===422,'Executable uploads rejected by MIME');
 response=await a.request.get('http://127.0.0.1:8086/storage/uploads/receipt.png');ok(response.status()===404,'Private storage not web-accessible');
 result=await post(b,'register',{name:'Another Owner',email:'other@example.test',company:'Other Studio',currency:'USD',password,password_confirmation:password},'register');ok(result.res.url().includes('r=dashboard'),'Second workspace created');
 result=await get(b,'entry&id='+expenseId);ok(result.res.status()===404,'Cross-workspace entry access denied');
 result=await get(b,'document-download&id='+documentId);ok(result.res.status()===404,'Cross-workspace document access denied');
 result=await post(b,'entry&type=expense',{description:'Foreign contact',amount:'10',tax_amount:'0',entry_date:today,status:'draft',contact_id:contactId});ok(result.res.status()===404,'Cross-workspace contact references denied');
 result=await post(a,'invoice',{contact_id:contactId,number:'INV-TEST-001',issue_date:today,due_date:today,'item_description[0]':'Design work','quantity[0]':'2','unit_price[0]':'50.00','tax_rate[0]':'21',notes:'Thank you'});
 const invoiceId=new URL(result.res.url()).searchParams.get('id');ok(invoiceId&&result.html.includes('EUR 121.00'),'Invoice totals calculated server-side including tax');
 result=await post(a,'invoice-action',{id:invoiceId,action:'paid',paid_on:today});ok(result.res.status()===422,'Draft invoice cannot be paid');
 result=await post(a,'invoice-action',{id:invoiceId,action:'sent'});ok(result.html.includes('Sent'),'Invoice marked sent');
 result=await post(a,'invoice-action',{id:invoiceId,action:'paid',paid_on:today});ok(result.html.includes('Payment recorded in Income.'),'Invoice payment creates income');
 result=await post(a,'invoice-action',{id:invoiceId,action:'paid',paid_on:today});ok(result.res.status()===422,'Duplicate invoice payment rejected');
 result=await get(a,'dashboard');ok(result.html.includes('EUR 997.65'),'Invoice included once in cash-flow balance');
 result=await get(a,'invoice-print&id='+invoiceId);ok(result.html.includes('INV-TEST-001')&&result.html.includes('EUR 121.00'),'Printable invoice available');
 result=await get(a,'export&month='+month);ok(result.res.headers()['content-type'].startsWith('text/csv')&&result.html.includes('123.45'),'Accounting CSV export uses stored amounts');
 const csv=Buffer.from(`date,description,amount,reference\n${today},Office debit,-123.45,BANK-001\n${today},Consulting credit,1000.10,BANK-002\n`);
 response=await a.request.post(route('bank-import'),{multipart:{_token:await token(a),statement:{name:'bank.csv',mimeType:'text/csv',buffer:csv}}});let html=await response.text();ok(html.includes('2 transactions imported'),'Bank CSV import');
 const transactionIds=[...html.matchAll(/name="id" value="(\d+)"/g)].map(m=>m[1]);
 response=await a.request.post(route('bank-import'),{multipart:{_token:await token(a),statement:{name:'bank.csv',mimeType:'text/csv',buffer:csv}}});ok((await response.text()).includes('0 transactions imported'),'Bank import deduplicates references');
 const bankId=transactionIds[1];
 result=await post(a,'bank-match',{id:bankId,entry_id:incomeId,action:'match'});ok(result.res.status()===422,'Reconciliation rejects mismatched direction or amount');
 result=await post(a,'bank-match',{id:bankId,entry_id:expenseId,action:'match'});ok(result.html.includes('Reconciliation updated.'),'Matching transaction reconciled');
 result=await post(a,'team',{action:'invite',email:'employee@example.test',role:'member'});const link=result.html.match(/class="share-link"[^>]*value="([^"]+)"/)?.[1].replaceAll('&amp;','&');ok(link,'Team invitation created');
 const inviteToken=new URL(link).searchParams.get('token');
 result=await post(employee,'invite&token='+inviteToken,{name:'Team Member',password,password_confirmation:password,token:inviteToken},'invite&token='+inviteToken);ok(result.res.url().includes('r=dashboard'),'Invite creates member account');
 result=await get(employee,'income');ok(result.res.status()===403,'Member cannot access company income');
 result=await get(employee,'entry&id='+expenseId);ok(result.res.status()===403,'Member cannot access another user expense');
 result=await get(employee,'documents');ok(!result.html.includes('receipt.png'),'Member document list is private');
 result=await post(employee,'entry&type=expense',{description:'My train ticket',amount:'24.80',tax_amount:'0',entry_date:today,status:'pending'});const memberExpense=new URL(result.res.url()).searchParams.get('id');ok(memberExpense,'Member can submit their own expense');
 result=await post(employee,'entry-action',{id:memberExpense,action:'approve'});ok(result.res.status()===403,'Member cannot self-approve');
 result=await get(a,'approvals');ok(result.html.includes('My train ticket'),'Owner can review member submission');
 result=await get(employee,'invite&token='+inviteToken);ok(result.res.status()===410,'Invitation is single-use');
 result=await post(a,'settings',{action:'category',type:'income',name:'Royalties'});ok(result.html.includes('Royalties'),'Custom categories saved');
 result=await post(a,'settings',{action:'workspace',name:'Renamed Studio',registration_number:'123',address:'New address',monthly_budget:'2500.00'});ok(result.html.includes('Renamed Studio'),'Company settings saved');
 result=await post(a,'settings',{action:'new-workspace',name:'Second Company',currency:'GBP'});ok(result.html.includes('Second Company'),'Multiple workspaces supported');
 result=await get(a,'income');ok(!result.html.includes('Consulting payment'),'Workspace switch isolates finances');
 const companyOptions=[...result.html.matchAll(/<option value="(\d+)"[^>]*>([^<]+)<\/option>/g)];const originalWorkspace=companyOptions.find(x=>x[2]==='Renamed Studio')[1];
 result=await post(a,'switch-workspace',{workspace_id:originalWorkspace});ok(result.html.includes('EUR 997.65'),'Switch back restores original workspace balances');
 result=await get(a,'invoice-print&id='+invoiceId);ok(result.html.includes('Test Studio')&&!result.html.includes('Renamed Studio'),'Invoice seller snapshot survives company changes');
 result=await post(a,'document-delete',{id:documentId});ok(result.res.status()===422,'Finalized expense documents cannot be deleted');
 result=await post(a,'entry&type=expense',{description:'<img src=x onerror=alert(1)>',amount:'1.00',tax_amount:'0',entry_date:today,status:'draft'});const draftId=new URL(result.res.url()).searchParams.get('id');ok(result.html.includes('&lt;img src=x onerror=alert(1)&gt;')&&!result.html.includes('<img src=x'),'Stored descriptions are HTML-escaped');
 result=await post(a,'entry-action',{id:draftId,action:'delete'});ok(result.res.url().includes('r=expenses'),'Draft deletion works');
 result=await get(a,'entry&id='+draftId);ok(result.res.status()===404,'Deleted draft is no longer accessible');
 result=await get(a,'team');const memberId=result.html.match(/Team Member[\s\S]*?name="user_id" value="(\d+)"/)[1];
 result=await post(a,'team',{action:'remove',user_id:memberId});ok(result.html.includes('Team updated.'),'Owner can revoke member access');
 result=await get(employee,'dashboard');ok(result.res.url().includes('r=login')&&result.html.includes('workspace access has ended'),'Removed member is signed out without a redirect loop');
 const page=await a.newPage();page.on('pageerror',error=>errors.push(error.message));
 const screens=['dashboard','income','expenses','entry&id='+expenseId,'documents','approvals','invoices','invoice&id='+invoiceId,'invoice','reports','contacts','reconciliation','settings','team','integrations','activity'];
 for(const width of [320,390,768,1440,1920]){
   await page.setViewportSize({width,height:width<600?844:1000});
   for(const screen of screens){await page.goto(route(screen));const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);ok(!overflow,`${screen} fits ${width}px`);if(width===390||width===1440){const name=screen.replace(/[^a-z0-9]/gi,'-');await page.screenshot({path:`${results}/${name}-${width}.png`,fullPage:true})}}
 }
 await page.setViewportSize({width:390,height:844});await page.goto(route('dashboard'));await page.getByRole('button',{name:'Open navigation',exact:true}).click();ok(await page.locator('body').evaluate(el=>el.classList.contains('menu-open')),'Mobile navigation opens');await page.locator('[data-close-menu]').click({position:{x:350,y:400}});ok(!await page.locator('body').evaluate(el=>el.classList.contains('menu-open')),'Mobile navigation closes');
 await page.setViewportSize({width:1440,height:960});await page.goto(route('dashboard'));const pixels=await page.locator('canvas').evaluate(c=>{const data=c.getContext('2d').getImageData(0,0,c.width,c.height).data;let count=0;for(let i=3;i<data.length;i+=4)if(data[i])count++;return count});ok(pixels>5000,'Cash-flow canvas is nonblank');
 await page.goto(route('invoice'));await page.locator('[data-add-item]').click();ok(await page.locator('.invoice-item').count()===2,'Invoice line items can be added');await page.locator('[data-remove-item]').last().click();ok(await page.locator('.invoice-item').count()===1,'Invoice line items can be removed');
 const publicContext=await context(),pub=await publicContext.newPage();pub.on('pageerror',error=>errors.push(error.message));
 for(const width of [320,390,768,1440,1920]){await pub.setViewportSize({width,height:width<600?844:960});for(const screen of ['home','login','register','forgot-password','privacy']){await pub.goto(route(screen));await pub.evaluate(()=>Promise.all([...document.images].map(img=>{img.loading='eager';return img.decode().catch(()=>{})})));ok(!await pub.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),`Public ${screen} fits ${width}px`);ok(await pub.evaluate(()=>[...document.images].every(img=>img.complete&&img.naturalWidth>0)),`Public ${screen} images load at ${width}px`);if(screen==='home')await pub.screenshot({path:`${results}/home-${width}.png`,fullPage:true})}}
 await pub.setViewportSize({width:390,height:844});await pub.goto(route('home'));await pub.locator('[data-public-menu]').click();ok(await pub.locator('.site-nav').evaluate(el=>el.classList.contains('mobile-open')),'Public mobile menu works');
 await pub.goto(route('register'));await pub.locator('[name=password]').fill('Secret12345678!');await pub.locator('[data-toggle-password]').click();ok(await pub.locator('[name=password]').getAttribute('type')==='text','Password visibility control works');
 for(const [width,height] of [[320,568],[390,667],[1440,768],[1920,720]]){await pub.setViewportSize({width,height});await pub.goto(route('home'));ok(await pub.locator('.capabilities-strip').evaluate(el=>el.getBoundingClientRect().top<innerHeight),'Homepage reveals next band at '+width+'x'+height);ok(!await pub.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),'Short viewport fits '+width+'x'+height);await pub.screenshot({path:`${results}/home-first-${width}-${height}.png`})}
 result=await post(publicContext,'forgot-password',{email:'owner@example.test'},'forgot-password');ok(result.html.includes('queued for delivery'),'Password reset request succeeds');
 const outbox=fs.readdirSync(`${env.MONEY_STORAGE}/outbox`).map(file=>JSON.parse(fs.readFileSync(`${env.MONEY_STORAGE}/outbox/${file}`,'utf8')));const resetMessage=outbox.find(m=>m.subject==='Reset your Money password');const resetToken=resetMessage.body.match(/token=([a-f0-9]+)/)[1];
 const newPassword='NewMoneyPassword!2026';result=await post(publicContext,'reset-password&token='+resetToken,{token:resetToken,password:newPassword,password_confirmation:newPassword},'reset-password&token='+resetToken);ok(result.res.url().includes('r=login'),'Password reset updates account');
 result=await get(a,'dashboard');ok(result.res.url().includes('r=login'),'Password reset invalidates old sessions');
 result=await post(publicContext,'reset-password&token='+resetToken,{token:resetToken,password:newPassword,password_confirmation:newPassword},'reset-password&token='+resetToken);ok(result.res.status()===422,'Password reset link is single-use');
 result=await post(publicContext,'login',{email:'owner@example.test',password:newPassword},'login');ok(result.res.url().includes('r=dashboard'),'Login works with reset password');
 result=await post(publicContext,'logout',{});ok(result.res.url().includes('r=login'),'Logout ends session');
 for(let attempt=0;attempt<16;attempt++)result=await post(publicContext,'login',{email:'owner@example.test',password:'wrong-password'},'login');ok(result.res.status()===422&&result.html.includes('Too many attempts'),'Login rate limiting enforced');
 for(const privatePath of ['config.php','config.local.php','app/bootstrap.php','database/schema.sql','tools/install.php','storage/outbox/test.json']){response=await publicContext.request.get('http://127.0.0.1:8086/'+privatePath);ok(response.status()===404,'Private route blocked: '+privatePath)}
 ok(errors.length===0,'No browser JavaScript errors');
 const logPath=`${env.MONEY_STORAGE}/logs/application.log`;ok(!fs.existsSync(logPath)||fs.readFileSync(logPath,'utf8').trim()==='','No PHP warnings or exceptions');
 fs.writeFileSync(`${results}/report.json`,JSON.stringify({checks,errors,database:dbName,completed:new Date().toISOString()},null,2));
 console.log(`SUCCESS: ${checks} checks passed.`);
} catch(error){fs.writeFileSync(`${results}/failure.txt`,String(error.stack));throw error}
finally{await browser.close();server.kill();await new Promise(resolve=>server.exitCode!==null?resolve():server.once('exit',resolve));}
