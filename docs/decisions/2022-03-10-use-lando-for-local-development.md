---
status: accepted
date: 2022-03-10
decision-makers: Gagan Deep Singh
---

# Use Lando for local development

## Context and Problem Statement

WordPress plugin development requires a full LAMP/LEMP stack (PHP, MySQL/MariaDB, web server) plus tooling (WP-CLI, PHPCS, Node.js, Composer). Setting this up manually is error-prone and creates "works on my machine" issues. Contributors need a reproducible environment that matches production constraints.

The plugin also needs to test with both GD Library and Imagick image backends, requiring the ability to toggle PHP extensions — something that's difficult with native installations.

## Decision

Use Lando (`.lando.yml`) for local development with a single appserver container providing:

- **Stack**: PHP 8.2, Node 22, MariaDB, nginx
- **WordPress**: Auto-installed at `./wordpress/` with the plugin mounted into `wp-content/plugins/`
- **Tooling**: `lando npm`, `lando node`, `lando wp`, `lando phpcs`, `lando phpcbf`, `lando composer`
- **Xdebug**: Off by default, toggleable with `lando xdebug debug|profile|trace|off`
- **Imagick**: Toggleable with `lando imagick` (enable) / `lando imagick off` (disable) for testing GD vs Imagick codepaths
- **Auto-setup**: `lando start` / `lando rebuild` automatically installs npm packages, PHPCS with WordPress Coding Standards, PHPUnit, and the WP test suite

**All build and test commands run inside Lando** (`lando npm run ...`, `lando phpunit`, `lando phpcs`), never on the host machine.

**Non-goals:**

- `.wp-env.json` support — deferred as an alternative for contributors who prefer it
- Docker Compose without Lando — Lando provides higher-level abstractions (tooling, events) that reduce boilerplate

## Consequences

- Good, because `lando start` gives a fully working WordPress environment with zero manual setup
- Good, because Imagick toggling enables testing both image editor backends without rebuilding
- Good, because Xdebug on-demand avoids the performance penalty of always-on debugging
- Good, because all contributors get identical environments regardless of host OS
- Bad, because Lando and Docker must be installed on the host (moderate system requirements)
- Neutral, because `wordpress/` is gitignored — it's a local artifact, not committed

## Implementation Plan

- **Affected paths**: `.lando.yml`, `.lando/php.ini`, `.lando/mysql.cnf`, `.lando/wp-cli.yml`, `.lando/xdebug.sh`, `.lando/imagick.sh`
- **Dependencies**: Lando (host), Docker (host)
- **Patterns to follow**: All tooling commands use the `lando` prefix. Custom scripts go in `.lando/`. Build events (post-start, post-rebuild) handle auto-installation.
- **Patterns to avoid**: Never run `npm`, `php`, `composer`, or `phpunit` directly on the host — always use `lando` prefix. Do not commit `wordpress/` directory.

### Verification

- [x] `lando start` creates a working WordPress site at `https://fix-image-rotation.lndo.site/`
- [x] `lando phpcs` runs PHPCS with WordPress Coding Standards
- [x] `lando npm run readme` generates README.md
- [x] `lando xdebug debug` enables Xdebug, `lando xdebug off` disables it
- [x] `lando imagick` enables Imagick, `lando imagick off` disables it (falls back to GD)

## Alternatives Considered

- **Manual LAMP/LEMP setup**: Each contributor manages their own PHP/MySQL installation. Rejected because of environment inconsistency and setup complexity.
- **wp-env (WordPress official)**: Lightweight Docker-based environment. Rejected at the time because it lacked custom tooling support (PHPCS, Imagick toggling). May be added as an alternative option later.
- **DDEV**: Similar to Lando. Rejected because the maintainer was already familiar with Lando; both tools are comparable.
- **Vagrant/VVV**: Heavier-weight VM approach. Rejected as slower and more resource-intensive than container-based solutions.

## More Information

- Initial Lando setup: commits `5d6c1e2` through `e7f89f8` (`.lando.yml` and support files)
- Major Lando config update: commit `4177dd5` ("Update Lando config: PHP 8.2, Node 22, on-demand xdebug, PHPCS/Grunt tooling")
- PHPUnit auto-setup added in commit `d0b7942` ("chore: auto-install PHPUnit and WP test suite in Lando setup")
