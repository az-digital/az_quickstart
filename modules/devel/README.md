[[_TOC_]]

#### Introduction

Devel module contains helper functions and pages for Drupal developers and
inquisitive admins:

 - A block and toolbar for quickly accessing devel pages
 - A menu tab added to entities to give access to internal entity properties
 - Urls created to view the internal entity properties even when there is no menu tab, for example /devel/paragraph/n
 - Debug functions for inspecting a variable such as `dpm($variable)`
 - Debug a SQL query `dpq($query` or print a backtrace `ddebug_backtrace()`
 - A block for masquerading as other users (useful for testing)
 - A mail-system class which redirects outbound email to files
 - Drush commands such as `fn-hook`, `fn-event`, `token`, `uuid`, and `devel-services`
 - *Devel Generate*. Bulk creates nodes, users, comment, taxonomy, media, menus, block content for development. Has
 Drush integration.

This module is safe to use on a production site. Just be sure to only grant
_access development information_ permission to developers.

#### Collaboration
- https://gitlab.com/drupalspoons/devel is our workplace for code, MRs, and CI.
- Create a personal fork in order to make an MR.
- We plan to move bck to drupal.org once it uses Gitlab for issues.
- We auto-push back to git.drupalcode.org in order to keep
[Security Team](https://www.drupal.org/security) coverage and packages.drupal.org integration.
- Chat with us at [#devel](https://drupal.slack.com/archives/C012WAW1MH6) on Drupal Slack.

#### Local Development
DDEV is configured with https://github.com/ddev/ddev-drupal-contrib for for easy
local development, test running, etc.

#### Version Compatibility
| Devel version | Drupal core | PHP  | Drush |
|---------------|-------------|------|-------|
| 5.2+          | 10          | 8.1+ | 12+   |
| 5.0, 5.1      | 9,10        | 8.1+ | 11+   |
| 4.x           | 8.9+,9      | 7.2+ | 9+    |
| 8.x-2.x       | 8.x         | 7.0+ | 8+    |

#### Maintainers

See https://gitlab.com/groups/drupaladmins/devel/-/group_members.
