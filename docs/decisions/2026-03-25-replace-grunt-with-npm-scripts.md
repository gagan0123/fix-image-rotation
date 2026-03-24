---
status: accepted
date: 2026-03-25
decision-makers: Gagan Deep Singh
---

# Replace Grunt with npm scripts

## Context and Problem Statement

The project used Grunt for two build tasks: converting `readme.txt` to `README.md` and generating the translation `.pot` file. The Grunt setup required 4 direct devDependencies (`grunt`, `grunt-contrib-watch`, `grunt-wp-i18n`, `grunt-wp-readme-to-markdown`) which pulled in 168 transitive packages. `npm audit` reported 9 vulnerabilities (7 high severity) in these dependencies.

Grunt is effectively unmaintained — no major releases since 2016. The vulnerabilities had no upstream fixes available. The project's build needs are simple (two tasks), making Grunt significant overhead for minimal value.

## Decision

Remove Grunt entirely and replace with:

1. **`scripts/readme-to-markdown.js`** — A zero-dependency Node.js script that converts `readme.txt` to `README.md`, prepending the plugin icon and rewriting screenshot URLs (same output as `grunt-wp-readme-to-markdown`).
2. **`npm run makepot`** — Uses WP-CLI's `wp i18n make-pot` command (available in the Lando container) instead of the `grunt-wp-i18n` package.

Both are invoked via `package.json` scripts: `npm run readme` and `npm run makepot`.

**Non-goals:**

- Adopting another build tool (webpack, gulp, etc.) — the project doesn't need one
- Removing Node.js entirely — `package.json` is still used for npm scripts

## Consequences

- Good, because `npm audit` reports 0 vulnerabilities (was 9, including 7 high)
- Good, because dependency count dropped dramatically (168 transitive packages removed)
- Good, because the custom readme script is ~44 lines with zero dependencies, easy to understand and maintain
- Good, because WP-CLI `make-pot` is the canonical WordPress i18n tool, better maintained than `grunt-wp-i18n`
- Bad, because the custom readme script must be maintained if WordPress.org changes their readme format (low risk)
- Neutral, because `npm run makepot` requires a Lando/WP-CLI environment (same constraint as before with Grunt)

## Implementation Plan

- **Affected paths**: `package.json`, `scripts/readme-to-markdown.js` (new), `Gruntfile.js` (deleted), `.lando.yml` (remove grunt tooling)
- **Dependencies**: Remove `grunt`, `grunt-contrib-watch`, `grunt-wp-i18n`, `grunt-wp-readme-to-markdown`. No new dependencies added.
- **Patterns to follow**: All build commands run inside Lando (`lando npm run readme`, `lando npm run makepot`). Never run on the host directly.
- **Patterns to avoid**: Do not add new Node.js build tool dependencies. Keep build tasks as npm scripts backed by simple scripts or CLI tools.

### Verification

- [x] `Gruntfile.js` is deleted
- [x] `npm audit` reports 0 vulnerabilities
- [x] `lando npm run readme` produces identical `README.md` output to the old Grunt task
- [x] `lando npm run makepot` generates the `.pot` file correctly
- [x] No Grunt-related packages remain in `package.json` or `package-lock.json`

## Alternatives Considered

- **Update Grunt dependencies**: No fixes available for the vulnerable transitive dependencies. Grunt itself is unmaintained.
- **Switch to Gulp or another task runner**: Overkill for two simple tasks. Would introduce a new dependency tree with the same long-term maintenance risk.
- **Use Makefile**: Would work but adds a non-Node tool to a project already using npm scripts. Unnecessary complexity.

## More Information

- Implemented in commit `c38ac24` ("build: replace Grunt with npm scripts, resolve all npm audit vulnerabilities")
- The readme script (`scripts/readme-to-markdown.js`) replicates the behavior of `grunt-wp-readme-to-markdown` including icon URL prepend and screenshot URL rewriting
