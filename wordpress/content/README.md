# Published staging content

`published-site.xml.gz` is a public-only WordPress WXR snapshot (261 records,
22 October 2026). Decompress it before using the official WordPress importer on
the isolated staging instance. Assign authorship to the existing staging admin;
do not create production users. It contains only published titles, copy, public
links and three staging routing metadata fields. No comments, submissions,
private metadata, users, credentials, databases or mail configuration.

The source is the current production site's public content, not the illustrative
Digital Map design. Directory records, capability filters/editor and submission
forms remain unintegrated. Public scripts and forms that could post to production
were excluded. Public media references are retained.

The accompanying theme route manifest preserves original language-specific
paths and keeps known editorial links on staging. Do not re-import to overwrite
subsequent staging editorial changes. Keep a WordPress export before a later
content migration. Theme rollback does not delete or roll back imported content.
