# Agents Guide — Fix Image Rotation

## Project Overview

A WordPress plugin that automatically fixes image orientation based on EXIF data. When images are uploaded with an EXIF Orientation flag > 1 (common with mobile phone photos, especially iPhones), the plugin rotates/flips the image before WordPress processes it. Though WordPress 5.3+ included image rotation handling, it is still broken and doesn't handle edge cases that this plugin addresses.

- **Slug**: `fix-image-rotation`
- **Text Domain**: `fix-image-rotation`
- **Minimum WordPress**: 6.2
- **Minimum PHP**: 7.4
- **Versioning**: [Semantic Versioning](https://semver.org/) (MAJOR.MINOR.PATCH)
- **License**: GPLv2

## File Structure

```
init.php                               # Main plugin entry point (plugin header, loads class)
includes/
  class-fix-image-rotation.php         # Core plugin class (singleton, EXIF reading, rotation/flip logic)
  index.php                            # Security file (silence is golden)
index.php                              # Security file (silence is golden)
languages/
  fix-image-rotation.pot               # Translation template
tests/
  test-images/                         # 16 test JPEGs (8 landscape + 8 portrait, one per EXIF orientation 1–8)
assets/                                # Plugin branding images for WordPress.org directory
bin/
  release-plugin.sh                    # SVN deployment to WordPress.org
  rsync-excludes.txt                   # Files excluded from SVN deployment
Gruntfile.js                           # Build tasks: readme-to-markdown, i18n pot generation, watch
package.json                           # NPM dependencies (Grunt and plugins)
readme.txt                             # WordPress.org plugin readme
README.md                              # GitHub readme (generated from readme.txt via Grunt)
phpcs.xml.dist                         # PHPCS configuration for WordPress coding standards
.gitlab-ci.yml                         # GitLab CI/CD pipeline (PHPCS + manual deploy)
.lando.yml                             # Lando local development configuration
.lando/                                # Lando support files (php.ini, mysql.cnf, wp-cli.yml, xdebug.sh, imagick.sh)
docs/
  improvement-plan.md                  # Improvement roadmap for next release
```

### Directories to ignore

- `node_modules/` — Node dependencies
- `vendor/` — Composer dependencies
- `wordpress/` — Local WordPress installation (created by Lando)
- `profiler-output/` — Xdebug profiler output
- `assets/` — Plugin branding images for WordPress.org directory

## Coding Standards

This project follows the **WordPress Coding Standards** enforced via PHPCS.

- Run `lando phpcs` to check coding standards
- Run `lando phpcbf` for auto-fixable violations
- All PHP must comply with the `WordPress` ruleset
- Text domain `fix-image-rotation` is enforced by PHPCS

### Naming Conventions

- **Class name**: `Fix_Image_Rotation`
- **Text domain**: `fix-image-rotation` — all translatable strings must use this

## Architecture

The plugin uses a single class with a singleton pattern:

1. **Entry Point** (`init.php`) — Plugin header metadata, ABSPATH check, requires the class file and calls `register_hooks()`
2. **Core Class** (`Fix_Image_Rotation`) — Singleton that handles all plugin logic:
   - `register_hooks()` — Checks for EXIF extension availability. If available, registers upload filters; otherwise shows admin notice
   - `filter_wp_handle_upload_prefilter()` — Pre-upload filter, processes temp file before WordPress validation
   - `filter_wp_handle_upload()` — Post-upload filter, processes final uploaded file
   - `fix_image_orientation()` — Reads EXIF data, determines if rotation is needed, orchestrates the fix
   - `calculate_flip_and_rotate()` — Maps EXIF Orientation values (1–8) to rotation angles and flip operations
   - `do_flip_and_rotate()` — Uses `wp_get_image_editor()` to apply rotation/flip and save
   - `restore_meta_data()` — Restores image metadata stripped by GD Library (Imagick preserves metadata natively)
   - `display_exif_error()` — Admin notice when EXIF extension is unavailable

### Hooks Used

| Hook | Type | Purpose |
|------|------|---------|
| `wp_handle_upload_prefilter` | Filter | Process temp file before upload validation |
| `wp_handle_upload` | Filter | Process final uploaded file |
| `wp_read_image_metadata` | Filter | Restore metadata after GD Library rotation |
| `admin_notices` | Action | Display EXIF extension error |

### EXIF Orientation Mapping

| Value | Operation |
|-------|-----------|
| 1 | No change (already correct) |
| 2 | Flip horizontal |
| 3 | Rotate 180° |
| 4 | Flip vertical |
| 5 | Rotate -90° + flip horizontal |
| 6 | Rotate -90° |
| 7 | Rotate -270° + flip horizontal |
| 8/9 | Rotate -270° |

### Supported File Types

Currently: `jpg`, `jpeg`, `tiff` (extension checked case-insensitively).
PNG is excluded because PNG does not support EXIF data.

### Image Editor Handling

The plugin works with both WordPress image editors:
- **Imagick** — Preserves image metadata natively, no extra work needed
- **GD Library** — Strips metadata during rotation. The plugin saves metadata before processing and restores it via the `wp_read_image_metadata` filter, setting Orientation to 1

### PHP Extension Requirements

- **exif** — Must be loaded and `exif_read_data()` must be callable
- **gd** or **imagick** — For image manipulation (WordPress handles selection)

## Security

- All PHP files include ABSPATH check to prevent direct access
- Output escaping with `esc_html()` on admin notices
- Translatable strings use proper WordPress i18n functions

## Build System

All build commands MUST run inside the Lando container (via `lando` prefix), never on the local machine. The appserver container has both PHP and Node.js installed.

Grunt handles build tasks:

- **`lando grunt readme`** — Converts `readme.txt` to `README.md`
- **`lando grunt makepot`** — Generates POT translation file
- **`lando grunt watch`** — Watches for file changes during development
- **`lando grunt`** (default) — Runs both `readme` and `makepot`

## Deployment

The version must be updated in these places and kept in sync:
- `init.php` — plugin header (`Version:`)
- `readme.txt` — `Stable tag:` field
- `README.md` — `Stable tag:` field (auto-generated by Grunt)
- `package.json` — `version` field

`bin/release-plugin.sh` handles releases to WordPress.org SVN. It:
1. Verifies version matches between `readme.txt` and `init.php`
2. Checks out the SVN repo
3. Syncs assets and plugin files
4. Creates a version tag

### CI/CD Pipeline (GitLab)

Two stages:
1. **Verify** — PHPCS coding standards check (PHP 7.2)
2. **Deploy** — Manual deployment to WordPress.org SVN

## Local Development

Lando is used for local development (`lando start`). All commands run inside the container — do NOT use bare `npm`, `node`, `grunt`, or `php` on the host.

- **URL**: https://fix-image-rotation.lndo.site/
- **Admin login**: admin / password
- **Stack**: PHP 8.2, Node 22, MariaDB, nginx — all in a single appserver container
- **Tooling**: `lando npm`, `lando node`, `lando grunt`, `lando wp`, `lando phpcs`, `lando phpcbf`, `lando xdebug <mode>`, `lando imagick`
- **Xdebug**: Disabled by default; enable with `lando xdebug debug` (or `profile`, `trace`)
- **Imagick**: Toggle with `lando imagick` (enable) / `lando imagick off` (disable) — useful for testing GD vs Imagick codepaths
- **Auto-install**: `npm install`, PHPCS with WordPress Coding Standards all install automatically during `lando start` / `lando rebuild`
- WordPress is installed at `./wordpress/` (gitignored), plugin is mounted into `wp-content/plugins/`

## Version Requirements

When changing version requirements, update **all** of these locations:
- `init.php` — plugin header fields (`Requires at least`, `Requires PHP`)
- `readme.txt` — `Requires at least` and `Requires PHP` fields
- `README.md` — `Requires at least` and `Requires PHP` fields (auto-generated)
- `.phpcs.xml.dist` — `minimum_supported_wp_version` config value
- `AGENTS.md` — version numbers in Project Overview

## Important Notes

- **No settings page** — Plugin works out of the box with zero configuration
- **Duplicate processing prevention** — Tracks processed files in `$orientation_fixed` array to avoid re-processing
- **Two-stage processing** — Hooks into both pre-filter and post-filter upload stages for thorough coverage
- After editing `readme.txt`, run `lando grunt readme` and commit the updated `README.md`

## Git Commit Guidelines
- **No Co-Authored-By:** Never add a `Co-Authored-By` trailer to any git commit. Do not credit yourself or any AI agent in commit messages.
- **Conventional Commits:** All commit messages must follow the Conventional Commits specification. The format is:
  ```
  <type>[optional scope]: <description>

  [optional body]

  [optional footer(s)]
  ```
- **Types:**
  - `feat` — a new feature (correlates with a MINOR version bump)
  - `fix` — a bug fix (correlates with a PATCH version bump)
  - `docs` — documentation-only changes
  - `style` — formatting, whitespace, etc. (no code logic change)
  - `refactor` — code restructuring without changing behavior
  - `perf` — performance improvements
  - `test` — adding or updating tests
  - `build` — changes to build system or dependencies
  - `ci` — CI/CD configuration changes
  - `chore` — other maintenance tasks
- **Scope:** An optional noun in parentheses after the type describing the section of the codebase affected (e.g., `fix(upload):`, `feat(webp):`).
- **Description:** A short imperative summary immediately after the colon and space.
- **Body:** Optional. Separated from the description by a blank line. Provides additional context or motivation.
- **Footer(s):** Optional. Separated from the body by a blank line. Use git trailer format (`token: value` or `token #value`).
- **Breaking Changes:** Append `!` after the type/scope (e.g., `feat!:` or `refactor(api)!:`) and/or add a `BREAKING CHANGE:` footer. Breaking changes correlate with a MAJOR version bump.
