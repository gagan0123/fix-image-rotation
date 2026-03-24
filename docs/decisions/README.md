# Architecture Decision Records (ADR)

An Architecture Decision Record (ADR) captures an important architecture decision along with its context and consequences.

## Conventions

- Directory: `docs/decisions`
- Naming:
  - Use date-prefixed files: `YYYY-MM-DD-choose-database.md`
  - If the repo already uses slug-only names, keep that: `choose-database.md`
- Status values: `proposed`, `accepted`, `rejected`, `deprecated`, `superseded`

## Workflow

- Create a new ADR as `proposed`.
- Discuss and iterate.
- When the team commits: mark it `accepted` (or `rejected`).
- If replaced later: create a new ADR and mark the old one `superseded` with a link.

## ADRs

### Core Architecture

- [Use two-stage upload interception](2015-07-03-use-two-stage-upload-interception.md) (accepted, 2015-07-03)
- [Restore GD Library metadata after rotation](2017-08-24-restore-gd-library-metadata-after-rotation.md) (accepted, 2017-08-24)
- [Defer WebP and AVIF support](2026-03-23-defer-webp-avif-support.md) (accepted, 2026-03-23)

### Development Environment & Tooling

- [Use Lando for local development](2022-03-10-use-lando-for-local-development.md) (accepted, 2022-03-10)
- [Replace Grunt with npm scripts](2026-03-25-replace-grunt-with-npm-scripts.md) (accepted, 2026-03-25)

### Quality & CI/CD

- [Bump minimum PHP 7.4 and WordPress 6.2](2026-03-23-bump-minimum-php-74-and-wordpress-62.md) (accepted, 2026-03-23)
- [Adopt PHPUnit with WordPress test framework](2026-03-23-adopt-phpunit-with-wordpress-test-framework.md) (accepted, 2026-03-23)
- [Adopt GitHub Actions for CI/CD](2026-03-23-adopt-github-actions-for-ci.md) (accepted, 2026-03-23)
- [Adopt PHPStan at level 5](2026-03-24-adopt-phpstan-at-level-5.md) (accepted, 2026-03-24)

### Process

- [Adopt architecture decision records](2026-03-24-adopt-architecture-decision-records.md) (accepted, 2026-03-24)