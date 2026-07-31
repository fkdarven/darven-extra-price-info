# Task 9 report — final 4.0.0 release QA

## Status

Implementation and local QA are complete, with one release-matrix limitation: PHP 8.0 was not installed locally, so minimum-runtime PHPUnit/PHPCS could not be executed. PHP 8.3.30, JavaScript, package, clean-install, exact 3.3.1 upgrade, REST authorization, product persistence, frontend, YITH-present, responsive, and keyboard checks were executed.

Publication remains blocked on both the missing PHP 8.0 evidence and the explicit human approval gate. No push, PR, SVN operation, tag, upload, or publication was performed.

## Commits

- Task 9 target commit: `chore: prepare 4.0.0 release`
- Trailer: `Vault-Author: codex`
- Commit hash: the resulting local hash is reported in the Task 9 chat handoff (the hash cannot be embedded in the commit that creates this report without becoming self-referential). If the sandbox prevents the commit step, this report remains the handoff record.

## Changed scope

- `admin/src/settings/__tests__/app.test.js`: align the test with the canonical English `Display` msgid.
- `src/Services/InstallmentPriceFormatter.php`: mechanical indentation fix.
- `src/Setup/TextDomainLoader.php`: mechanical single-line call fix for mixed-line-ending PHPCS finding.
- `tests/ProductSettingsRepositoryTest.php`: regression assertion requiring a persistence call.
- `src/Repositories/ProductSettingsRepository.php`: persist REST product changes while allowing the WooCommerce standard-save fallback to defer persistence.
- `src/Admin/ProductOptionsController.php`: explicitly defer repository persistence inside `woocommerce_admin_process_product_object` to avoid a double save.
- `tests/ReleasePackageTest.php`: reject colocated `*.test.js` files in the distribution archive.
- `scripts/build-release.php`: exclude JavaScript tests during staging and reject them during final ZIP validation.
- `docs/release/4.0.0-qa.md`: full QA evidence and honest limitations.
- This report.

The untracked `wordpress-org-assets/screenshot-2-candidate.png` was not read as a release input, modified, staged, or included in the ZIP.

## Command log and exit codes

### Protocol, environment, and baseline

| Command / operation | Exit | Notes |
|---|---:|---|
| Read vault protocol, addon rules, Superpowers skills, and Task 9 brief | 0 | `.Codex/rules/addons.md` did not exist; used the available `.claude/rules/addons.md` |
| `git status --short --branch` / `git worktree list` | 0 | Correct linked worktree/branch; only candidate screenshot untracked |
| Discover `C:\laragon\bin\php` runtimes | 0 | 8.3.30 and 8.5.x found; no 8.0 runtime |
| `php-8.3.30\php.exe --version` | 0 | PHP 8.3.30 |

### Release assertions and Node matrix

| Command | Exit | Result |
|---|---:|---|
| `composer test -- --filter='ReleasePackageTest|ReleaseMetadataTest|PluginMetadataTest'` through Windows batch wrapper | 255 | Pipe in filter was interpreted by `cmd`; no tests ran |
| Composer PHAR focused command without PHP on child PATH | 1 | Composer script could not find `php`; no project failure inferred |
| Composer PHAR focused command with PHP 8.3 on PATH | 0 | 12 tests, 1,340 assertions, 1 skip |
| `npm ci` | 0 | 1,445 packages; audit reported 71 vulnerabilities |
| `npm run build:admin` | 0 | Both bundles built; existing Sass deprecation warnings |
| First `npm test -- --runInBand` | 1 | 1 stale-label test failed, 46 passed |
| `npm test -- admin/src/settings/__tests__/app.test.js --runInBand` | 0 | GREEN, 5/5 |
| Full `npm test -- --runInBand` after fix | 0 | 47/47 |
| Final full `npm test -- --runInBand` | 0 | 47/47 |

### Composer/PHP matrix

| Command | Exit | Result |
|---|---:|---|
| `composer validate --strict` via PHP 8.3 Composer PHAR | 0 | Valid composer.json/lock |
| First full `composer test` | 0 | 134 tests, 1,766 assertions, 1 skip |
| First full `composer phpcs` | 2 | Two inherited fixable findings from Task 8 |
| `composer phpcs` after mechanical fixes | 0 | Clean |
| Focused product persistence RED | 1 | Expected: save count 0, required 1 |
| First impacted GREEN attempt | 1 | Exposed double-save risk in two fallback tests |
| Final impacted suite `ProductSettingsRepositoryTest|SettingsRestControllerTest|ProductOptionsTest` | 0 | 23 tests, 80 assertions |
| Task 9 pre-review full `composer test` | 0 | 134 tests, 1,766 assertions, 1 skip |
| Final full `composer phpcs` | 0 | Clean |
| Final repeat of `composer validate --strict` | Not started | Execution approval-usage limit rejected the command before process launch; Composer files had not changed since the successful run |
| PHP 8.0 PHPUnit/PHPCS | Not run | No PHP 8.0 binary was present; explicitly blocking limitation |

### Package commands

