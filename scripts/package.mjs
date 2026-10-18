import fs from 'node:fs/promises';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { root, output, pages, assets } from './project.mjs';

const archiveDir = path.join(root, 'artifacts');
await fs.mkdir(archiveDir, { recursive: true });
const parts = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
  timeZone: 'Europe/Skopje', year: 'numeric', month: '2-digit', day: '2-digit',
  hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
}).formatToParts(new Date()).map(part => [part.type, part.value]));
const stamp = `${parts.year}${parts.month}${parts.day}_${parts.hour}${parts.minute}${parts.second}`;
const archive = path.join(archiveDir, `finelevate360_ai_${stamp}.zip`);
execFileSync('zip', ['-q', archive, ...pages, ...assets, 'robots.txt', '.htaccess'], { cwd: output });
execFileSync('unzip', ['-tq', archive], { stdio: 'pipe' });
console.log(`Verified upload archive: ${archive}`);
