INTRODUCTION
============

This combines the two modules, 
[CSS Injector](https://www.drupal.org/project/css_injector) and 
[JS Injector](https://www.drupal.org/project/js_injector), into a 
single module for simplicity.
As described from those modules, this functions the same. This module is
definitely not a replacement for full-fledged theming, but it provides 
site administrators with a quick and easy way of tweaking things without 
diving into full-fledged theme hacking.


CSS INJECTOR
------------

Allows administrators to inject CSS into the page output based on 
configurable rules. It's useful for adding simple CSS tweaks without 
modifying a site's official theme.


JS INJECTOR
-----------

Allows administrators to inject JS into the page output based on 
configurable rules. It's useful for adding simple JS tweaks without 
modifying a site's official theme.

These configurations uses Drupal 8 Entity API and therefore all 
configurations are held in the database. This means they are exportable 
using features or custom module installs using yml files.

This is great for multi-site installations where each site may have a 
few minor differences. It is sometimes easiest to allow for single site 
configurations.


REQUIREMENTS
============
This module has no requirements.


INSTALLATION
============

Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
=============
1. Configure user permissions in Administration » People » Permissions:
    * Administer CSS Assets
       Add and configure CSS injector configurations
    * Administer JS Assets
       Add and configure JS injector configurations. Give to trusted roles only.
2. Add JS and CSS Assets in Administration » Configuration » Development » 
    Asset Injector
    Choose the desired asset to be injected and click to add a new.
    Upon saving a new asset or editing an existing asset, caches will 
    automatically be flushed. This may result in a slowly loading page after 
    submitting the form.
