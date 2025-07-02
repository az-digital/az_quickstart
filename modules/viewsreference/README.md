Views reference field
=====================

INTRODUCTION
------------
The views reference field works the same way as any Entity Reference field
except that the entity it targets is a View. You can target a view using the
entity reference field but you cannot select a particular View display. The
views reference field enables you to select a display ID.


REQUIREMENTS
------------
You will need the `views` module to be enabled.

INSTALLATION
------------
Install the module as usual
Or use:
/*****  Composer *****/
Although views reference does not need composer, if you install using composer
then use the following:

From the drupal root directory of your install:
```
composer require drupal/viewsreference
```

CONFIGURATION
-------------
In any entity in the manage fields tab:
when adding new fields a 'Views reference' field will now be available

After adding a 'Views reference' field it is possible to change the following
additional settings:
- View display plugins to allow - for each field instance the available display
  plugins can be limited.
- Hide available settings - modules are ables to add settings to the view
  reference fields. For each field instance the available settings can be
  hidden.

MAINTAINERS
-----------
Current maintainers:

 * Kent Shelley (New Zeal) - https://www.drupal.org/u/new-zeal
 * Joe Kersey (joekers) - https://www.drupal.org/u/joekers
 * Sean Blommaert (seanB) - https://www.drupal.org/u/seanb
