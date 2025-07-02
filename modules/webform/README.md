## CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Recommended modules
- Configuration
- Upgrading
- Maintainers


## INTRODUCTION

The Webform module is a form builder and submission manager for Drupal 8/9.

The primary use case for this module is to:

- **Build** a new webform or duplicate an existing template
- **Publish** the webform as a page, node, or block
- **Collect** submissions
- **Send** confirmations and notifications
- **Review** submissions online
- **Download** submissions as a CSV

Goals:

- A comprehensive form and survey building solution for Drupal 8/9.
- A stable, maintainable, and tested API for building forms and handling
  submission.
- A pluggable/extensible API for custom form elements and submission handling.

Demo:

- Video review of the Webform module:
  http://youtu.be/sQGsfQ_LZJ4
- Online evaluate by simplytest.me:
  https://simplytest.me/project/webform/6.x

## REQUIREMENTS

This module requires no modules outside of Drupal core.


## INSTALLATION

The installation of this module is like other Drupal modules.

1. If your site is [managed via Composer](https://www.drupal.org/node/2718229),
   use Composer to download the webform module running
   ```composer require "drupal/webform"```. Otherwise copy/upload the webform
   module to the modules directory of your Drupal installation.

2. Enable the 'Webform' module and desired sub-modules in 'Extend'.
   (`/admin/modules`)

3. Set up user permissions. (`/admin/people/permissions#module-webform`)


## RECOMMENDED MODULES

- Third party libraries (`/admin/help/webform`)
- Add-on contrib modules (`/admin/structure/webform/addons`)


## CONFIGURATION

- Build a new webform (`/admin/structure/webform`)
  or duplicate an existing template (`/admin/structure/webform/templates`).

- Publish your webform as a:
  - **Page** by linking to the published webform. (`/webform/contact`)
  - **Node** by creating a new node that references the
    webform. (`/node/add/webform`)
  - **Block** by placing a Webform block on your
    site. (`/admin/structure/block`)


## UPGRADING

- All existing configuration and submission data was maintained and updated
  through the beta and rc release cycles.
  **APIs have changed** during these release cycles.

- Simply put, if you installed and used the Webform module out of the box AS-IS,
  and now you want to upgrade to a full release, then
  you _should_ be okay. If you extended webforms with plugins, altered
  hooks, and overrode templates, you will need to read each release's
  notes and assume that _things have changed_.


## MAINTAINERS

Current maintainers for Drupal 8/9:

- Jacob Rockowitz (jrockowitz) - https://www.drupal.org/u/jrockowitz

This project has the following Growth Backers:

- Acquia - https://www.acquia.com/
- New Relic - https://newrelic.com/
- nextide - http://www.nextide.ca/
- Intranet Solutions - https://www.intranets.com.au/
- TechReviewer - https://techreviewer.co/
