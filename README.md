# Finelevate360 website — Prototype v2

Clickable prototype of the revised finelevate360.com: **consulting first, with the Finelevate Digital Summit as the annual flagship.**

**Preview locally:** with Node.js 20 or newer, run `npm run preview` and open `http://127.0.0.1:8765/`. Photos and fonts need an internet connection. There are no npm dependencies to install.

## Test deployment to ai.finelevate360.com

This repository is a static HTML/CSS/JavaScript test site. It does not install, replace or modify the existing WordPress site at finelevate360.com.

1. Run `npm ci --ignore-scripts`, then `npm test`.
2. Run `npm run package`. This validates local links, anchors and JavaScript, builds `dist/`, and creates a verified ZIP in `artifacts/`.
3. On the hosting account, create or select **ai.finelevate360.com** and confirm that its document root is separate from the main site's `public_html`. Enable HTTPS for the test hostname.
4. Deploy the ZIP to that test site's document root, with `index.html` at the root. Include the hidden `.htaccess` file. Do not upload the repository or `node_modules/` as the public document root.
5. Verify `/`, `/assessment.html`, `/summit/`, `/assets/site.css` and `/assets/site.js` over HTTPS. The main site's files and DNS records must remain unchanged.

The build includes `noindex` metadata, a crawler exclusion in `robots.txt`, and an `X-Robots-Tag` header for Apache/LiteSpeed. These discourage indexing; they do not password-protect the preview.

For a Node.js deployment instead of static hosting: build with `npm run build`, start with `npm start`, and set `HOST=0.0.0.0` plus the hosting provider's `PORT`. The server serves only `dist/`; unknown URLs return 404. Static hosting is the recommended option for this project.

The build uses current working-tree files and an explicit list of deployment inputs. It excludes unfinished `proposal/` work, source scripts, contact data, dependencies and credentials. Run `npm run package` again after any source change; do not reuse an older archive.

## Checks

- `npm run check`: local pages, links, anchors and JavaScript syntax.
- `npm test`: assessment regression tests, build completeness and HTTP serving tests.
- GitHub Actions runs the same checks and produces a deployable archive; it does not publish the site.

## Pages
| Page | What it shows |
|---|---|
| `index.html` | Home: consulting capabilities, industries, approach, AI readiness, the Summit band, insights, people, contact |
| `services.html` | All consulting services, ways to start, 14 expert roles, FAQ |
| `service-ai-data.html` | Template for every service page |
| `industry-banking.html` | Template for every industry page (sourced market figures) |
| `assessment.html` | The working AI readiness assessment |
| `insights.html` | Digital Maps, the State of AI Readiness 2026 report, articles, newsletter |
| `about.html` | Platform, leadership, experts, Advisory Board, partners, news |
| `summit/index.html` | Finelevate Digital Summit 2026 story, 24 speakers, programme, photo gallery, partners, awards, 2027 sign-up |

Add `?review` to any page URL to enable **Show PM notes**. Mobile navigation works on every page. Without JavaScript, the page content and mobile navigation remain visible.

## Still to provide (marked in yellow on the pages)
- Viktor's portrait and confirmed biography.
- Expert portraits, with each person's consent.
- Advisory Board members.
- Awards winners and categories.
- Session times and recordings for the programme.
- Article authors.
- Partner logos and confirmed sponsor tiers.

## Notes
- All forms are prototypes: nothing is sent. In production they connect to Brevo and CF7.
- The report request is explicitly a preview. Contact details are kept only in the current tab; browser storage saves assessment progress without name, email or organisation name.
- WordPress integration is a separate future step. The product plan and design research referenced by the original prototype are not included in this repository.