| Command | Exit | Result |
|---|---:|---|
| Initial `php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0.zip` | 0 | Pre-fix artifact generated |
| Initial extract/hash/header/forbidden-content inspection | 0 | Reported 67 files and zero forbidden directory paths, but the matcher incorrectly missed colocated `public/js/frontend.test.js` |
| Rebuild after persistence fix | 0 | Pre-review SHA-256 `bf6330a41a0050a3c41970e86375137414aaea41eb2f8c79c952e2ef270cb263` |
| Fix round 1 focused RED | 1 | Failed on `darven-extra-price-info/public/js/frontend.test.js` |
| Fix round 1 focused GREEN | 0 | 1 test, 1,346 assertions |
| Fix round 1 package/metadata suite | 0 | 12 tests, 1,387 assertions, 1 skip |
| Fix round 1 `composer phpcs` | 0 | Clean |
| Fix round 1 full `composer test` | 0 | 134 tests, 1,813 assertions, 1 skip |
| Corrected final rebuild | 0 | 66 entries; SHA-256 `c93276a841ea03244b6203996df895a28331801a5db504b4c9eafdf4809a4261`; 186,570 bytes |
| Corrected direct ZIP inspection | 0 | Zero `*.test.js`, zero `tests/`, zero other forbidden paths |
| Install exact final ZIP on pre-existing QA site | 0 | Plugin 4.0.0 active |
| Install exact final ZIP on clean QA site | 0 | Plugin 4.0.0 active |

### Local WordPress QA commands

| Command / operation | Exit | Result |
|---|---:|---|
| Query original QA WP/WC/YITH/products/options | 0 | WP 7.0.2, WC 10.9.4, YITH 4.29.0 |
| Build clean QA filesystem from local WordPress/Woo/YITH files | timeout 124, then 0 | Initial broad copy timed out after core and partial Woo; idempotent plugin copies completed and source/destination counts matched |
| First clean `table_prefix` configuration with `--raw` | 255 | Operational mistake produced undefined-constant fatal before table creation |
| Correct string `table_prefix` and `wp core install` | 0 | Fresh isolated WordPress installed |
| Activate WooCommerce, YITH, and exact Darven ZIP | 0 | All active |
| Copy/activate Twenty Twenty-Five | 0 | Local frontend available without downloads |
| Create simple and variable QA products | 0 | IDs 10 and 11; variation ID 12 |
| Initial clean option list | 0 | No Darven option existed |
| Open React admin without save and repeat option list | 0 | Still no Darven option |
| React settings save | 0 | Schema 2 and four mirrors; no sync-state option |
| Anonymous settings GET/PUT and product PUT | curl exit 0 | HTTP 403 for all three |
| Initial product UI toggle | browser operation succeeded | UI reported success, but subsequent DB reads proved no meta persisted |
| Authenticated simple/variable PUT after fix | curl exit 0 | HTTP 200; canonical and two mirrors persisted |
| Temporary application-password cleanup | 0 | Credentials removed immediately |
| Disable `woocommerce_coming_soon` on QA | 0 | Anonymous frontend became testable |
| Switch/curl modes `default`, `popup`, `nofee` | 0 | Expected statement/toggle/dialog behavior; no PHP response errors |
| Browser keyboard check | 0 | Enter/open/focus and Escape/close/focus-return passed |
| Browser mobile viewport 375×812 | 0 | Dialog within viewport; scrollable table; no horizontal overflow |
| Browser console query | 0 | No warnings/errors |
| Clean debug.log inspection | 0 | One WooCommerce notice, zero Darven errors |

### Exact 3.3.1 upgrade

| Command / operation | Exit | Result |
|---|---:|---|
| Search local tag/ZIP/history | 0 | Found exact local 3.3.1 ZIP |
| Install 3.3.1 ZIP and seed legacy options/meta | 0 | Plugin active at 3.3.1 |
| Pre-upgrade fingerprint | 0 | `c131ad828064674f177bb7463e9409fd0b2e4f6d2de90c157b9fbf8f38bc3733` |
| Install final 4.0.0 ZIP | 0 | Fingerprint unchanged |
| Open 4.0.0 React admin without save | browser operation succeeded | Legacy values rendered; fingerprint unchanged; canonical remained null |
| React save changing discount 7→9 | 0 | Schema 2 created; all four mirrors preserved/updated |
| Product PUTs after upgrade | curl exit 0 | HTTP 200; simple/variable canonical + two mirrors persisted |
| Temporary upgrade application-password cleanup | 0 | Credential removed |

## Manual QA summary

- All four settings tabs rendered and were inspected.
- Clean open caused no writes; exact 3.3.1 upgrade open caused no writes.
- React save generated schema 2 and synchronized four legacy options.
- REST denied anonymous requests and accepted authorized requests.
- Simple and variable product editors exposed the Darven React controls.
- Product persistence bug was discovered, reproduced, fixed via TDD, and verified in the real database.
- YITH was active throughout the primary clean/frontend path.
- Default, popup, and no-fee modes rendered correctly.
- Popup keyboard focus lifecycle and mobile layout passed.
- Visible DOM contained one statement/toggle/dialog; no loop duplication was observed.
- No JavaScript console errors and no Darven PHP errors were observed.

## Blockers and limitations

1. **PHP 8.0 unavailable:** minimum-runtime PHPUnit/PHPCS remains unverified locally.
2. **Execution approval limit:** prevented only a redundant final Composer validation and may prevent the final Git shell step; the earlier validation passed and Composer files did not change.
3. `npm ci` reports 71 development-dependency vulnerabilities; resolving them is outside the release-preparation scope and may require breaking toolchain upgrades.
4. Webpack reports the existing Dart Sass legacy JS API deprecation.
5. Debug log contains one WooCommerce early-translation notice, not a Darven error.

## Risks

- Do not publish until PHP 8.0 is executed or the maintainer explicitly accepts that gap.
- Independently recompute the final ZIP hash after checkout/review and before publication.
- Review the new persistence contract carefully: REST persists immediately; the classic WooCommerce hook defers to WooCommerce's standard product save.
- Keep the candidate screenshot untracked and outside any release commit/package.

## Publication gate

Not crossed. No external action was taken.
