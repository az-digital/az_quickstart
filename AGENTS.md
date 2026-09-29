# AGENTS.md

This file guides AI coding agents (including Copilot code review) working on AZ Quickstart, the University of Arizona Drupal distribution. Project conventions live in `CONTRIBUTING.md`, this file distills the checkable ones.

## Code Review

When reviewing a pull request, check the following:

- `hook_update_N()` numbering: each new `MODULE_update_NNNN()` number must be higher than every existing update number for that module on the target branch and higher than the value returned by that module's `hook_update_last_removed()` (e.g. `az_quickstart_update_last_removed()` in `az_quickstart.install`). Flag gaps, duplicates, or a number lower than an existing one. Numbers follow the `XYZZnn` scheme in [RELEASES.md#update-hook-numbering](RELEASES.md#update-hook-numbering), so when backporting the same fix to more than one release branch give each branch its own number in that branch's slot (for example a fix on 2.1.x and 2.2.x gets a `...01nn` number on one branch and a `...02nn` number on the other). When both branches carry the same change, Drupal core's [EquivalentUpdate](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Update%21EquivalentUpdate.php/class/EquivalentUpdate/11.x) API can mark them equivalent so sites upgrading across branches only run it once.
- Database updates versus config changes: whether a config change needs an update hook depends on what state the site's config is in, so ask how the change lands in each case. A fresh Quickstart install. A fresh install that overrides Quickstart config. An existing site a few minor or patch releases behind. An existing site where the owner overrode the config. If the change might not apply cleanly in one of those states, flag it and ask for an update hook.
- Exported configuration is included: config changed in a local dev site must be exported into the module's saved config files (via `drush az-core-config-export-single` or `drush az-core-distribution-config`). Flag PRs that change behavior without touching the corresponding `config/install` YAML.
- Drupal coding standards: PHP must follow [Drupal coding standards](https://www.drupal.org/docs/develop/standards) as enforced by `phpcs.xml.dist`. Flag style violations the linter would catch.
- Commit messages: first line in present-tense imperative form, 72 characters or less. Docs-only changes include `[ci skip]`, and issue-closing commits use `fix`, `close`, or `resolve` keywords (see `CONTRIBUTING.md`).
- Tests: every PR runs PHPUnit and PHPStan. Flag new logic without test coverage or code that would fail static analysis.
