# BLOCK CLASS

## CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Bulk Operations
 * Related Modules
 * Maintainers


## INTRODUCTION
------------

Block Class allows users to add classes to any block through the block's
configuration interface. By adding a very short snippet of PHP to a theme's file
classes can be added to the parent <div class="block ..."> element of a block.
Hooray for more powerful block theming!


## REQUIREMENTS
------------

The Block Class requires Block:

 * Block (https://drupal.org/project/block)


## INSTALLATION
------------

* Install as you would normally install a contributed Drupal projects. Visit
   https://www.drupal.org/node/1897420 for further information.


## CONFIGURATION
-------------

 * Visit the block configuration page at Administration » Structure » Block
   Layout and click in the Configure button.

 * Enter the classes in the field called "CSS class(es)" and save the block.

 * Open the page where the block is rendered and inspect the block element.
   You'll be able to see your class added at  <div class="block ..."> element of
   a block.

 * By default block classes using a multiple field for classes and you can add
   one class per line.
   By default you can add 10 classes per block using add more and remove item
   in the block settings page, but if you need you can go to the settings page
   at /admin/config/content/block-class/settings and update this value if you
   need in the field "Quantity of classes per block".

 * You can set the weight to organize better the field items in the block
   settings.
   This field can be configured to class, id and attributes as well.

 * The auto-complete settings is enabled by default and you can use this to
   auto-complete the classes inside of multiple class items.

 * By default on Block Class there is a textfield with 255 chars in the
   maxlength but there is a settings where you can modify if necessary. You can
   go to Administration » Configuration » Content authoring » Block Class or you
   can open the url directly at /admin/config/content/block-class/settings and
   there you can select the fieldtype between textfield (default) and multiple.
   You can select the Maxlength of the field. By default is 255 chars.

 * You can enable an option to use attributes, and you're free to customize that
   if you want. To do it you can go to admin/config/content/block-class/settings
   and mark the option "Enable attributes". With that the next time that you go
   to block settings page you'll be able to see a textarea where you can insert
   your attributes. You need to use key | value format, and one per line. For
   example: data-block-type|info

 * Using attributes on block class you can use 10 attributes per block by
   default but you can modify this value in the
   "Quantity of attributes per block" field.

 * You can enable the ID personalization to allow you to update the block id in
   the front-end only. If you remove that the default block id will continue
   appearing.
   You can also define the Maxlength and Weight items that will be removed if
   you disable the id option.
   In the ID you can also remove the default block ID using the key <none>
   Using that the default block ID will be removed and won't show in the
   front-end for the end-users.

 * There is a block class list where you can see all blocks and theirs
   attributes and classes at
   Administration » Configuration » Content authoring » Block Class » List
   This list can have a lot of items depending on your database. For this reason
   there is a pagination and you can define the items per page in the settings
   page admin/config/content/block-class/settings and update the field
   BLOCK CLASS LIST » Items per page. By default are 50 items.

 * "Advanced" On this field group that is closed by default you can set the
   array to be used in the Html::cleanCssIdentifier. This one is used to filter
   special chars in the classes. You can use one per line using key|value format
   example, to replace underline to hyphen:
   _|-

## BULK OPERATIONS
-------------

 * There is a Bulk Operation to update classes automatically to help. To do this
   you can go to:
   Administration » Configuration » Content authoring » Block Class » Bulk
   Operations and there you can select 2 options:

   1) Insert Class: With this option you can insert with a bulk operation
   classes to all blocks that you have.

   2) Insert Attributes: With this option you can insert with a bulk operation
   attributes to all blocks that you have.

   3) Convert all block classes to uppercase: With this operation you can
   convert all block classes that you have to use uppercase.

   4) Convert all block classes to lowercase: With this operation you can
   convert all block classes that you have to use lowercase.

   5) Update class: With that option you can insert a current class that you
   have in the field "Current class" and in the other field "New class" you can
   insert the new class that you want to use. After this you'll be redirected to
   another page to review that and update all classes.

   6) Delete all block classes: With this option the bulk operation will remove
   all block classes on blocks. After this form you'll be redirected to another
   page to confirm that operation.

   7) Delete all attributes: With this option the bulk operation will remove
   all attributes on blocks. After this form you'll be redirected to another
   page to confirm that operation.

   8) Remove all custom IDs: On this option will remove the replacement of block
   id in the front-end. But the ID will continue working with the default block
   id.

## RELATED MODULES
---------------

 * [Layout Builder Component Attributes](https://www.drupal.org/project/layout_builder_component_attributes)
   allows editors to add HTML attributes to Layout Builder components. The
   following attributes are supported:
   - ID
   - Class(es)
   - Style
   - Data-* Attributes

 * [Block Classes](https://www.drupal.org/project/block_classes)
   Block Classes allows users to add classes to block title, content, and
   wrapper of any block through the block's configuration interface. This module
   extends the Block Class module features.
   In some cases, we have to write twig file for blocks, if we want to add
   separate classes for block wrapper, title, and its content. Instead of
   writing twig's, we can handle it using the Block Classes module.

 * [Block Attributes](https://www.drupal.org/project/block_attributes)
   The Block Attributes module allows users to specify additional HTML
   attributes for blocks, through the block's configuration interface, such as
   class, id, style, title and more.

 * [Block Class Styles](https://www.drupal.org/project/block_class_styles)
   Extends the Block Class module to incorporate styles (or themes) rather than
   css classes. Adds style-based tpl suggestions. Allows HTML in your block
   titles.
