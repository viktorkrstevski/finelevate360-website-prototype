# Country map presentation QA

Draft presentation patch based on the installed theme release `847e20f500e390c63e132c6c76fdeffc42b9c4e5`. Target: https://stg.finelevate360.com only. No deployment has occurred.

## Causes and changes

- Montenegro's rendered `.finelevate-map-content` is empty, while the other maps have a region heading. Supply `Region: Crna Gora` only in that empty wrapper. Existing editorial text is never replaced.
- Imported map counts are not inside the legacy `.finelevate-header-item` ancestor, so its flex/gap styling never matches. Apply the same count-row treatment to the actual shared wrapper.
- The theme's `.content-page article` rule adds 48px bottom margin inside the Directory card grid, in addition to its 15px grid gap. Scope the card margin to zero and remove inherited heading top spacing. Keep the current card grid, progress display, filters and five statuses.
- Native Polylang menu markup has `.lang-item-me` labels but no flag image, both in the current-language parent and submenu. Add a local MIT-licensed Montenegro flag to primary menu output, with an empty decorative alt; preserve labels and destinations. Do not change Polylang settings.
- Header logo: 250px to 205px on desktop and 390px mobile (18% reduction); 203.9px to 172px at 320px (15.6%). Footer: 250/260px to 205px. Keep SVG geometry and automatic height.

## Verified

- CI: https://github.com/viktorkrstevski/finelevate360-website-prototype/actions/runs/40222334607 — passed PHP lint, route/event tests, focused flag/content isolation tests, 21 Python tests, JavaScript syntax and theme packaging. Deployment/access jobs skipped.
- Chrome local previews use focused public DOM snapshots from current staging plus its actual CSS, JS, fonts and images. The PHP-produced markup is represented in these previews; runtime hooks are separately unit-tested. This is not staging deployment/readback.
- All six maps (ME, MK, EN, Serbia, Slovenia, Croatia) at 320, 390 and 1280 CSS pixels: no document overflow; one card column on mobile and four on desktop; image assets load; header logo and menu remain inside the viewport; map cards have zero extra article margin. Montenegro's parent and submenu flags load in desktop/mobile menus.
- Apply/reset on all six desktop previews: mobile channel results ME 1/11, MK 12/12, EN 13/13, SR 16/19, SL 11/12, HR 18/19; reset restores every card and the default channel.
- Montenegro at 320 and 390: filter drawer opens, selection and Apply produce one visible card, Reset restores all 11 and clears capability selection; no horizontal overflow. The existing drawer remains open after Apply.
- All serialized card data attributes are identical before/after, including titles, filter maps and capability/status rows. The existing five-state legends are unchanged. No bank records were written.

## Deployment coordination

Only six theme files need installing: `style.css`, `functions.php`, `inc/presentation.php`, `assets/flags/me.svg`, `assets/flags/LICENSE.txt`, `THIRD_PARTY_NOTICES.txt`. The workflow, test and this QA document are repository-only.

Use a serialized, file-level patch to the active staging `wp-content/themes/finelevate-advisory` directory. Verify existing file hashes against the base first; if different, rebase/reconcile instead of overwriting. Upload the new PHP/asset/license files before adding the require to `functions.php`. Apply the style and notice changes, update the release manifest consistently, purge staging page/CSS caches, then read back files and rendered pages. Never upload the incomplete delta ZIP as a WordPress theme, replace the whole theme from an old package, or install any Directory package.

Remaining: parent coordination before any shared staging write, then actual staging readback/functional verification. A parent message attempt returned `thread not found`; no deployment action was attempted, denied or left uncertain.

Pre-existing data checkpoint for the Directory owner: the Montenegro heading count is 10 while the rendered card/filter count is 11. EN's initial cache served an old theme page with no map; a cache-busted URL returned the current authenticated render. Neither observation was modified by this presentation task.
