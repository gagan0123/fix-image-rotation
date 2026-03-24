---
status: accepted
date: 2026-03-23
decision-makers: Gagan Deep Singh
---

# Defer WebP and AVIF orientation support

## Context and Problem Statement

WordPress 5.8+ supports WebP uploads and WordPress 6.1+ supports AVIF. Both formats can contain EXIF orientation data. Users uploading WebP or AVIF images from mobile devices could experience the same rotation issues the plugin fixes for JPEG and TIFF.

Investigation revealed that PHP's `exif_read_data()` does **not** work with WebP or AVIF files — it returns `false` even on PHP 8.2+. While Imagick can read EXIF profiles from these formats via its own API, the plugin's architecture depends on PHP's native `exif_read_data()` for orientation detection.

## Decision

Defer WebP and AVIF support to a future release. Do not add `webp` or `avif` to the `SUPPORTED_EXTENSIONS` constant.

The technical reason: adding support requires building an Imagick-based fallback EXIF reader that bypasses `exif_read_data()`. This is a significant architectural change (the plugin currently has a single EXIF reading path) and introduces an Imagick hard dependency for these formats, which conflicts with the plugin's goal of working on GD-only hosts.

**Non-goals:**

- Partial WebP/AVIF support (Imagick-only) — would create confusing behavior where the plugin works for some formats only on some hosts
- Waiting for PHP to add WebP/AVIF support to `exif_read_data()` — no upstream plans for this exist

## Consequences

- Good, because the plugin remains simple with a single EXIF reading path (`exif_read_data()`)
- Good, because the plugin continues to work identically on both GD and Imagick hosts
- Bad, because WebP and AVIF images with incorrect orientation will not be fixed
- Neutral, because most mobile cameras still output JPEG; WebP/AVIF with orientation issues is uncommon in practice

## Implementation Plan

- **Affected paths**: `includes/class-fix-image-rotation.php` — `SUPPORTED_EXTENSIONS` constant
- **Dependencies**: None — this is a decision to NOT change
- **Patterns to follow**: Keep the `SUPPORTED_EXTENSIONS` constant as the single source of truth for which formats are processed
- **Patterns to avoid**: Do not add WebP/AVIF to `SUPPORTED_EXTENSIONS` without implementing the Imagick fallback reader. Do not silently attempt `exif_read_data()` on WebP/AVIF (it returns `false`, wasting cycles).

### Verification

- [x] `SUPPORTED_EXTENSIONS` contains only `jpg`, `jpeg`, `tiff`
- [x] WebP and AVIF files are not processed by the plugin
- [x] The improvement plan documents this as a deferred item with technical rationale

## Alternatives Considered

- **Imagick-only WebP/AVIF support**: Would work on Imagick hosts but silently do nothing on GD hosts. Rejected because inconsistent behavior across hosts is confusing and hard to debug for users.
- **Require Imagick for the plugin**: Would enable full WebP/AVIF support but break the plugin for GD-only hosts, which are common on shared hosting. Rejected.
- **Bundle a PHP EXIF library**: No mature PHP library exists that can read EXIF from WebP/AVIF without Imagick. Too risky for a stable plugin.

## More Information

- Research documented in `docs/improvement-plan.md` under "4. Add WebP and AVIF Support (Deferred)"
- PHP `exif_read_data()` documentation: only JPEG and TIFF are supported formats
- Revisit condition: if PHP adds `exif_read_data()` support for WebP/AVIF, or if a reliable pure-PHP EXIF reader becomes available
