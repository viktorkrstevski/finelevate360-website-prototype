import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { root, output, pages, assets, stagingHeaders } from './project.mjs';

export async function build() {
  await fs.rm(output, { recursive: true, force: true });
  for (const file of [...pages, ...assets]) {
    const dest = path.join(output, file);
    await fs.mkdir(path.dirname(dest), { recursive: true });
    let content = await fs.readFile(path.join(root, file));
    if (file.endsWith('.html')) {
      content = content.toString().replace(/<head>/i,
        '<head>\n<meta name="robots" content="noindex,nofollow,noarchive">');
    }
    await fs.writeFile(dest, content);
  }
  await fs.writeFile(path.join(output, 'robots.txt'), 'User-agent: *\nDisallow: /\n');
  const headers = Object.entries(stagingHeaders).map(([key, value]) => `Header always set ${key} "${value}"`).join('\n');
  await fs.writeFile(path.join(output, '.htaccess'),
    `DirectoryIndex index.html\nOptions -Indexes\n<IfModule mod_headers.c>\n${headers}\n</IfModule>\n`);
  console.log(`Built ${pages.length} pages in dist/. Search indexing is disabled for this test build.`);
}

if (process.argv[1] === fileURLToPath(import.meta.url)) await build();
