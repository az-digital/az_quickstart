# Views Remote Data: PokeAPI

Example module using Views Remote Data and the PokeAPI.

This example module provides a new content type called "Pokemon" and a view to render a Pokedex. The view is accessible
at `/pokemon-species` and will display "Pokemon" nodes from data retrieved from the PokeAPI. No data is saved in Drupal
as a node.

# How to install this module

This module is a _test_ module, meaning it is not automatically discovered by Drupal. There are two methods for
installing this module.

## Allow Drupal to discovery test modules

Add the following to your `settings.php` to allow Drupal to scan test modules and themes:

```php
$settings['extension_discovery_scan_tests'] = TRUE;
```

## Copy the module into the `modules`

This module can be copied or moved into your normal module's directory.
