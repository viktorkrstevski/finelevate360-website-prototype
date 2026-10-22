# Staging localization and QA scope

Existing published localized hero, mission, expertise and navigation terms are
preserved. Missing theme descriptions, roles, dates, buttons, accessibility text
and form feedback are translated for MK, SR, HR, SL and ME. Official names,
brands, venue names and English presentation titles intentionally retained by
production remain unchanged.

News archive and article dates use the regional numeric format (`7. 7. 2026`)
outside English, independent of the staging administrator's WordPress locale.

The Macedonian event uses the existing public EN/MK translation table. It is
read as a strictly parsed literal, without executing the production script.
Translation happens on the server and accepts WordPress Customizer overrides.
Edited post text that no longer matches the source is preserved. Scripts,
external form handlers and private configuration are not imported.

## Source content without a published locale counterpart

These are existing content-availability gaps, rather than missing shared-theme
labels. Links use the available English route. No new legal or directory pages
or invented translated records are introduced by the theme translation repair.

| Published content | Missing locale counterparts |
| --- | --- |
| Privacy and cookie policies | SL |
| Insurance and Fintech directory pages | SR, HR, SL, ME |
| Featured CaSys news article | SL, ME |
| FEIT cooperation article | SR, HR, SL, ME |
| Summit event | SR, HR, SL, ME |

Some speaker/session titles and business roles inside the published MK event
remain English in production itself. Those official titles are preserved.

## What route checks establish

The 261-route sweep checks unauthenticated HTTPS, status 200, expected shared
theme release, noindex, the route's language attribute, and staging-only header
destinations. It does not establish correct pixels on every page, translation
quality for every imported article, successful directory filters/data editing,
external inquiry delivery, ticket purchases or booking-service behavior.

Visual and interaction checks cover representative homepage, About, Partners,
Advisory Board, news and Summit templates, desktop/mobile navigation and local
form validation. Exact views, viewport sizes, verified outcomes and limitations
belong in the task QA evidence; a route count is not visual or functional proof.

The Digital Map source restriction and the protected deployment gates remain
unchanged. Inquiries are preview-only; no external form submissions are enabled.
