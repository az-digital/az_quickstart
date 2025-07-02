## Summary

Drupal 8 includes jQuery UI in core, however it is no longer actively
maintained and has been marked deprecated. This module provides the
jQuery UI library for any themes and modules that require it.

  - jQuery UI [documentation](https://jqueryui.com/)
  - jQuery UI [API documentation](https://api.jqueryui.com/)

**Caution**: jQuery UI was deprecated from core because it is no longer
actively maintained, and has been marked “End of Life” by the OpenJS
Foundation. It is not recommended to depend on jQuery UI in your own
code, and instead to select a replacement solution as soon as possible.

## Instructions

1.  Install this module.
2.  Change any references in your theme or module from `core/jquery.ui`
    to `jquery_ui/core`

### Related modules

  - [jQuery UI Accordion](https://www.drupal.org/project/jquery_ui_accordion)
  - [jQuery UI Button](https://www.drupal.org/project/jquery_ui_button)
  - [jQuery UI Checkboxradio](https://www.drupal.org/project/jquery_ui_checkboxradio)
  - [jQuery UI Controlgroup](https://www.drupal.org/project/jquery_ui_controlgroup)
  - [jQuery UI Draggable](https://www.drupal.org/project/jquery_ui_draggable)
  - [jQuery UI Droppable](https://www.drupal.org/project/jquery_ui_droppable)
  - [jQuery UI Effects](https://www.drupal.org/project/jquery_ui_effects)
  - [jQuery UI Menu](https://www.drupal.org/project/jquery_ui_menu)
  - [jQuery UI Progressbar](https://www.drupal.org/project/jquery_ui_progressbar)
  - [jQuery UI Selectable](https://www.drupal.org/project/jquery_ui_selectable)
  - [jQuery UI Selectmenu](https://www.drupal.org/project/jquery_ui_selectmenu)
  - [jQuery UI Slider](https://www.drupal.org/project/jquery_ui_slider)
  - [jQuery UI Spinner](https://www.drupal.org/project/jquery_ui_spinner)
  - [jQuery UI Tooltip](https://www.drupal.org/project/jquery_ui_tooltip)


## Update assets.
yarn install
yarn build

## Current maintainers:
* Jeff Robbins (jjeff)
* Angela Byron (webchick)
* Addison Berry (add1sun)
* Daniel F. Kudwien (sun) - http://drupal.org/user/54136
* Lauri Eskola (lauriii)
* Peter Weber (zrpnr)
