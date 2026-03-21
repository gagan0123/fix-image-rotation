# AGENTS.md

## Project Overview

**Fix Image Rotation** is a WordPress plugin that automatically corrects image orientation based on EXIF metadata. It fixes the common issue where photos (especially from mobile devices) appear rotated after uploading to WordPress.

## Architecture

### Entry Point

- `init.php` — Plugin bootstrap. Defines `GS_FIR_PATH`, includes the main class, and calls `register_hooks()`.

### Core Logic

- `includes/class-fix-image-rotation.php` — Singleton class containing all plugin logic:
  - **`register_hooks()`** — Checks for EXIF extension and registers WordPress filters.
  - **`filter_wp_handle_upload_prefilter()`** / **`filter_wp_handle_upload()`** — Hook into WordPress upload pipeline to process images before they are stored.
  - **`fix_image_orientation()`** — Reads EXIF data and orchestrates the rotation/flip.
  - **`calculate_flip_and_rotate()`** — Maps EXIF orientation values (1–9) to rotation angles and flip operations.
  - **`do_flip_and_rotate()`** — Applies transformations using the WordPress image editor (Imagick or GD).
  - **`restore_meta_data()`** — Restores image metadata stripped by the GD library after processing.

### Key Design Decisions

- **Singleton pattern** — Only one instance of the plugin class exists.
- **Dual library support** — Works with both Imagick and GD image processing libraries. GD strips metadata, so the plugin saves and restores it.
- **Two filter hooks** — Processes images at both `wp_handle_upload_prefilter` and `wp_handle_upload` to cover different upload paths.
- **Supported formats** — JPG, JPEG, and TIFF only (formats that carry EXIF data).

## Development Setup

### Prerequisites

- PHP 5.6+ with EXIF extension enabled
- Node.js (for Grunt build tasks)
- Lando (optional, for local WordPress environment)

### Local Environment

```bash
lando start          # Start WordPress environment (Nginx, PHP 7.4, MariaDB)
lando imagick        # Toggle Imagick extension on/off
lando xdebug         # Toggle Xdebug modes
npm install          # Install Grunt dependencies
```

### Build Commands

```bash
npx grunt readme     # Convert readme.txt → README.md
npx grunt makepot    # Generate translation template
npx grunt watch      # Watch for file changes
```

## Code Quality

- **Linter:** PHPCS with WordPress Coding Standards (`.phpcs.xml.dist`)
- **Run checks:** `phpcs` (configured via `.phpcs.xml.dist`)
- **Text domain:** `fix-image-rotation` (enforced by PHPCS)

## Testing

There are no automated unit/integration tests. The `tests/test-images/` directory contains 16 EXIF-oriented images (8 landscape, 8 portrait) for manual validation of rotation behavior.

## CI/CD

Configured via `.gitlab-ci.yml`:

1. **verify** — Runs PHPCS on PHP 7.2
2. **deploy** — Manual SVN deployment to WordPress.org via `bin/release-plugin.sh`

## File Conventions

- `index.php` files in directories are empty security files (prevent directory listing) — do not modify.
- `readme.txt` is the source of truth for the WordPress.org listing; `README.md` is auto-generated from it.
- Version must be kept in sync across `init.php`, `readme.txt`, and `package.json`.

## Release Process

1. Update version in `init.php`, `readme.txt`, and `package.json`.
2. Update changelog in `readme.txt`.
3. Run `npx grunt readme` to regenerate `README.md`.
4. Commit and push.
5. Trigger the manual "Deploy" job in GitLab CI, which runs `bin/release-plugin.sh` to push to the WordPress.org SVN repository.
