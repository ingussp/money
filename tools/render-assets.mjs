import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath,pathToFileURL} from 'node:url';
import {spawnSync} from 'node:child_process';
import {openBrowser} from './browser.mjs';
const root=path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const runtime='C:/dev/money-runtime';
let credentials;
if(fs.existsSync(`${runtime}/demo-credentials.json`))credentials=JSON.parse(fs.readFileSync(`${runtime}/demo-credentials.json`,'utf8'));
else {const result=spawnSync('C:/xampp/php/php.exe',[`${root}/tools/seed-demo.php`],{encoding:'utf8',windowsHide:true});if(result.status!==0)throw new Error(result.stderr);credentials=JSON.parse(result.stdout);if(!credentials.password)throw new Error('Demo exists but no credential file is available.');fs.writeFileSync(`${runtime}/demo-credentials.json`,JSON.stringify(credentials,null,2));}
const browser=await openBrowser();
try {
 const page=await browser.newPage({viewport:{width:1440,height:960},deviceScaleFactor:1});
 await page.goto('http://127.0.0.1:8085/index.php?r=login');
 await page.locator('[name=email]').fill(credentials.email);await page.locator('[name=password]').fill(credentials.password);await page.locator('button[type=submit]').click();await page.waitForURL('**r=dashboard');await page.waitForTimeout(400);
 await page.screenshot({path:`${root}/assets/product.jpg`,type:'jpeg',quality:94});
 await page.setViewportSize({width:390,height:844});await page.waitForTimeout(200);await page.screenshot({path:`${root}/assets/product-mobile.jpg`,type:'jpeg',quality:94});
 await page.setViewportSize({width:1600,height:800});await page.goto(pathToFileURL(`${root}/tools/art-stage.html`).href);await page.evaluate(()=>Promise.all([...document.images].map(img=>img.decode())));await page.screenshot({path:`${root}/assets/hero.jpg`,type:'jpeg',quality:95});
 await page.screenshot({path:`${root}/assets/auth-art.jpg`,type:'jpeg',quality:94,clip:{x:738,y:80,width:830,height:550}});
 await page.setViewportSize({width:780,height:1380});await page.evaluate(()=>document.body.classList.add('mobile'));await page.screenshot({path:`${root}/assets/hero-mobile.jpg`,type:'jpeg',quality:95});
 console.log('English H01 artwork and Daily Focus product screenshots rendered.');
} finally {await browser.close()}
