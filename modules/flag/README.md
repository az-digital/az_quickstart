# Flag

The Flag module allows you to define a boolean toggle field and attach it to a
node, comment, user, or any entity type. You may define as many of these 'flags'
as your site requires. By default, flags are per-user. This means any user with
the proper permission may chose to flag the entity.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/flag).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/flag).


## Table of contents

- Requirements
- Installation
- Configuration
- Support requests
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Configuration of Flag module involves creating one or more flags.

1. Go to Admin > Structure > Flags, and click "Add flag".
2. Select the target entity type, and click "Continue".
3. Enter the flag link text, link type, and any other options.
4. Click "Save Flag".
5. Under Admin > People, configure the permissions for each Flag.

Once you are finished creating flags, you may choose to use Views to leverage
your new flags.


### Customize the flag link

You can
[customize the mark up](https://www.drupal.org/docs/develop/theming-drupal/twig-in-drupal/working-with-twig-templates)
via the `templates/flag.html.twig` file, or change the look and behaviour of the
flag link with CSS, for example [adding an icon](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/flag/add-icon-to-flag-link).


## Support requests

Before posting a support request, check Recent log entries at
admin/reports/dblog

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/flag

When posting a support request, please inform what does the status report say
at admin/reports/dblog and if you were able to see any errors in
Recent log entries.


## Maintainers

- Joachim Noreiko - [joachim](https://www.drupal.org/u/joachim)
- Nate Lampton - [quicksketch](https://www.drupal.org/u/quicksketch)
- Tess - [socketwench](https://www.drupal.org/u/socketwench)
- Sascha Grossenbacher - [Berdir](https://www.drupal.org/u/berdir)
- Earl Miles - [merlinofchaos](https://www.drupal.org/u/merlinofchaos)
- mooffie - [mooffie](https://www.drupal.org/user/78454)
- Wolfgang Ziegler - [fago](https://www.drupal.org/u/fago)
- Sebastian Siemssen - [fubhy](https://www.drupal.org/u/fubhy)
- Shabana Navas - [shabana.navas](https://www.drupal.org/u/shabananavas)
- Andrei Ivnitskii - [Ivnish](https://www.drupal.org/u/ivnish)
