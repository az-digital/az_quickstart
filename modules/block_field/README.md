
Contents of this file
---------------------

 * About this module
 * Use cases
 * Demo
 * Installation
 * Notes
 * Known Issues/Limitations
 * Todo


About this Module
-----------------

Provides a field that allows a content entity to create and configure custom 
block instances.


Use Cases
---------

- Add blocks to a node's sidebar.
- Add blocks to paragraph.
- Create a carousel of content and configurable blocks.


Demo
----

> Evaluate this project online using [simplytest.me](https://simplytest.me/project/block_field).
 
 
Installation
------------

1. Copy/upload the `block_field.module` to the modules directory of your 
   Drupal installation.

2. Enable the 'Block field' module in 'Extend'. (/admin/modules)

3. Add the 'Block (plugin)' field to any content entity


Notes
-----

- The Block field's block instances are stored as configuration, which is good 
  thing, since site builders and editors can easily tweak them with impacting 
  any configuration management.

- All content blocks from the 'Custom Block Library' are available.


Known Issues/Limitations
------------------------

- Context aware plugins are not currently supported. This includes ctools 
  entity view blocks.
  See: \Drupal\block_field\BlockFieldManager::getBlockDefinitions


Todo
----

- Write additional test for block field plugin ids setting.


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
