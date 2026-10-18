import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';
import { root, pages, assets } from './project.mjs';

export function checkProject() {
  const errors = [];
  const html = new Map(pages.map(page => [page, fs.readFileSync(path.join(root, page), 'utf8')]));
  const ids = new Map([...html].map(([page, text]) => [page,
    new Set([...text.matchAll(/\bid\s*=\s*["']([^"']+)["']/g)].map(match => match[1]))]));
  const deployed = new Set([...pages, ...assets]);
  for (const asset of assets) if (!fs.existsSync(path.join(root, asset))) errors.push(`Missing asset: ${asset}`);
  for (const [page, text] of html) {
    if (!/<title>[^<]+<\/title>/.test(text)) errors.push(`${page}: missing title`);
    for (const match of text.matchAll(/\b(?:src|href)\s*=\s*["']([^"']*)["']/g)) {
      const value = match[1];
      if (!value || value === '#' || /^(?:https?:|data:|mailto:|tel:|javascript:)/i.test(value)) continue;
      const url = new URL(value, `https://preview.invalid/${page}`);
      const target = decodeURIComponent(url.pathname.slice(1));
      if (!deployed.has(target)) errors.push(`${page}: resource is missing from build: ${value}`);
      if (url.hash && ids.has(target) && !ids.get(target).has(decodeURIComponent(url.hash.slice(1)))) {
        errors.push(`${page}: missing anchor: ${value}`);
      }
    }
    for (const match of text.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
      if (!/\bsrc\s*=/.test(match[1]) && !/application\/ld\+json/.test(match[1])) {
        try { new vm.Script(match[2], { filename: page }); } catch (error) { errors.push(String(error)); }
      }
    }
  }
  for (const asset of assets.filter(file => file.endsWith('.js'))) {
    try { new vm.Script(fs.readFileSync(path.join(root, asset), 'utf8'), { filename: asset }); }
    catch (error) { errors.push(String(error)); }
  }
  if (errors.length) throw new Error(errors.join('\n'));
  return { pages: pages.length, assets: assets.length };
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  const result = checkProject();
  console.log(`Validated ${result.pages} pages, local links, anchors and JavaScript; ${result.assets} assets.`);
}
