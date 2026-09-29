# AGENTS.md

This file guides AI coding agents (including Copilot code review) working on AZ Quickstart, the University of Arizona Drupal distribution. Project conventions live in `CONTRIBUTING.md`, this file distills the checkable ones.

## Code Review

When reviewing a pull request, check the following:

- `hook_update_N()` numbering: each new `MODULE_update_NNNN()` number must be higher than every existing update number for that module and higher than the value returned by that module's `hook_update_last_removed()` (e.g. `az_quickstart_update_last_removed()` in `az_quickstart.install`). Flag gaps, duplicates, or a number lower than an existing one.
- Database updates accompany config changes when required: per `CONTRIBUTING.md`, a new setting, a changed default that sites should get immediately, or a renamed setting key needs an update hook. A new optional value or a non-breaking change does not.
- Exported configuration is included: config changed in a local dev site must be exported into the module's saved config files (via `drush az-core-config-export-single` or `drush az-core-distribution-config`). Flag PRs that change behavior without touching the corresponding `config/install` YAML.
- Drupal coding standards: PHP must follow [Drupal coding standards](https://www.drupal.org/docs/develop/standards) as enforced by `phpcs.xml.dist`. Flag style violations the linter would catch.
- Commit messages: first line in present-tense imperative form, 72 characters or less. Docs-only changes include `[ci skip]`, and issue-closing commits use `fix`, `close`, or `resolve` keywords (see `CONTRIBUTING.md`).
- Tests: every PR runs PHPUnit and PHPStan. Flag new logic without test coverage or code that would fail static analysis.
