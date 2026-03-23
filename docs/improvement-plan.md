# Fix Image Rotation — Improvement Plan

This document outlines planned improvements for the next release of the Fix Image Rotation plugin.

## ~~1. Update WordPress & PHP Compatibility~~ (Done)

- [x] Bump `Requires PHP` from `5.6` to `7.4`
- [x] Bump `Requires at least` from `3.7` to `6.2`
- [x] Bump `Tested up to` to `6.9`
- [x] Sync `package.json` version with plugin version (`2.0.0` → `2.2.2`)
- [x] Rename `.phpcs.xml.dist` → `phpcs.xml.dist`, add `wordpress/`/`vendor/`/`docs/` exclusions, add `minimum_supported_wp_version`, fix deprecated text_domain syntax

## ~~2. Add Automated Tests (PHPUnit)~~ (Done)

- [x] Set up PHPUnit with WordPress test framework (`phpunit.xml.dist`, `tests/bootstrap.php`, `bin/install-wp-tests.sh`)
- [x] Write unit tests (17 tests):
  - Singleton instantiation and identity
  - Hook registration with EXIF extension
  - File extension filtering (jpg, jpeg, tiff accepted; png, gif, webp skipped; uppercase handled)
  - `calculate_flip_and_rotate()` — all 8 EXIF orientations via data provider
  - `restore_meta_data()` — passthrough when no previous meta
- [x] Write integration tests (16 tests):
  - All landscape and portrait test images (orientations 2–8) rotated successfully
  - Orientation 1 images left unmodified
  - Duplicate processing prevention verified
- [x] All 33 tests passing with 48 assertions

## ~~3. Set Up GitHub Actions CI~~ (Done)

- [x] Create `.github/workflows/phpcs.yml` — PHPCS on every push/PR with checkstyle annotations
- [x] Create `.github/workflows/tests.yml` — PHPUnit on PHP 7.4–8.3 matrix with WP latest, 6.2, and nightly
- [ ] Remove `.gitlab-ci.yml` once GitHub Actions are verified working
- [ ] Add status badges to README

## 4. Add WebP and AVIF Support (Deferred)

**Research findings:** PHP's `exif_read_data()` does **not** work with WebP or AVIF in practice (tested on PHP 8.2). While Imagick can read EXIF profiles from these formats, PHP's native function returns `false`. Both GD and Imagick support reading/writing WebP and AVIF images, but the EXIF orientation data is inaccessible through `exif_read_data()`.

Adding support would require an Imagick-based fallback EXIF reader, which is a larger architectural change deferred to a future release.

- [x] Research EXIF support in WebP and AVIF — **not feasible with `exif_read_data()`**
- [ ] (Future) Implement Imagick-based EXIF orientation reader as fallback
- [ ] (Future) Add `webp` and `avif` to allowed extensions with Imagick fallback
- [ ] (Future) Add test images in WebP/AVIF formats

## ~~5. Modernize PHP Code~~ (Done)

- [x] Add type declarations to all method parameters and return types
- [x] Add typed class properties with default values (PHP 7.4+)
- [x] Extract supported extensions to a class constant (`SUPPORTED_EXTENSIONS`)
- [x] Remove unnecessary constructor (properties use inline defaults)
- [x] Fix `add_filter` accepted_args from 3 to 1 (callback only uses first param)
- Note: Short array syntax `[]` not used — WordPress Coding Standards disallow it

## ~~6. Add Static Analysis~~ (Done)

- [x] Add `composer.json` with `phpstan/phpstan` and `szepeviktor/phpstan-wordpress` as dev dependencies
- [x] Create `phpstan.neon` configuration at level 5
- [x] Add PHPStan GitHub Actions workflow
- [x] Clean pass at level 5 — no errors

## ~~7. Improve Error Handling~~ (Done)

- [x] Check `is_wp_error( $editor )` before calling `get_class( $editor )` in `do_flip_and_rotate()`
- [x] Use `instanceof WP_Image_Editor_GD` instead of `get_class()` comparison
- [x] Handle `exif_read_data()` returning `false` gracefully
- [x] Log rotation failures via `error_log()` when `WP_DEBUG` is enabled
- [x] Handle `$editor->rotate()`, `$editor->flip()`, and `$editor->save()` return values (all can return `WP_Error`)

## ~~8. Improve Build & Release Tooling~~ (Done)

- [x] Add `composer.json` for PHP dependency management (PHPStan)
- [x] Add GitHub Actions release workflow (`deploy.yml`) — deploys to WordPress.org SVN on tag push via `10up/action-wordpress-plugin-deploy`, with version consistency verification
- [x] Update GitLab CI — add PHPUnit test matrix (PHP 7.4–8.4, WP latest/nightly/6.2), update PHPCS to PHP 8.2
- [ ] Consider replacing Grunt with npm scripts (deferred — low priority)
- [ ] Add `.wp-env.json` as alternative local dev option (deferred)

## ~~9. Update Plugin Documentation~~ (Done)

- [x] Rewrite `readme.txt` description to explain why the plugin is still needed despite WordPress 5.3+
- [x] Add supported image formats and requirements sections
- [x] Update the `== Installation ==` section with modern instructions
- [x] Add FAQ entry about EXIF extension not being available
- [x] Regenerate `README.md` from `readme.txt`
- [x] Update `rsync-excludes.txt` to exclude new dev files (`.github/`, `docs/`, `AGENTS.md`, `vendor/`, etc.)
- [ ] Add a FAQ entry about WebP/AVIF support (once implemented)

## ~~10. Housekeeping~~ (Done)

- [x] Add `.editorconfig` for consistent editor settings across contributors
- [x] Add `.gitattributes` to mark binary files, enforce line endings, and define export-ignore
- [x] Update `.gitignore` to include `vendor/`
- [x] Update `rsync-excludes.txt` to exclude all dev files

---

## Priority Order

| Priority | Item | Rationale |
|----------|------|-----------|
| ~~P0~~ | ~~1. Update compatibility versions~~ | ~~Done~~ |
| ~~P0~~ | ~~7. Fix error handling bugs~~ | ~~Done~~ |
| ~~P1~~ | ~~2. Automated tests~~ | ~~Done — 33 tests, 48 assertions~~ |
| ~~P1~~ | ~~3. GitHub Actions CI~~ | ~~Done — PHPCS + PHPUnit matrix~~ |
| ~~P1~~ | ~~9. Update documentation~~ | ~~Done~~ |
| P2 | 4. WebP/AVIF support | Deferred — `exif_read_data()` doesn't support WebP/AVIF |
| ~~P2~~ | ~~5. Modernize PHP~~ | ~~Done — typed properties, return types, const~~ |
| ~~P2~~ | ~~6. Static analysis~~ | ~~Done — PHPStan level 5, clean pass~~ |
| ~~P3~~ | ~~8. Build & release tooling~~ | ~~Done — GH Actions deploy, GitLab CI tests~~ |
| ~~P3~~ | ~~10. Housekeeping~~ | ~~Done — .editorconfig, .gitattributes~~ |

## Target Release

All P0 and P1 items should be completed before the next release. P2 items are stretch goals for this release, and P3 items can land whenever convenient.
