# Quickstart Global Footer

Enabling the Quickstart Global Footer module on your site will add default University of Arizona resource and social media links to your footer.

## Permissions

You must have administrator permissions in order to enable this module.

## Pre-existing Menu Links

If your site already has menu links in the provided footer menus, those links will persist after enabling this module.

## Installing

If your site was created prior to Quickstart 2.1.0, you may encounter the
following error messages when trying to install this module:

```
Legacy global footer blocks not deleted because config differences detected. Blocks will need to be manually deleted with drush in order to install Quickstart Global Footer module.
```
```
Unable to install Quickstart Global Footer, <em>block.block.az_barrio_footer_menu_info</em>, <em>block.block.az_barrio_footer_menu_main</em>, <em>block.block.az_barrio_footer_menu_resources</em>, <em>block.block.az_barrio_footer_menu_social_media</em>, <em>block.block.az_barrio_footer_menu_topics</em> already exist in the active configuration.
```

If you encounter these errors, you should be able to manually delete the menu
blocks via the Drupal block layout admin UI interface or by using the following
drush commands:
```
drush config:delete block.block.az_barrio_footer_menu_info
drush config:delete block.block.az_barrio_footer_menu_main
drush config:delete block.block.az_barrio_footer_menu_resources
drush config:delete block.block.az_barrio_footer_menu_social_media
drush config:delete block.block.az_barrio_footer_menu_topics
```

After manually deleting the footer menu blocks, it should be possible to install
the module.
## Uninstalling

If you uninstall this module the following menu block configuration will also be uninstalled and deleted:
  - az-footer-information-for
  - az-footer-main
  - az-footer-resources
  - az-footer-social-media
  - az-footer-topics

The menu link content provided by this module will also be deleted including any modifications to specific menu items.
To ensure your menu links are not uninstalled by this module, create new menu links instead of changing the [links
provided by this module](data/az_global_footer.json).
