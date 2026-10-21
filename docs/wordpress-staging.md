# Finelevate WordPress staging

Target: **https://stg.finelevate360.com**. This branch adds a separate classic
WordPress theme under `wordpress/themes/finelevate-advisory`. The original
static prototype and `codex/production-readiness` branch are preserved.

The A v5 handoff archive was verified against SHA-256
`e80f5a4a8aac926d54a952b8ac52207e26aec7a5563097015aec8c58e302747e`.
Only the theme's public source assets and license notices are versioned here.
JPEG EXIF/XMP/IPTC/comments, PNG text/EXIF and WebP EXIF/XMP metadata are removed
before versioning; the FEIT
photograph's orientation was 1, so removing metadata leaves its image orientation
and compressed pixel stream unchanged. Responsive hero JPEGs are also scrubbed.
CI rejects these metadata chunks if subsequently reintroduced.
The theme does not bundle WordPress core, a database, credentials, bank records,
or private uploads. Digital Map record storage and capability definitions are
owned by the existing directory plugin; this theme does not edit them.

## Editing and integration

Appearance → Customize → Finelevate homepage edits section copy, portraits,
hero image and navigation destinations. Assigned WordPress primary/footer
menus override the supplied fallback navigation. Polylang, when installed,
provides real language navigation and translates registered homepage strings.
Until staging translations and pages are imported, verified public production
destinations remain the fallback; navigation never pretends that missing
staging translations exist. Existing locale-specific directory behavior must
be migrated and verified separately before the staging map is called complete.

All 17 original partner marks are used in their supplied order. The moving
track is grayscale, Mastercard is fixed in colour. Pause, hover/focus pause,
and reduced-motion static access are implemented. The architecture hero is
conceptual. The Summit date is in the past and uses neutral Event details copy.
The contact dialog retains Become a Partner and the published fields. Its
validation is local, sends no inquiry, and stores no contact data.

The theme adds no analytics. On the exact staging hostname it emits noindex
headers/meta/robots rules and blocks `wp_mail`. Also set WordPress Settings →
Reading → Discourage search engines before making staging reachable. Do not
copy production mail providers, API keys, recipient lists or private submissions.

## CI and deployment

The workflow lints PHP, checks JavaScript, tests packaging/target isolation and
rollback, and uploads a reproducible theme ZIP with per-file and ZIP SHA-256
hashes. Artifacts contain a public `release.json` with the exact commit SHA.
Actions are pinned to verified commit SHAs. Fork/PR validation gets no deploy
secrets. Production has no job, environment or deploy destination in this change.

The GitHub `staging` environment requires approval by `viktorkrstevski`, disables
administrator bypass and allows only `codex/advisory-clarity-staging`.
Deployment stays disabled while repository variable `STAGING_DEPLOY_ENABLED`
is unset. Before enabling it, complete these owner-approved account operations:

1. Create a fresh WordPress instance and separate database at the staging host;
   the owner completes the new WordPress administrator credential form.
2. Confirm a patched supported PHP runtime by **full patch version** or a vendor
   remediation statement. As of 21 October 2026 the PHP 8.3 fix is 8.3.36.
3. Add only the `stg` DNS record in authoritative Cloudflare DNS, issue a valid
   staging SSL certificate, set noindex, and activate the theme in staging.
4. Obtain action-time approval for a **new, dedicated FTPS account jailed to the
   staging instance's `public_html/wp-content/themes` directory**. Never reuse a
   hosting-wide credential or production credential for ongoing CI access.
   The owner must enter new credentials and save them directly in GitHub's
   staging environment, never chat or a repository file. Confirm Hostinger
   supports verified explicit FTPS for this account; no plain-FTP fallback.
5. In that dedicated FTP root, place `.finelevate-staging-target` containing only
   `https://stg.finelevate360.com`. The deploy script checks this before any write.
6. Set staging environment variable `STAGING_FTPS_HOST` to the verified Hostinger
   TLS hostname, and secrets `STAGING_FTPS_USER` and `STAGING_FTPS_PASSWORD`.
   Then set the repository opt-in variable `STAGING_DEPLOY_ENABLED=true`.

Every staging push then validates/packages before waiting for environment
approval. Deployments verify the uploaded bytes, retain the previous theme
directory, swap the theme directory, and check HTTPS, noindex and public commit
identity. Failed post-deployment checks restore the previous theme directory
when one exists. Database and uploads are untouched. Older backups are retained
for owner-managed retention; the pipeline never deletes historical releases.

Rollback uses the exact `.finelevate-rollback-*` directory recorded in a prior
run plus that release's full commit SHA, through the same protected environment.
The manual-dispatch UI becomes available when this workflow is accepted into
the default branch; select the allowed staging branch when dispatching.
The deployment script also supports the same explicit rollback inputs for an
approved operator. No database migrations occur, so rollback restores code only.

## Validation and known pending checks

Local packaging tests exercise real forbidden paths, symlinks, tampered files,
wrong-target roots, and interrupted swaps. GitHub performs PHP lint and packages
the exact pushed commit. Runtime WordPress, full desktop/mobile screenshots,
SSL/noindex, integration with the directory plugin, published translations and
actual deployment remain separate checks; passing CI alone does not certify them.

Production metadata was read through WPVibe on 21 October 2026 and reported
PHP **8.3.35**. The [official PHP advisory](https://www.php.net/archive/2026.php)
lists **8.3.36** as the fixed 8.3 release for CVE-2026-109618 and CVE-2026-109619.
Hostinger vendor backport status is unverified. No production PHP settings were
changed, and this observation establishes no compromise.
