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
Hostinger vendor backport status for this server is unverified. No production PHP settings were
changed, and this observation establishes no compromise.


## Provisioning checkpoint — 21 October 2026

A fresh staging WordPress instance and administrator were created after explicit
owner approval; WordPress noindex is saved. The approved dedicated FTPS account
is scoped in hPanel to staging's `public_html/wp-content/themes`. Its generated
credential is stored only in the protected staging environment. The marker was
saved and read back through the scoped file manager. The intended FTP endpoint
is pinned in the deploy script; its certificate does not currently match that
hostname. Neither certificate validation nor data-channel encryption may be disabled.

The separate `verify-staging-access` job waits for the existing owner reviewer.
It reads the marker, checks the reported root and its listing, and verifies that
`CWD ..` cannot leave that root. It changes no files and does not enable deployment.
These checks supplement hPanel's directory restriction; they do not certify the
hosting provider's underlying isolation implementation. No workflow receives
production credentials. Deployment also repeats the confinement checks.

Staging currently reports PHP **8.4.26**; production's read-only metadata still
reports **8.3.35**. Fixed upstream releases include 8.4.27 and 8.3.36. No relevant
Hostinger server-specific backport statement was found. See the later checkpoint
for the newly published platform-mitigation statement; it does not establish a
patched runtime on this Business hosting server. No support request is pending,
and the owner now explicitly prohibits further support contact.
Hostinger's public PHP selector guide describes plan-wide effects, so further
selector changes require resolving scope before any action. Deployment remains
disabled; no staging DNS record has been added and staging SSL is unverified.

Chrome's local ZIP chooser failed before selecting a file because extension
file-URL permission is disabled. No ZIP was submitted or installed, and that
permission was not changed. The separately authorized CI route can be used after
readiness checks; it does not require this additional browser permission.

Digital Map desktop/mobile references have been retrieved from the user-provided
Library files. Their figures and filter outcomes are illustrative, not bank data.
The production directory plugin source cannot be inspected through the connected
WPVibe 1.16.5: its read-only wp-content operation explicitly requires a plugin
update. No production update was attempted. Actual directory integration and
locale-specific staging content remain unfinished; no speculative records,
ratings or official bank URLs are bundled.

The owner-approved preflight at commit `87628d4d` reached verified TLS but failed
at login with `530 Login incorrect`; no target read or write ran. hPanel then
confirmed that the FTP service address is **82.198.228.17**, distinct from the
file-manager hostname's current DNS address. A credential-free AUTH TLS handshake
to that exact IP validated the certificate against `srv2071-files.hstgr.io` using
the default trust store and hostname checks. That historical handshake established
certificate identity only, not that the file-manager hostname was the intended
FTP service identity. The current script instead pins the provider/user-configured
FTP hostname and hPanel IP, with default certificate verification. See the current
checkpoint below. It never disables certificate validation, changes credentials,
or broadens the account.

The corrected owner-approved check also failed at authentication with `530 Login
incorrect`. No target read or write ran. No further login retry or password reset
was attempted. Access checks now require the explicit repository opt-in variable
`STAGING_ACCESS_CHECK_ENABLED=true`, which stays unset until the owner has repaired
the dedicated staging credential and its protected-environment secret. Regular
pushes still validate/package. Deployment requires a successful access-check job,
its own opt-in, and the protected environment review; skipping the access check
cannot enable deployment. Do not use broader hosting credentials as a workaround.

The local desktop visual check found a scrollbar-width overflow from the deliberate
hero bleed. Clipping horizontal paint at the main page boundary keeps the image
at the viewport edge without clipping header dropdowns or introducing a scroll
container. The fix was checked at 320, 375, 430, 768, 1024 and 1280 viewport widths
and in the native desktop window with its vertical scrollbar.

## Current transport and runtime checkpoint — 22 October 2026

