CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Recommended modules
 * Configuration
 * Upgrading

INTRODUCTION
------------

This module modifies image form widgets to allow users to mark images as
decorative, thereby bypassing the otherwise required entry of alt text.


INSTALLATION
------------

The installation of this module is like other Drupal modules.

 1. Copy/upload the module to the modules directory of your Drupal
   installation.

 2. Enable the 'Decorative Image Widget' module in 'Extend'.
   (/admin/modules)

 3. Edit an image field and make sure the alt text is enabled but NOT required.

 4. Edit the form display settings for the image and enable the option
    to force the image to be marked decorative when no alt text is provided.

 5. Add an image and observe you users cannot leave alt text blank without
    checking the "Decorative" checkbox.
