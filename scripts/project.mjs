import { fileURLToPath } from 'node:url';
import path from 'node:path';

export const root = fileURLToPath(new URL('../', import.meta.url));
export const output = path.join(root, 'dist');
export const pages = [
  'index.html', 'start.html', 'services.html', 'service-ai-data.html',
  'industry-banking.html', 'assessment.html', 'insights.html', 'about.html',
  'summit/index.html',
];
export const assets = [
  'assets/site.css', 'assets/site.js', 'assets/assessment.js',
  'assets/brand/finelevate-logo.svg', 'assets/brand/finelevate-logo-white.svg',
  'assets/brand/fds-compact-finelevate360.svg',
  'assets/brand/fds-endorsed-dark-finelevate360.svg',
];
export const stagingHeaders = {
  'X-Robots-Tag': 'noindex, nofollow, noarchive',
  'X-Content-Type-Options': 'nosniff',
  'Referrer-Policy': 'strict-origin-when-cross-origin',
};
