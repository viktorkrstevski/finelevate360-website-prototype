# Installed Rank Math REST fixture

`class-shared.php` was read from staging through the native WordPress editor, without edits, and matched the public upstream file byte for byte (LF): SHA256 `1e1dbcf45fe06abc1a5831e34c14958797bf2e3a7b9a97d25e6a4b5333148fd1`.

Source: https://github.com/rankmath/seo-by-rank-math/blob/50fc59587112316ad754ca3c9a26e1282795d49d/includes/rest/class-shared.php

Rank Math SEO 1.0.280 is GPL-3.0-or-later, as declared by its plugin header: https://github.com/rankmath/seo-by-rank-math/blob/50fc59587112316ad754ca3c9a26e1282795d49d/rank-math.php . Original author/copyright headers are retained.

The installed compiled Classic Editor script also confirms `saveMeta()` runs before native `requestSubmit()`, using POST `rankmath/v1/updateMeta`, with objectID, objectType, meta and content. Installed `assets/admin/js/classic.js` SHA256 (LF): `726df98a8b4e602f09be89c1afb4c150c27c66d5d55f3bc9da3ce167e0cc10c4`. The script itself is not modified or bundled.

The test executes the real REST callback and Directory native save/repair hooks with fresh hooks, globals and superglobals between requests, using simulated WordPress persistence and minimal dependency stubs. It is not a full WordPress/HTTP integration environment; live authenticated verification remains required.
