INTRODUCTION
------------

Designed to keep you sane while working on different copies of your site
(Development, Staging, Production, etc.), Environment Indicator adds some
visual cues (displaying a colored bar, changing the background color of the
toolbar, and an overlay on the site's favicon) to indicate which copy of the
site you are interacting with.

This is incredibly useful if you have multiple environments for each of your
sites, and like me, are prone to forgetting which version of the site you are
currently looking at.

Environment Indicator can also be configured to add links to the other copies of
the site, in case you find yourself on the wrong one and want to quickly jump to
another.

REQUIREMENTS
------------

No special requirements.

RECOMMENDED MODULES
-------------------

 * The Toolbar module (which ships with core):
   When enabled, Environment Indicator adds an item to it and changes its
   background color.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------

In order for a user to see the environment indicator, they will need the "See
environment indicator" permission.

Global options, such as  whether to change the background of the Toolbar, and
whether to show an overlay on top of the favicon, can be configured from the
settings page at admin/config/development/environment-indicator. In order for a
user to change the global options, they will need the "Administer
environment_indicator settings" permission.

Since each copy of the site should have it's own `settings.local.php` (to store
database credentials without checking them into version control), you must
configure Environment Indicator by adding some configuration overrides to
`settings.local.php`:

 * `$config['environment_indicator.indicator']['bg_color']`:

   The background color for the indicator (or Toolbar). Must be a string
   containing a valid CSS color (including the leading # if using hexadecimal
   notation). See http://www.w3.org/TR/css3-color#colorunits for more
   information.

   If you have the typical "development-staging-production" environments, it can
   be helpful to choose "stoplight colors" - green for development (to indicate
   that most changes are okay), yellow for staging (to indicate caution, as
   clients may be working on staging), and red for production (to indicate that
   you are making changes to a live website).

   This configuration is optional. If you do not set it, the background color
   will be inherited from the CSS styles on the page.

 * `$config['environment_indicator.indicator']['fg_color']`:

   The text color for the environment name. Must be a string containing a valid
   CSS color (including the leading # if using hexadecimal notation). See
   http://www.w3.org/TR/css3-color#colorunits for more information.

   To be readable, the text color must contrast with the background color. See
   http://www.w3.org/TR/WCAG20/#visual-audio-contrast-contrast for more
   information about choosing contrasting colors which meet accessibility
   standards.

   This configuration is optional. If you do not set it, the text color will be
   inherited from the CSS styles on the page.

 * `$config['environment_indicator.indicator']['name']`:

   A string of text to display indicate the environment name. Note that Drupal's
   translation system has not yet been bootstrapped when `settings.local.php` is
   run, so you cannot use the t() function here.

   This configuration is optional. If you do not set it, Environment Indicator
   will try to find the name of the current release. If the current release name
   cannot be determined, then it will appear as an empty string.

   The release name is stored as a state:
   ```php
   \Drupal::state()->set('environment_indicator.current_release', 'v1.2.44');
   ```

An example configuration could look like:

```php
$config['environment_indicator.indicator']['bg_color'] = '#FF5555';
$config['environment_indicator.indicator']['fg_color'] = '#555533';
$config['environment_indicator.indicator']['name'] = 'Staging';
```

To add links to switch to other environments, you must add a configuration for
each environment at admin/config/development/environment-indicator/switcher.
This configuration can copied to other environments using Drupal core's
configuration management system. See
https://www.drupal.org/docs/8/configuration-management/managing-your-sites-configuration
for more information. In order for a user to add environment switchers, they
will need the "Administer environment_indicator settings" permission.

TROUBLESHOOTING
---------------

If your configuration is in `settings.local.php`, but is not showing up, you may
need to enable the file by uncommenting the appropriate lines from the bottom of
`settings.php`.

If your configuration is in `settings.local.php`, but is not showing up for a
particular user, you may need to grant that user the "See environment indicator"
permission.

If you encounter fatal errors ("white screens of death") after making changes to
`settings.local.php`, there may be a syntax error. You can check for syntax
errors by running `php -l settings.local.php`.

If you find any bugs, have any suggestions for new features, or are interested
in the latest developments to this module, please visit
https://www.drupal.org/project/issues/environment_indicator for more
information.

FAQ
---

Q: What happened to the feature which allowed me to save environment
   configurations that would be selected by looking at the request URL?

A: This feature has been removed from the 8.x-3.x branch.

Q: What happened to the feature which allowed me to position the environment
   indicator at the bottom of the screen?

A: This feature has been removed from the 8.x-3.x branch.

Q: What happened to the feature which allowed me to display the environment
   indicator in a fixed position in the browser window, regardless of where I
   scrolled?

A: This feature has been removed from the 8.x-3.x branch.

MAINTAINERS
-----------

Current maintainers:
* e0ipso - https://www.drupal.org/u/e0ipso
* Tom Kirkpatrick (mrfelton) - https://www.drupal.org/u/mrfelton
* isholgueras - https://www.drupal.org/u/isholgueras

This project has been sponsored by:
* Lullabot (Development and maintenance for the 7.x-2.x & 8.x-2.x branches).
* SystemSeed (Development and maintenance for the 6.x-1.x & 7.x-1.x branches).