The intended explicit-FTPS endpoint is `ftp.stg.finelevate360.com:21`, pinned to
the hPanel FTP address `82.198.228.17`. Cloudflare has its DNS-only A record, with
no competing AAAA/CNAME. The certificate presented by that service covers
`*.hstgr.io` / `hstgr.io`, not the configured FTP hostname. Credential-free TLS
therefore fails hostname validation. No credentials were sent in those checks.
Do not substitute the file-manager name merely because its wildcard validates,
use plaintext, or disable hostname/certificate checks. The dedicated username
guard, staging marker and confinement checks must all pass before CI writes.
The later username-guarded run failed before networking; there is no current
successful confinement checkpoint. Both deployment opt-in variables remain unset.

Staging's authenticated WordPress HTTPS admin works through its temporary
Hostinger domain. The theme ZIP chooser still fails because the Chrome extension
lacks local-file access. The upload form was reconciled after the failed call:
zero files selected and Install Now disabled. Nothing was uploaded or installed.
No extension permission was changed, and no upload approval operation remains
pending. The ZIP is built from source commit `435076c7b0662eec1f50291f4c57bbdb4eb1e5f7`
with SHA-256 `44c695a15e2a6d7859eab30026579f735bca1889c481a3c855a95bfbd3c6a575`.

Hostinger's Git wizard reused an existing GitHub authorization and listed the
correct repository. Its destination control supports a subfolder of public_html,
but the [current Git documentation](https://docs.hostinger.com/websites/git)
states that the integration enables automatic deployment on pushes. The wizard
was left undeployed: directly connecting the source branch would bypass the
protected staging environment and would deploy the repository root rather than
the packaged theme. A future artifact-branch solution must retain owner review
and target only `wp-content/themes/finelevate-advisory`.

Both hPanel PHP Info and WordPress Site Health still report **PHP 8.4.26**.
Hostinger's [public incident](https://statuspage.hostinger.com/incidents/pj2zs1lx3n9d)
states that its platform tests found no exposure to CVE-2026-109618/109619 and
that it added a protective layer. Its completed patch updates currently identify
Agency infrastructure and cPanel servers; remaining managed-hosting upgrades
are described as ongoing. This is Business hPanel hosting, so those completed
updates do not establish this server's patched package or a backport. Deployment
remains disabled pending the required applicable remediation evidence.

The new navigation redesign is awaiting visual approval and has not been
implemented. Digital Map integration, translated staging pages, hosted QA and
the public staging domain's DNS/SSL remain incomplete. Production, mail,
passwords, account scope and hosting settings were not changed in this check.

## Current checkpoint - 22 October 2026

The installed theme's 47 files were verified against release
`435076c7b0662eec1f50291f4c57bbdb4eb1e5f7` before activation. Finelevate Advisory
is now active on the isolated temporary staging hostname. Browser control was
restored, and staging PHP Info was freshly verified: PHP 8.4.26, Build Date
`Oct 21 2026 00:00:00`. Hostinger's 22 October 06:44 UTC incident update confirms
all managed hosting patched and identifies this build date as a fixed backport.
No production PHP or plan setting was changed.

Cloudflare now has the authorized DNS-only `stg` A record at `82.198.228.17`,
verified both in the zone and at its authoritative nameserver. Hostinger's
Lifetime SSL certificate is still Installing; strict HTTPS has not yet passed.
The existing temporary HTTPS hostname remains usable. No TLS exception is used.

The user's site-wide consistency report exposed production URLs in fallback
navigation. The fresh staging site did not yet contain inner pages. The repair
adds a public-content route manifest, existing language destinations, shared
header geometry, and a public-only WXR import of 261 published records. Import
uses the official WordPress importer and maps attribution to the existing
staging administrator; it must not create production users or copy private meta,
comments, submissions, credentials or database settings. Public media URLs are
retained; they are not production admin access.

The imported directory pages contain their original explanatory copy only.
The live directory plugin, bank records and capability editor have NOT been
replaced with static scraped filters or illustrative ratings. Access to that
plugin's source remains restricted by the old connected WPVibe plugin. Its
integration and the submission forms remain incomplete. The separate Canva
navigation redesign is still unapproved and is not part of this repair.

CI keeps both deployment/access opt-ins disabled and retains owner review.
The FTPS certificate identity issue remains unresolved; the authorized manual
WordPress HTTPS upload path is used without weakening TLS or broadening access.
Production, mail, credentials and other sites are untouched. No support contact.
