# User Expire

The User Expire module allows administrators to define a date on which to
expire a specific user account or to define a period at a role level where
inactive accounts will be locked.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/user_expire)

To submit bug reports and feature suggestions, or track changes
[issue queue](https://www.drupal.org/project/issues/user_expire)


## Table of contents

- Requirements
- Installation
- Configuration
- FAQ
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. Visit
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
for further information.


## Configuration

- Configure the user expire settings in

  `Administration » Configuration » People » User expire`:

    - Set the frequency time in seconds

      The frequency time is the time set for how often to run warns users with
      an upcoming expiration by roles

    - Set the warning offset time in seconds

      The warning offset is how long before the account expires is the first
      warning sent.

    - Set seconds of inactivity before expiring for each role within your system

      Each role can be set to a different expiration time with 0 set for roles
      that will not be expired.


## FAQ

If you need to unblock a user from the command line, you can use Drush or a SQL
query. Visit [Securing user #1](https://www.drupal.org/node/947312) for further
information.


## Maintainers

- Erik Webb - [erikwebb](https://www.drupal.org/u/erikwebb)
- Greg Knaddison - [greggles](https://www.drupal.org/u/greggles)
- Shelane French - [shelane](https://www.drupal.org/u/shelane)
- Joe Parsons    - [joegraduate](https://www.drupal.org/u/joegraduate)
- Troy Dean      - [tadean](https://www.drupal.org/u/tadean)
- Chris Green    - [trackleft2](https://www.drupal.org/u/trackleft2)
