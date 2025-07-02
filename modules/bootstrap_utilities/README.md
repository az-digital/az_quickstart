Drupal Bootstrap Utilities
=========

Bootstrap Utilities is a module that adds [Filter options](https://www.drupal.org/docs/user_guide/en/structure-text-format-config.html) to a Text Format for easy content creation using a Bootstrap theme in Drupal. When you use Bootstrap 4 or Bootstrap 5 in your site, this module helps you to use Bootstrap with less hassle. It comes with **4 text filters**. These filters will add Bootstrap classes automatic to HTML elements, so Bootstrap styles are applied automatic.

_The text filters using xPath to apply the classes. No regular expressions are used to keep high performance._

Text editor filters:
--------------------

### Table filter options

*   Remove `width` and `height` attributes from table cells.
*   Adds `.table-striped` to add zebra-striping to any table row within the `<tbody>`.
*   Adds `.table-hover` to enable a hover state on table rows within a `<tbody>`.
*   Adds `.table-sm` to make tables more compact by cutting cell padding in half.
*   Remove `width` and `height` attributes from table cells, so table behaves more responsive.

### Blockquote filter

This filter will allow you to add default Bootstrap classes to a blockquote

### Figure filter

This filter will allow you to add default Bootstrap classes to a figure HTML element and its caption.

### Image filter

This filter will allow you to add a default Bootstrap class to each image HTML element.

Module installation
-------------------

Install Drupal module as usual. Visit [https://www.drupal.org/docs/extending-drupal/installing-modules](https://www.drupal.org/docs/extending-drupal/installing-modules) for further information.

Add the desired filters via: Home » Administration » Configuration » Content authoring » Text formats and editors.
