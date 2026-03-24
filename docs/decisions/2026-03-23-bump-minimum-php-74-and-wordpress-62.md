---
status: superseded by [Revert minimum PHP to 5.6](2026-03-25-revert-minimum-php-to-56.md)
date: 2026-03-23
decision-makers: Gagan Deep Singh
---

# Bump minimum requirements to PHP 7.4 and WordPress 6.2

## Context and Problem Statement

The plugin's minimum requirements were PHP 5.6 and WordPress 3.7, set when the plugin was first released. These versions are long past end-of-life:

- PHP 5.6 reached EOL in December 2018
- WordPress 3.7 was released in October 2013

Supporting ancient versions prevented using modern PHP features (typed properties, union types, null coalescing) and modern WordPress APIs. WordPress.org's own stats show negligible usage of PHP < 7.4 and WP < 6.0.

The plugin needed to modernize its codebase (typed properties, return types, class constants) which requires PHP 7.4+ language features.

## Decision

Bump minimum requirements to:

- **PHP 7.4** — the oldest version still receiving community security patches at the time of decision, and the minimum needed for typed properties and arrow functions
- **WordPress 6.2** — a recent LTS-like release that ensures the WordPress image handling APIs the plugin depends on are fully mature

Update all locations where versions are declared:

- `init.php` plugin header (`Requires at least`, `Requires PHP`)
- `readme.txt` (`Requires at least`, `Requires PHP`)
- `phpcs.xml.dist` (`minimum_supported_wp_version`)
- `package.json` (documentation)

**Non-goals:**

- Requiring PHP 8.0+ — would exclude too many shared hosting environments
- Requiring WordPress 6.5+ — no API dependency that necessitates it

## Consequences

- Good, because PHP 7.4 typed properties and return types make the code safer and more self-documenting
- Good, because dropping PHP 5.6/7.x support removes the need for polyfills and backward-compatible patterns
- Good, because WordPress 6.2+ ensures mature image editor APIs and upload handling
- Good, because PHPCS can enforce WordPress 6.2+ coding standards
- Bad, because users on very old PHP/WordPress versions will no longer receive updates (negligible user base)

## Implementation Plan

- **Affected paths**: `init.php`, `readme.txt`, `README.md` (auto-generated), `phpcs.xml.dist`, `package.json`
- **Dependencies**: None
- **Patterns to follow**: Always update ALL version locations in sync. Run `lando npm run readme` after changing `readme.txt` to regenerate `README.md`.
- **Patterns to avoid**: Do not update one version location without updating the others. Do not use PHP 8.0+ only features (e.g., named arguments, match expressions) since the minimum is 7.4.

### Verification

- [x] `init.php` header shows `Requires PHP: 7.4` and `Requires at least: 6.2`
- [x] `readme.txt` shows matching version requirements
- [x] `phpcs.xml.dist` has `minimum_supported_wp_version` set to `6.2`
- [x] CI matrix tests PHP 7.4 as the minimum version
- [x] Plugin code uses PHP 7.4 features (typed properties, return types)

## Alternatives Considered

- **PHP 8.0 minimum**: Would enable named arguments, union types, and match expressions. Rejected because PHP 7.4 still has significant usage on shared hosting.
- **WordPress 6.0 minimum**: Slightly broader compatibility. Rejected because 6.2 is only marginally newer and provides a cleaner baseline for the image handling APIs.
- **Keep PHP 5.6 / WP 3.7**: Would prevent any modernization. Rejected.

## More Information

- Implemented in commit `77c9852` ("chore: update version requirements and PHPCS config")
- PHP modernization that followed: commit `3d4a16e` ("refactor: modernize PHP code with types, typed properties, and const")
- WordPress PHP compatibility stats: https://wordpress.org/about/stats/
