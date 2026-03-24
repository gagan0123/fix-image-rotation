---
status: accepted
date: 2026-03-24
decision-makers: Gagan Deep Singh
---

# Adopt PHPStan static analysis at level 5

## Context and Problem Statement

After modernizing the PHP code with typed properties and return types, the codebase needed a way to verify type correctness beyond what PHPCS can catch. PHPCS enforces coding style; it does not perform type-level analysis like detecting incorrect argument types, missing null checks, or unreachable code.

The plugin interacts heavily with WordPress functions that return mixed types (`WP_Error|WP_Image_Editor`, `array|false`), making it prone to type errors that only surface at runtime.

## Decision

Adopt PHPStan at **level 5** with the `szepeviktor/phpstan-wordpress` extension for WordPress-aware type stubs.

- **Level 5** checks: type inference, function signatures, return types, dead code, missing typehints on parameters, and basic PHPDoc validation. This is the highest level that passes cleanly without requiring extensive PHPDoc annotations on WordPress core functions.
- **`szepeviktor/phpstan-wordpress`** provides type stubs for WordPress core functions (e.g., `wp_get_image_editor()` returns `WP_Image_Editor|WP_Error`), enabling accurate analysis without custom stubs.

Configuration in `phpstan.neon`:
- Analyze `init.php` and `includes/` only
- Exclude `tests/`, `vendor/`, `wordpress/`, `node_modules/`

**Non-goals:**

- PHPStan level 6+ — would require extensive PHPDoc annotations for array shapes and generics, disproportionate effort for a small plugin
- Custom PHPStan rules — the standard ruleset is sufficient

## Consequences

- Good, because type errors are caught at CI time rather than runtime
- Good, because WordPress function return types are correctly modeled (e.g., `WP_Error` checks on image editor operations)
- Good, because it validated the error handling improvements — PHPStan confirmed all `is_wp_error()` checks are on correct paths
- Bad, because Composer is now a dev dependency (needed for PHPStan and its WordPress extension)
- Neutral, because level 5 is a pragmatic ceiling — higher levels would require diminishing-return type annotations

## Implementation Plan

- **Affected paths**: `composer.json`, `composer.lock`, `phpstan.neon`, `.github/workflows/phpstan.yml`
- **Dependencies**: `phpstan/phpstan` (dev), `szepeviktor/phpstan-wordpress` (dev), installed via Composer
- **Patterns to follow**: Run `lando composer install` for local analysis. All new PHP code must pass PHPStan level 5. Add `@var` or `@param` annotations only when PHPStan cannot infer types.
- **Patterns to avoid**: Do not add `@phpstan-ignore` comments without justification. Do not lower the analysis level. Do not add PHPStan baseline files to suppress existing errors — fix them instead.

### Verification

- [x] `phpstan.neon` is configured at level 5 with WordPress extension
- [x] `composer.json` includes `phpstan/phpstan` and `szepeviktor/phpstan-wordpress` as dev dependencies
- [x] `lando composer exec phpstan analyse` passes with 0 errors
- [x] GitHub Actions workflow runs PHPStan on every push/PR
- [x] `vendor/` is excluded from git and from plugin deployment

## Alternatives Considered

- **Psalm**: Another PHP static analyzer with similar capabilities. Rejected because PHPStan has better WordPress ecosystem support via `szepeviktor/phpstan-wordpress`.
- **PHPStan level 8+**: Maximum strictness. Rejected because it requires exhaustive PHPDoc annotations for array shapes — disproportionate effort for a 350-line plugin.
- **PHPCS only**: PHPCS catches style issues but not type errors. Insufficient for a plugin that handles mixed-type WordPress API returns.

## More Information

- Implemented in commit `cd6efc2` ("build: add PHPStan static analysis at level 5")
- PHPStan documentation: https://phpstan.org/
- WordPress extension: https://github.com/szepeviktor/phpstan-wordpress
