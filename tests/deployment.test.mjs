import test, { before, after } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { request } from 'node:http';
import { once } from 'node:events';
import { build } from '../scripts/build.mjs';
import { checkProject } from '../scripts/check.mjs';
import { output, pages, assets } from '../scripts/project.mjs';
import { makeServer } from '../scripts/serve.mjs';

let server, base;
before(async () => {
  await build(); server = makeServer(); server.listen(0, '127.0.0.1'); await once(server, 'listening');
  base = `http://127.0.0.1:${server.address().port}`;
});
after(async () => { if (server) { server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); } });

test('all local links, anchors and scripts in the upload are valid', () => {
  assert.equal(checkProject().pages, 9);
});

test('all built pages and assets are served with their expected content type', async () => {
  for (const name of [...pages, ...assets]) {
    const response = await fetch(`${base}/${name}`);
    assert.equal(response.status, 200, name);
    assert.match(response.headers.get('x-robots-tag'), /noindex/);
    if (name.endsWith('.html')) {
      assert.match(response.headers.get('content-type'), /text\/html/);
      assert.match(await response.text(), /name="robots" content="noindex,nofollow,noarchive"/);
    }
    if (name.endsWith('.css')) assert.match(response.headers.get('content-type'), /text\/css/);
    if (name.endsWith('.js')) assert.match(response.headers.get('content-type'), /javascript/);
  }
});

test('home and nested Summit URLs resolve to their own index pages', async () => {
  const home = await fetch(`${base}/`), summit = await fetch(`${base}/summit/`);
  assert.match(await home.text(), /Advisory for financial services/);
  assert.match(await summit.text(), /Finelevate Digital Summit/);
  const redirect = await fetch(`${base}/summit`, { redirect: 'manual' });
  assert.equal(redirect.status, 308); assert.equal(redirect.headers.get('location'), '/summit/');
});

test('build excludes unfinished proposals, source, dependencies and configuration', async () => {
  for (const file of ['proposal/index.html', 'package.json', 'content/assessment-model.json', 'scripts/serve.mjs', 'node_modules/jsdom/package.json', '.env']) {
    assert.equal((await fetch(`${base}/${file}`)).status, 404, file);
  }
  assert.equal((await fetch(`${base}/.htaccess`)).status, 404);
});

test('encoded traversal cannot expose files outside the deployment root', async () => {
  for (const target of ['/%2e%2e%2fpackage.json', '/%2eenv', '/assets/%5c..%5cpackage.json', '/%00']) {
    const status = await new Promise((resolve, reject) => {
      const req = request(base, { path: target }, res => { res.resume(); res.on('end', () => resolve(res.statusCode)); });
      req.on('error', reject); req.end();
    });
    assert.equal(status, 404, target);
  }
});

test('staging headers and crawler rules are included for Apache/LiteSpeed hosting', async () => {
  assert.match(await fs.readFile(path.join(output, '.htaccess'), 'utf8'), /X-Robots-Tag "noindex, nofollow, noarchive"/);
  assert.match(await fs.readFile(path.join(output, 'robots.txt'), 'utf8'), /Disallow: \//);
  assert.equal((await fetch(base, { method: 'POST' })).status, 405);
  const head = await fetch(base, { method: 'HEAD' });
  assert.equal(head.status, 200); assert.equal(await head.text(), '');
});
