import fs from 'node:fs/promises';
import path from 'node:path';
import { createServer } from 'node:http';
import { fileURLToPath } from 'node:url';
import { output, stagingHeaders } from './project.mjs';

const mime = { '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8', '.svg': 'image/svg+xml', '.txt': 'text/plain; charset=utf-8' };

export function makeServer() {
  return createServer(async (req, res) => {
    for (const [key, value] of Object.entries(stagingHeaders)) res.setHeader(key, value);
    if (!['GET', 'HEAD'].includes(req.method)) { res.writeHead(405, { Allow: 'GET, HEAD' }); res.end(); return; }
    let pathname;
    try { pathname = decodeURIComponent(new URL(req.url, 'http://localhost').pathname); }
    catch { res.writeHead(400); res.end('Bad request'); return; }
    if (pathname.split('/').some(segment => segment.startsWith('.') || segment.includes('\\') || segment.includes('\0'))) {
      res.writeHead(404); res.end('Not found'); return;
    }
    const requested = path.resolve(output, `.${pathname.endsWith('/') ? pathname + 'index.html' : pathname}`);
    if (!requested.startsWith(output + path.sep)) { res.writeHead(404); res.end('Not found'); return; }
    try {
      const info = await fs.stat(requested);
      if (info.isDirectory()) { res.writeHead(308, { Location: pathname + '/' }); res.end(); return; }
      if (!info.isFile()) throw new Error('Not a file');
      const body = await fs.readFile(requested);
      res.writeHead(200, { 'Content-Type': mime[path.extname(requested)] || 'application/octet-stream', 'Content-Length': body.length });
      res.end(req.method === 'HEAD' ? undefined : body);
    } catch { res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' }); res.end('Not found'); }
  });
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try { await fs.access(path.join(output, 'index.html')); }
  catch { console.error('Build the site first: npm run build'); process.exit(1); }
  const port = Number(process.env.PORT || 8765);
  const host = process.env.HOST || '127.0.0.1';
  makeServer().listen(port, host, () => console.log(`Test site: http://${host}:${port}`));
}
