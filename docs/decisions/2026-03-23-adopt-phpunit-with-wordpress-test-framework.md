---
status: accepted
date: 2026-03-23
decision-makers: Gagan Deep Singh
---

# Adopt PHPUnit with WordPress test framework

## Context and Problem Statement

The plugin had no automated tests. All validation was manual — upload an image, check if it rotated correctly. This made it risky to refactor code or update PHP/WordPress version requirements, since regressions could only be caught by manually testing all 8 EXIF orientations in both landscape and portrait.

The plugin's core logic (EXIF reading, rotation angle calculation, image manipulation) is well-suited to automated testing, and the project already had 16 test images covering all 8 EXIF orientations in both landscape and portrait.

## Decision

Adopt PHPUnit using the WordPress test framework (`wp-phpunit`) for both unit and integration tests.

**Test structure:**

- **Unit tests** (`tests/test-fix-image-rotation.php`, 17 tests): Test plugin internals without image processing — singleton behavior, hook registration, file extension filtering, EXIF orientation-to-rotation mapping (all 8 values via data provider), metadata restoration logic.
- **Integration tests** (`tests/test-image-orientation.php`, 16 tests): Test actual image rotation using the 16 test images in `tests/test-images/`. Verify that images with EXIF orientation 2-8 are physically rotated/flipped to correct orientation, orientation 1 images are left unmodified, and duplicate processing is prevented.

**Test infrastructure:**

- `phpunit.xml.dist` — PHPUnit configuration with two test suites
- `tests/bootstrap.php` — Loads WordPress test framework
- `bin/install-wp-tests.sh` — Installs WP test suite and creates test database (auto-runs in Lando)

**Non-goals:**

- Browser/E2E testing — overkill for a backend-only plugin with no UI
- Code coverage enforcement — not needed at this project's scale

## Consequences

- Good, because all 8 EXIF orientations are verified in both landscape and portrait (16 integration tests)
- Good, because refactoring is safe — the unit tests catch logic regressions without needing a WordPress environment
- Good, because integration tests verify actual pixel-level correctness using dimension and hash assertions
- Good, because the WordPress test framework provides a realistic environment (real database, real hooks)
- Bad, because integration tests require a MySQL database and WordPress installation (handled by Lando and CI)
- Neutral, because the WP test framework resets the database between tests, so integration tests are isolated

## Implementation Plan

- **Affected paths**: `phpunit.xml.dist`, `tests/bootstrap.php`, `tests/test-fix-image-rotation.php`, `tests/test-image-orientation.php`, `bin/install-wp-tests.sh`
- **Dependencies**: `phpunit/phpunit` (dev), `wp-phpunit` (via `bin/install-wp-tests.sh`)
- **Patterns to follow**: Use WordPress `WP_UnitTestCase` as the base class. Use data providers for parameterized tests (EXIF orientation values). Name test methods `test_<behavior_being_tested>`.
- **Patterns to avoid**: Do not mock WordPress core functions in integration tests — use the real WordPress test framework. Do not skip orientations in the test matrix.

### Verification

- [x] `phpunit.xml.dist` defines both unit and integration test suites
- [x] `lando phpunit` runs all tests (33 tests, 48 assertions, all passing)
- [x] Integration tests cover all 8 EXIF orientations in both landscape and portrait
- [x] Unit tests cover singleton, hooks, extension filtering, rotation mapping, and metadata restoration

## Alternatives Considered

- **Codeception**: Full-stack testing framework. Overkill for this plugin — no HTTP endpoints or admin UI to test.
- **Pest PHP**: Modern testing framework with elegant syntax. Rejected because WordPress test framework integration is less mature than PHPUnit's.
- **Manual testing only**: The prior approach. Rejected because it doesn't scale and misses regressions.

## More Information

- Implemented in commit `0657442` ("test: add PHPUnit test suite with unit and integration tests")
- Strengthened in commit `b6b291c` ("test: strengthen integration tests with dimension and hash assertions")
- Test images in `tests/test-images/` — 16 JPEGs covering orientations 1-8 in landscape and portrait
