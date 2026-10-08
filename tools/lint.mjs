import fs from 'node:fs';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
const root=path.dirname(path.dirname(fileURLToPath(import.meta.url)));
function files(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(entry=>entry.isDirectory()&&entry.name!=='storage'?files(path.join(dir,entry.name)):entry.isFile()&&entry.name.endsWith('.php')?[path.join(dir,entry.name)]:[])}
let failed=0;const list=files(root);
for(const file of list){const result=spawnSync('C:/xampp/php/php.exe',['-l',file],{encoding:'utf8',windowsHide:true});if(result.status!==0){failed++;console.log(result.stdout+result.stderr)}}
console.log(`${list.length} PHP files checked; ${failed} failures.`);process.exitCode=failed?1:0;
