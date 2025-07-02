## SUMMARY

  Chosen uses the Chosen jQuery plugin to make your \<select\> elements
  more user-friendly.


## INSTALLATION

  1. Download the Chosen jQuery plugin
     (https://github.com/JJJ/chosen)
     and extract the file under "libraries".
  2. Download and enable the module.
  3. Configure at Administer > Configuration > User interface > Chosen
     (requires administer site configuration permission)

## INSTALLATION VIA COMPOSER
  It is assumed you are installing Drupal through Composer using the Drupal
  Composer facade. See https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#drupal-packagist

### Installation with repository entry:

  Add the following entry in the "repositories" section of your main composer.json file.

```
{
    "type": "package",
    "package": {
        "name": "jjj/chosen",
        "version": "2.2.1",
        "type": "drupal-library",
        "source": {
            "url": "https://github.com/JJJ/chosen.git",
            "type": "git",
            "reference": "2.2.1"
        }
    }
},
```

  Now you can run the following command to install chosen in the right folder:

```
composer require jjj/chosen:2.2.1
```

### Installation with merge plugin:

  The Chosen Drupal module is shipped with a "composer.libraries.json" file
  which contains information about the chosen library, required by the module itself.

  This file should be merged with the project's main composer.json by the aid
  of the Composer Merge Plugin available on GitHub. The advantage of this approach is
  that the version of the library is defined by the module, and so if the module
  updates the version, it will be automatically pulled by composer.

  Inside the project directory, open a terminal and run:

```
composer require wikimedia/composer-merge-plugin
```

  Then, edit the "composer.json" file of your website and under the "extra"
  section add:

```
"merge-plugin": {
    "include": [
        "web/modules/contrib/chosen/composer.libraries.json"
    ]
}
```

  (*) note: the `web` represents the folder where drupal lives like: ex.
  `docroot`.

  From now on, every time the "composer.json" file is updated, it will also
  read the content of "composer.libraries.json" file located at
  web/modules/contrib/chosen/ and update accordingly.

  Remember, you may have other entries in there already. For this to work, you
  need to have the 'oomphinc/composer-installers-extender' installer. If you
  don't have it, or are not sure, simply run:
```
composer require oomphinc/composer-installers-extender
```

  Then, run the following composer command:

```
composer require drupal/chosen
```

  This command will add the Chosen Drupal module and JavaScript library to your
  project.

## INSTALLATION WITH DRUSH

  A Drush command is provided for easy installation of the Chosen plugin.

  drush chosenplugin

  The command will download the plugin and unpack it in "libraries".
  It is possible to add another path as an option to the command, but not
  recommended unless you know what you are doing.

  If you are using Composer to manage your site's dependencies,
  then the Chosen plugin will automatically be downloaded to `libraries/chosen`.

## TROUBLE SHOOTING

  How to exclude a select field from becoming a chosen select.
  - go to the configuration page and add your field using the jquery "not"
    operator to the textarea with the comma separated values.
    For date fields this could look like:
    select:not([name*='day'],[name*='year'],[name*='month'])
