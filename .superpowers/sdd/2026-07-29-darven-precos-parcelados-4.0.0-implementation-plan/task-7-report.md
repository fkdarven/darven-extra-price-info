# Task 7 — Internationalization report

## Scope and revisions

- **BASE:** `111dafa3fb93d7f5e5bd46b395ea8a6451ff58b9`
- **HEAD:** `103d5ee2353ba42af5e8eecb15ba869abae125c4` (local Task 7 commit;
  amended after this report update).
- **Official domain:** `darven-multiplos-precos-informativos`

The plugin header and `DARVEN_EPI_LANGUAGE_DOMAIN` now declare the official
domain. `TextDomainLoader` loads the runtime `languages/` directory and the
two active React bundle handles register translations through
`wp_set_script_translations`. The technical plugin slug, directory, main file,
admin page slug, existing CSS class names, and frontend script handle remain
unchanged.

All active PHP and admin JavaScript translation calls use the official domain.
The source strings are English; the administrative product name is now
`Darven Installment Prices` in source and is supplied as `Darven Preços
Parcelados` by pt-BR. The pt-BR catalog was reviewed for terminology including
`preço à vista`, `parcelamento`, accessible loading labels, save notices, and
the product-options controls.

## TDD record

`tests/TextDomainTest.php` was created first. The focused RED run failed for
the expected three missing behaviors: old loader domain/path, old script
translation domain for both handles, and absence of the v4 artifacts. The
minimum implementation then migrated the loader, header, PHP/JS calls, and
runtime packaging. The same focused suite subsequently passed.

## Generated artifacts and tooling

The normal `composer` and `php` commands were not on PATH. The reproducible
tools found were PHP 8.3 at
`C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe` and WP-CLI at
`C:\laragon\bin\wp-cli\wp-cli.phar`. `vendor/bin`, `%APPDATA%\Composer\vendor\bin`,
and the local Laragon tree were checked before choosing that WP-CLI.

Generated with WP-CLI:

- `languages/darven-multiplos-precos-informativos.pot`
- `languages/darven-multiplos-precos-informativos-pt_BR.po`
- `languages/darven-multiplos-precos-informativos-pt_BR.mo`
- six hashed `languages/darven-multiplos-precos-informativos-pt_BR-*.json`
  script catalogs.

No translation binary was hand-authored. `scripts/build-release.php` explicitly
excludes Node dependencies while retaining the runtime `languages/` tree;
the release test verifies POT, PO, MO and hashed JSON inclusion and excludes
admin source/Node files.

## Verification

- `npm run build:admin` — passed. Webpack emitted both bundles. Sass emitted
  its existing legacy-JS API deprecation warning.
- `wp i18n make-pot …` — passed using the Laragon WP-CLI.
- `wp i18n make-mo …` — passed using the Laragon WP-CLI.
- `wp i18n make-json languages --no-purge` — passed; six JS catalogs emitted.
- `php vendor/bin/phpunit --filter='TextDomainTest|PluginMetadataTest|ReleasePackageTest'`
  — passed: 11 tests, 1273 assertions, 1 pre-existing platform-specific skip.

## Limitations

The generated JSON filenames are content hashes and may change whenever the
extracted strings change; tests intentionally match the expected hash pattern
instead of pinning a hash. The Sass deprecation warning originates from the
project's current loader/toolchain and was not altered by this task.

## Review fix round 1

The initial PO population command used a single-quoted PowerShell replacement
value containing `` `r`n ``. In a single-quoted PowerShell string those
characters are literal, so translated entries became one invalid gettext line
instead of a `msgid` line followed by a `msgstr` line. WP-CLI consequently
treated the malformed combined text as the source key and emitted JSON files
with empty translation maps.

The regression test now rejects the literal sequence, asserts a real newline
between `msgid "General"` and `msgstr "Geral"`, and reads the generated JSON
for both `admin/src/settings/app.js` and `admin/src/product-options/app.js`.
The PO was corrected by replacing that literal token with
`[Environment]::NewLine`, then the MO and JSON artifacts were regenerated with
Laragon WP-CLI.

RED: `TextDomainTest` failed with the malformed PO and empty JSON values.
GREEN: `TextDomainTest` passed (4 tests, 31 assertions). The full Task 7
focused set passed (12 tests, 1275 assertions, 1 platform-specific skip),
including the release-package check. The local fix commit follows this report
update and carries `Vault-Author: codex`; no push was made.
