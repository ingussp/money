import fs from 'node:fs';
import net from 'node:net';
import {spawn} from 'node:child_process';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const runtime='C:/dev/money-runtime';
const env=Object.fromEntries(Object.entries(process.env).map(([k,v])=>[k.toUpperCase(),v]));
const listening=port=>new Promise(resolve=>{const s=net.createConnection({host:'127.0.0.1',port});s.once('connect',()=>{s.destroy();resolve(true)});s.once('error',()=>resolve(false))});
function start(exe,args,name){
  const child=spawn(exe,args,{cwd:root,env,detached:true,windowsHide:true,stdio:['ignore',fs.openSync(`${runtime}/${name}-output.log`,'a'),fs.openSync(`${runtime}/${name}-error.log`,'a')]});
  child.unref();console.log(`${name}: ${child.pid}`);
}
if(!await listening(3307))start('C:/xampp/mysql/bin/mysqld.exe',['--defaults-file=C:/dev/money-runtime/mysql/my.ini','--bind-address=127.0.0.1','--console'],'mysql');
if(!await listening(8085))start('C:/xampp/php/php.exe',['-d','upload_max_filesize=10M','-d','post_max_size=12M','-S','127.0.0.1:8085','router.php'],'php');
console.log('Preview: http://127.0.0.1:8085');
