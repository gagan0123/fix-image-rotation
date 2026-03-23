# Fix Image Rotation — Improvement Plan

This document outlines planned improvements for the next release of the Fix Image Rotation plugin.

## 1. Update WordPress & PHP Compatibility

- [ ] Bump `Requires PHP` from `5.6` to `7.4` (WordPress 6.7 itself requires PHP 7.2.24+, and 7.4 is a reasonable baseline for modern hosting)
- [ ] Bump `Requires at least` from `3.7` to `6.2`
- [ ] Bump `Tested up to` to the latest WordPress version
- [ ] Sync `package.json` version with plugin version (currently `2.0.0` vs `2.2.2`)

## 2. Add Automated Tests (PHPUnit)

The plugin has test images (`tests/test-images/`) but no automated test suite.

- [ ] Add `composer.json` with `phpunit`, `wp-phpunit`, and `brain/monkey` (or `wp-env`) as dev dependencies
- [ ] Set up PHPUnit bootstrap with WordPress test framework
- [ ] Write unit tests:
  - `calculate_flip_and_rotate()` — verify correct rotation/flip values for each EXIF orientation (1–8)
  - `restore_meta_data()` — verify metadata restoration with correct orientation reset
  - `filter_wp_handle_upload_prefilter()` / `filter_wp_handle_upload()` — verify file extension filtering (jpg, jpeg, tiff accepted; png, gif, webp skipped)
  - `display_exif_error()` — verify correct error messages when EXIF unavailable
- [ ] Write integration tests:
  - Upload test images (from `tests/test-images/`) through the WordPress upload pipeline and verify orientation is corrected
  - Test with both GD and Imagick image editors
  - Verify metadata is preserved after rotation (GD path)
- [ ] Add `phpunit.xml.dist` configuration file

## 3. Set Up GitHub Actions CI

Replace GitLab CI (`.gitlab-ci.yml`) with GitHub Actions since GitHub is the primary repository.

- [ ] Create `.github/workflows/phpcs.yml` — run PHPCS on every push/PR
- [ ] Create `.github/workflows/tests.yml` — run PHPUnit on a matrix of PHP versions (7.4, 8.0, 8.1, 8.2, 8.3) and WordPress versions (latest, latest-1)
- [ ] Remove `.gitlab-ci.yml` once GitHub Actions are in place
- [ ] Add status badges to README

## 4. Add WebP and AVIF Support

Modern image formats are now common in WordPress (WebP since WP 5.8, AVIF since WP 6.5). Both formats support EXIF metadata.

- [ ] Research EXIF support in WebP and AVIF (PHP's `exif_read_data()` added WebP support in PHP 7.2)
- [ ] Add `webp` to the allowed file extensions list if `exif_read_data()` can handle it
- [ ] Add `avif` to the allowed file extensions list if supported
- [ ] Add test images in WebP/AVIF formats with EXIF orientation data
- [ ] Test with both GD and Imagick editors

## 5. Modernize PHP Code

Adopt modern PHP patterns while keeping the minimum PHP requirement at 7.4.

- [ ] Add type declarations to method parameters and return types
- [ ] Replace `array()` with short array syntax `[]`
- [ ] Use null coalescing operator (`??`) and null safe operator (`?->`) where appropriate
- [ ] Add typed class properties (PHP 7.4+)
- [ ] Consider replacing singleton pattern with a simple function-based initialization (more WordPress-idiomatic)

## 6. Add Static Analysis

- [ ] Add `composer.json` with `phpstan/phpstan` and `szepeviktor/phpstan-wordpress` as dev dependencies
- [ ] Create `phpstan.neon` configuration targeting level 5+ (increase over time)
- [ ] Add PHPStan to the GitHub Actions CI pipeline
- [ ] Fix any issues found during initial analysis

## 7. Improve Error Handling

- [ ] Check `is_wp_error( $editor )` before calling `get_class( $editor )` in `do_flip_and_rotate()` (currently checks after)
- [ ] Handle `exif_read_data()` returning `false` gracefully (currently only checks `isset`)
- [ ] Log rotation failures via `error_log()` or `wp_trigger_error()` when `WP_DEBUG` is enabled
- [ ] Handle `$editor->rotate()` and `$editor->flip()` return values (both can return `WP_Error`)

## 8. Improve Build & Release Tooling

- [ ] Add a `composer.json` for PHP dependency management (PHPUnit, PHPStan, PHPCS)
- [ ] Consider replacing Grunt with npm scripts (the two Grunt tasks — readme conversion and i18n pot generation — can be done via standalone CLI tools)
- [ ] Add a `.wp-env.json` as an alternative local dev option (simpler than Lando for quick testing)
- [ ] Automate version bumping across `init.php`, `readme.txt`, and `package.json`
- [ ] Add a GitHub Actions release workflow that deploys to WordPress.org SVN on tag push

## 9. Update Plugin Documentation

- [ ] Update `readme.txt` description to better explain why the plugin is still needed despite WordPress 5.3+ handling
- [ ] Add a FAQ entry about WebP/AVIF support (once implemented)
- [ ] Update the `== Installation ==` section with modern instructions
- [ ] Regenerate `README.md` from `readme.txt` after updates

## 10. Housekeeping

- [ ] Add `.editorconfig` for consistent editor settings across contributors
- [ ] Add `.gitattributes` to mark binary files and enforce line endings
- [ ] Update `.gitignore` to include `vendor/`
- [ ] Update `rsync-excludes.txt` to exclude new dev files (`docs/`, `composer.json`, `composer.lock`, `phpstan.neon`, `phpunit.xml.dist`, `.github/`, etc.)

---

## Priority Order

| Priority | Item | Rationale |
|----------|------|-----------|
| P0 | 1. Update compatibility versions | Users see outdated "Tested up to" and skip the plugin |
| P0 | 7. Fix error handling bugs | `is_wp_error` check is in wrong order — potential fatal |
| P1 | 2. Automated tests | Foundation for safe refactoring |
| P1 | 3. GitHub Actions CI | Ensures quality on every PR |
| P1 | 9. Update documentation | Accompanies the new release |
| P2 | 4. WebP/AVIF support | Most impactful new feature |
| P2 | 5. Modernize PHP | Better DX, type safety |
| P2 | 6. Static analysis | Catches bugs early |
| P3 | 8. Build & release tooling | Nice-to-have automation |
| P3 | 10. Housekeeping | Contributor experience |

## Target Release

All P0 and P1 items should be completed before the next release. P2 items are stretch goals for this release, and P3 items can land whenever convenient.
