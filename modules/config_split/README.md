# Configuration Split

## Background

The Drupal 8 configuration management works best when importing and exporting
the whole set of the sites configuration. However, sometimes developers like to
opt out of the robustness of CM and have a super-set of configuration active on
their development machine. The canonical example for this is to have the
<code>devel</code> module enabled or having a few block placements or views in
the development environment and then not export them into the set of
configuration to be deployed, yet still being able to share the development
configuration with colleagues.

This module allows to define sets of configuration that will get exported to
separate directories when exporting, and get merged together when importing.
It is possible to define in settings.php which of these sets should be active
and considered for the export and import.

## How to use it

Let us assume that you configured your sync directory as follows:
```php
$settings['config_sync_directory'] = '../config/sync';
```
Create a split with the folder `../config/my-split-folder` and create that
directory. Now add a module that is currently active that you wish not to
export, say `devel`. Next export all the configuration (with `drush cex`).
This should have removed devel from `core.extensions` and moved the devel
configuration to the split folder.
Next you can disable the split in the UI and enable it with a config override.
```php
// In settings.php assuming your split was called 'my_split'
$config['config_split.config_split.my_split']['status'] = TRUE;
```
Now export the configuration again and you will see the split being deactivated
But it is still active on your development site due to the override.

Now deploy the configuration and devel will be un-installed.
On another developers machine just import the configuration, add the override,
clear the cache, and import again to have devel enabled on that environment.

You should only edit active splits as inactive splits will not take effect when
exporting the configuration.

NOTE: Do **NOT** put configuration directories inside of each other.
In particular the split folder **MUST NOT** be inside of the sync directory.
Recommended is a sibling, or in other words a folder that shares the same
parent as the sync directory or in a folder with other split folders which
is next to the sync folder.

Examples:
```
../config/
├── dev
├── sync
└── test

../config/
├── sync
└── splits
    ├── dev
    └── live
```

## How it works

The module depends on Config Filter for the integration with the import/export
pipeline of the Drupal UI and drush. The configuration is read from the main
directory and also from split directories under the hood. Presenting Drupal
with the unified configuration of the sync directory and the extra
configuration defined in splits. Importing and exporting works the same way as
before, except some configuration is read from and written to different
directories. Importing configuration still removes configuration not present in
the files. Thus, the robustness and predictability of the configuration
management remains.


## Attention

You may need to single-import the split configuration if it changes apart from
which extensions are split off.
And remember to clear the caches when overriding the splits configuration.

## Notice

The important part to remember is to use Drupal 8s configuration management the
way it was intended to be used. This module does not interfere with the active
configuration but instead filters on the import/export pipeline. If you use
this module you should have a staging environment where you can let the
configuration management do its job and verify that everything is good for
deployment.
