---
status: accepted
date: 2026-03-25
decision-makers: Gagan Deep Singh
---

# Revert minimum PHP requirement to 5.6

## Context and Problem Statement

The plugin's minimum PHP was bumped to 7.4 in a recent decision ([2026-03-23-bump-minimum-php-74-and-wordpress-62.md](2026-03-23-bump-minimum-php-74-and-wordpress-62.md)) to enable modern PHP syntax (typed properties, return type declarations, class constant visibility).

However, the plugin's core purpose is fixing image rotation on WordPress versions that lack built-in rotation support. WordPress only added image rotation handling in 5.3, and even that implementation is incomplete. The plugin supports WordPress 3.7+, and those older WordPress installations commonly run on PHP 5.6. Requiring PHP 7.4 effectively locks out the users who need the plugin most — those on older environments where WordPress does not handle image orientation at all.

## Decision

Revert the minimum PHP requirement from 7.4 to **5.6** and remove all PHP 7.0+ syntax from the codebase.

Specific syntax changes:

- `private const` → `const` (class constant visibility requires PHP 7.1)
- Typed properties (`private array $prop`) → untyped (`private $prop`) (requires PHP 7.4)
- Return type declarations (`: void`, `: array`, `: bool`) → removed (requires PHP 7.0)
- Scalar type hints (`string`) → removed (requires PHP 7.0)
- Nullable types (`?Type`) → removed (requires PHP 7.1)
- `array` type hints in method signatures are kept (supported since PHP 5.1) — **Update**: also removed for consistency, since WordPress hook callbacks receive mixed types

Keep WordPress minimum at **3.7**.

**Non-goals:**

- Dropping below PHP 5.6 — WordPress 3.7 itself requires PHP 5.2.4, but 5.6 is a reasonable floor
- Reverting any bug fixes, error handling improvements, or behavioral changes introduced alongside the PHP 7.4 bump

## Consequences

- Good, because the plugin works on the older WordPress + PHP environments where image rotation is most needed
- Good, because it aligns the minimum PHP with the plugin's stated WordPress minimum (3.7 runs on PHP 5.6)
- Bad, because the code loses self-documenting typed properties and return types
- Bad, because static analysis tools (PHPStan) may be less effective without type annotations
- Neutral, PHPDoc `@var` and `@return` annotations still document types for IDEs and static analysis

## Implementation Plan

- **Affected paths**: `includes/class-fix-image-rotation.php`, `init.php`, `readme.txt`, `README.md` (auto-generated), `CLAUDE.md`, `AGENTS.md`
- **Dependencies**: None
- **Patterns to follow**: Use PHPDoc annotations (`@var`, `@param`, `@return`) to document types that can no longer be expressed in PHP syntax. Keep `array` type hints only where they existed in the pre-7.4 codebase.
- **Patterns to avoid**: Do not use any PHP 7.0+ syntax features: scalar type hints, return type declarations, nullable types, class constant visibility, typed properties, null coalescing operator (`??`), spaceship operator (`<=>`), anonymous classes, `void` return type.

### Verification

- [x] `init.php` header shows `Requires PHP: 5.6`
- [x] `readme.txt` shows `Requires PHP: 5.6`
- [x] `CLAUDE.md` and `AGENTS.md` show minimum PHP 5.6
- [x] No `private const`, `protected const`, or `public const` with visibility in class files (bare `const` is fine)
- [x] No typed properties (`private array`, `protected static ?Type`) in class files
- [x] No return type declarations (`: void`, `: array`, etc.) in class files
- [x] No scalar type hints (`string`, `int`, `bool`) in method signatures

## Alternatives Considered

- **Keep PHP 7.4 minimum**: Would retain modern syntax benefits but exclude the primary audience — users on older WordPress without built-in image rotation. Rejected because the plugin's value proposition depends on broad compatibility.
- **PHP 7.0 minimum**: Would allow return type declarations and scalar type hints but not typed properties. Rejected because PHP 7.0 reached EOL in December 2018 and doesn't meaningfully expand the target audience compared to 5.6, while still excluding some older hosts.

## More Information

- Supersedes [Bump minimum PHP 7.4 and WordPress 6.2](2026-03-23-bump-minimum-php-74-and-wordpress-62.md)
- The WordPress `Requires PHP` plugin header is only enforced by WordPress 5.2+. On older WordPress, PHP compatibility must be ensured by using compatible syntax directly.
