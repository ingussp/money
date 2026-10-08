import fs from 'node:fs';
import path from 'node:path';
import {chromium} from 'playwright';

export async function openBrowser() {
  const localAppData = process.env.LOCALAPPDATA;
  const cachedBrowsers = localAppData ? path.join(localAppData, 'ms-playwright') : '';
  const cachedShells = cachedBrowsers && fs.existsSync(cachedBrowsers)
    ? fs.readdirSync(cachedBrowsers).filter(name => name.startsWith('chromium_headless_shell-')).sort().reverse()
      .map(name => path.join(cachedBrowsers, name, 'chrome-headless-shell-win64', 'chrome-headless-shell.exe'))
    : [];
  const executablePath = [
    process.env.MONEY_BROWSER_PATH,
    ...cachedShells,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  ].find(file => file && fs.existsSync(file));

  return chromium.launch({
    executablePath,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
}
