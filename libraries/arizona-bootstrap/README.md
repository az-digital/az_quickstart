# Arizona Bootstrap Packagist Repo

Composer package that contains the dist version of Arizona Bootstrap assets for
use by PHP / Drupal projects.

## Instructions for non-Drupal Projects
If using this package in a non-Drupal project, you may want to add the following
configuration to your project's composer.json file:

```
{
    "extra": {
        "installer-disable": [
            "drupal"
        ]
    }
}
```
