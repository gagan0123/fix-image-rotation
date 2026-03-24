---
status: accepted
date: 2026-03-23
decision-makers: Gagan Deep Singh
---

# Adopt GitHub Actions for CI/CD

## Context and Problem Statement

The project was hosted on GitHub but used GitLab CI (`.gitlab-ci.yml`) for its CI/CD pipeline. This required mirroring the repository to GitLab, adding latency and complexity. The GitLab CI pipeline only ran PHPCS — no automated tests, and deployment was a manual GitLab CI job.

With the addition of PHPUnit tests and PHPStan, the project needed a CI setup that could run a PHP version matrix and integrate tightly with GitHub pull requests.

## Decision

Adopt GitHub Actions as the primary CI/CD platform with three workflows:

1. **`.github/workflows/phpcs.yml`** — Runs PHPCS on every push/PR to `master` with inline checkstyle annotations on the PR diff.
2. **`.github/workflows/tests.yml`** — Runs PHPUnit on a PHP 7.4–8.3 matrix against WordPress latest, 6.2 (minimum supported), and nightly. Uses `shivammathur/setup-php` for PHP setup.
3. **`.github/workflows/phpstan.yml`** — Runs PHPStan static analysis.
4. **`.github/workflows/deploy.yml`** — Deploys to WordPress.org SVN on tag push using `10up/action-wordpress-plugin-deploy`, with version consistency verification.

Retain `.gitlab-ci.yml` for now (it was also updated with PHPUnit matrix tests) as a transitional measure until GitHub Actions are fully verified.

**Non-goals:**

- Running CI on every branch — only `master` pushes and PRs trigger workflows
- Self-hosted runners — GitHub-hosted runners are sufficient

## Consequences

- Good, because CI runs natively on GitHub with direct PR integration (inline annotations, status checks)
- Good, because the PHP version matrix catches compatibility issues across PHP 7.4–8.3
- Good, because WordPress nightly builds catch issues with upcoming WordPress releases
- Good, because automated deployment to WordPress.org SVN removes manual deployment steps
- Bad, because GitLab CI is retained temporarily, creating two CI configurations to maintain
- Neutral, because `shivammathur/setup-php` is a well-maintained community action but is a third-party dependency

## Implementation Plan

- **Affected paths**: `.github/workflows/phpcs.yml`, `.github/workflows/tests.yml`, `.github/workflows/phpstan.yml`, `.github/workflows/deploy.yml`
- **Dependencies**: `shivammathur/setup-php` (GitHub Action), `10up/action-wordpress-plugin-deploy` (GitHub Action)
- **Patterns to follow**: Each workflow is a single-purpose file. Use matrix strategy for PHP/WordPress version combinations. Pin action versions to specific commits or major versions.
- **Patterns to avoid**: Do not combine all checks into a single workflow — separate workflows allow independent reruns and clearer status checks.

### Verification

- [x] PHPCS workflow runs on push/PR to master and produces inline annotations
- [x] PHPUnit workflow tests PHP 7.4, 8.0, 8.1, 8.2, 8.3 against WP latest, 6.2, and nightly
- [x] PHPStan workflow runs on push/PR
- [x] Deploy workflow triggers on tag push and publishes to WordPress.org SVN
- [x] All workflows use `shivammathur/setup-php` for consistent PHP environment

## Alternatives Considered

- **Keep GitLab CI only**: Would require continued repository mirroring and lacks native GitHub PR integration. Rejected.
- **CircleCI**: Good CI platform but GitHub Actions has tighter GitHub integration and is free for public repositories. Rejected.
- **Self-hosted CI**: Unnecessary complexity for a small open-source plugin. Rejected.

## More Information

- CI workflows added in commit `a5adb67` ("ci: add GitHub Actions workflows for PHPCS and PHPUnit")
- Deploy workflow added in commit `8a571db` ("ci: add GitHub Actions deploy workflow and update GitLab CI")
- GitLab CI retained in `.gitlab-ci.yml` — planned for removal once GitHub Actions are fully verified
