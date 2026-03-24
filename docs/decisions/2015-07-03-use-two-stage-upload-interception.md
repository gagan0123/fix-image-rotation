---
status: accepted
date: 2015-07-03
decision-makers: Gagan Deep Singh
---

# Use two-stage upload interception for image orientation fixing

## Context and Problem Statement

WordPress processes uploaded images through a pipeline with two hook points: `wp_handle_upload_prefilter` (before validation) and `wp_handle_upload` (after validation and file move). The plugin needs to fix image orientation based on EXIF data before WordPress generates thumbnails and stores metadata.

A single hook point is insufficient because:

- `wp_handle_upload_prefilter` operates on the temp file (`$file['tmp_name']`) before WordPress moves it to `wp-content/uploads/`. If the plugin only hooks here, edge cases where WordPress re-reads the file after moving could still see the wrong orientation.
- `wp_handle_upload` operates on the final file path (`$file['file']`), but by this point WordPress may have already read metadata from the un-fixed temp file.

Hooking into both stages ensures the image is corrected regardless of which stage WordPress or other plugins use for further processing.

## Decision

Hook into both `wp_handle_upload_prefilter` (priority 10) and `wp_handle_upload` (priority 1) to fix image orientation at two stages of the upload pipeline.

Use an internal `$orientation_fixed` array (keyed by file path) to track which files have already been processed, preventing duplicate rotation when both hooks fire for the same image.

**Non-goals:**

- Intercepting sideloaded images (`wp_handle_sideload`) — not currently needed
- Processing images on media regeneration — only handles initial uploads

## Consequences

- Good, because images are fixed at the earliest opportunity (prefilter), catching them before WordPress processes metadata
- Good, because the post-upload hook acts as a safety net, catching any images that slipped through
- Good, because the deduplication array prevents double-rotation which would produce incorrect results
- Bad, because the two-hook design adds complexity — contributors must understand why both hooks exist
- Neutral, because the `$orientation_fixed` array is instance-scoped and resets on each request, which is correct for the upload lifecycle

## Implementation Plan

- **Affected paths**: `includes/class-fix-image-rotation.php` — `register_hooks()`, `filter_wp_handle_upload_prefilter()`, `filter_wp_handle_upload()`, `fix_image_orientation()`
- **Dependencies**: None beyond WordPress core
- **Patterns to follow**: WordPress filter conventions — accept the `$file` array, return it unmodified after side-effecting the file on disk
- **Patterns to avoid**: Do not remove either hook without understanding the coverage gap it creates. Do not rely on file path identity between prefilter (tmp path) and post-upload (final path) — they are different paths, so both can fire without the dedup array blocking them

### Verification

- [x] Both `wp_handle_upload_prefilter` and `wp_handle_upload` are registered in `register_hooks()`
- [x] `fix_image_orientation()` checks `$orientation_fixed` before processing and sets it after
- [x] Uploading an image with EXIF orientation > 1 results in a correctly oriented image in the media library
- [x] The same image is not rotated twice (verified by integration tests checking final dimensions)

## Alternatives Considered

- **Single hook (prefilter only)**: Simpler, but misses files if another plugin modifies the upload path between prefilter and final save.
- **Single hook (post-upload only)**: Simpler, but WordPress may have already read incorrect metadata from the unrotated temp file.

## More Information

- WordPress upload pipeline: `wp_handle_upload()` in `wp-admin/includes/file.php`
- Initial two-hook design introduced in the original plugin code (commit `42000e0`)
- Deduplication tracking added during code restructure (commit `a2cf19a`)
