---
status: accepted
date: 2017-08-24
decision-makers: Gagan Deep Singh
---

# Restore GD Library metadata after image rotation

## Context and Problem Statement

WordPress supports two image processing backends: Imagick and GD Library. When the plugin rotates or flips an image to fix its orientation, Imagick preserves all EXIF and IPTC metadata in the saved file. GD Library, however, strips all metadata during image manipulation — the saved file loses EXIF data (camera model, GPS, date taken, etc.).

Many WordPress hosts only have GD Library available (Imagick requires the `imagick` PHP extension). Losing image metadata on upload is unacceptable — it breaks features like WordPress's built-in metadata display and any plugins that rely on EXIF data.

## Decision

When the image editor is `WP_Image_Editor_GD`, save the image metadata (via `wp_read_image_metadata()`) before rotation, then restore it by hooking into the `wp_read_image_metadata` filter after the file is saved.

The restored metadata has its `orientation` field set to `1` (correct/no rotation needed), since the image has already been physically rotated.

When the image editor is `WP_Image_Editor_Imagick`, no metadata preservation is needed — Imagick handles it natively.

**Non-goals:**

- Writing EXIF data back into the file bytes (PHP's GD doesn't support this; we intercept WordPress's metadata reading instead)
- Preserving metadata for non-JPEG formats

## Consequences

- Good, because image metadata (camera info, GPS, date taken) is preserved even when GD Library is the only available backend
- Good, because the approach uses WordPress's own `wp_read_image_metadata` filter, staying within the plugin API rather than manipulating raw file bytes
- Good, because `instanceof WP_Image_Editor_GD` makes the behavior explicit and only activates when needed
- Bad, because the metadata is only restored within WordPress's metadata pipeline — external tools reading the file directly will still see stripped metadata (GD limitation)
- Neutral, because the `$previous_meta` array is instance-scoped, matching the single-request upload lifecycle

## Implementation Plan

- **Affected paths**: `includes/class-fix-image-rotation.php` — `do_flip_and_rotate()`, `restore_meta_data()`
- **Dependencies**: `wp-admin/includes/image.php` for `wp_read_image_metadata()`
- **Patterns to follow**: Use `instanceof WP_Image_Editor_GD` (not string comparison of class names) to detect the GD backend. Store metadata keyed by file path for multi-image upload support.
- **Patterns to avoid**: Do not attempt to write EXIF data back into the JPEG file using PHP — there is no reliable cross-platform way to do this with GD. Do not apply the metadata filter when Imagick is in use (it preserves metadata natively).

### Verification

- [x] `do_flip_and_rotate()` checks `instanceof WP_Image_Editor_GD` before saving metadata
- [x] `restore_meta_data()` returns saved metadata with `orientation` set to `1`
- [x] When using GD Library, uploaded images retain their metadata after rotation
- [x] When using Imagick, no metadata interception occurs

## Alternatives Considered

- **Use a PHP EXIF-writing library**: No mature, maintained library exists for writing EXIF back into JPEG files in PHP without Imagick. Too fragile for a WordPress plugin.
- **Force Imagick requirement**: Would break the plugin for the many hosts that only offer GD. Unacceptable for a plugin targeting broad WordPress compatibility.
- **Accept metadata loss with GD**: Users on GD-only hosts would lose all image metadata. Rejected as too poor a user experience.

## More Information

- GD metadata stripping is a known PHP limitation: GD does not read or write EXIF/IPTC data
- Original implementation: commit `e81d57b` ("Added functionality to restore metadata of the images even if GD library is being used")
- `instanceof` check replaced string comparison in commit `a2fc71c` ("fix: improve error handling in image rotation pipeline")
