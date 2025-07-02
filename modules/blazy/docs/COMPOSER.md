
***
## <a name="composer"></a>INSTALLING THIRD-PARTY LIBRARIES VIA COMPOSER

Until Drupal has an official [core management of the 3rd-party front-end libraries](https://www.drupal.org/project/drupal/issues/2873160), there are various ways to install third party bower (deprecated)/ npm asset libraries.

Check out any below suitable to your workflow:

  + [#3021902](https://www.drupal.org/project/blazy/issues/3021902)
  + [#2907371](https://www.drupal.org/project/slick/issues/2907371)
  + [#2907371](https://www.drupal.org/project/slick/issues/2907371#comment-12882235)
  + Via [asset-packagist.org](https://asset-packagist.org/), see below.

It is up to you to decide which works best. Composer is not designed to
manage JS, CSS or HTML framework assets. It is for PHP. Then come Composer
plugins, and other workarounds to make Composer workflow easier. As many
alternatives, it is not covered here. Please find more info on the
above-mentioned issues.

### VIA ASSET-PACKAGIST.ORG
If using [asset-packagist.org](https://asset-packagist.org/), regardless cons,
be sure to set up your composer.json correctly, some distros use it, see
[Slick](https://drupal.org/project/slick) project for the supporting distros to
copy/ paste from their composer.json files. Be warned! Invalid json may break.
Normally unwanted trailing commas.

1. Add/ merge these lines, add commas as required:
````
    "repositories": [
        {
            "type": "composer",
            "url": "https://asset-packagist.org"
        }
    ]
````

2. Add/ merge these lines, add commas as required:
````
    "extra": {
        "installer-types": [
            "bower-asset",
            "npm-asset"
        ],
        "installer-paths": {
            "web/libraries/{$name}": [
                "type:drupal-library",
                "type:bower-asset",
                "type:npm-asset"
            ],
            // ....
        }
    }
````

3. Require [composer-installers-extender](https://github.com/oomphinc/composer-installers-extender):
  `composer require oomphinc/composer-installers-extender`

4. Then require any libraries as usual only prefixed with `npm-asset`, or
   `bower-asset` (deprecated). The versions must be re-checked, just samples:
   + If using [Slick](https://www.drupal.org/project/slick), the namespace is
     `slick-carousel`, not `slick`. The supported versions are `1.6.0 - 1.8.0`,
     not `1.8.1` up, use exact numbers:
     * `composer require npm-asset/slick-carousel:1.8.0`
   + If using [Splide](https://www.drupal.org/project/splide):
     * `composer require npm-asset/splidejs--splide:^4`
   + If using Colorbox, verify the latest supported version:
     * `composer require npm-asset/jquery-colorbox:^1.6.4`
   + etc.

   Or run them once:
   + `composer require npm-asset/slick-carousel:1.8.0 npm-asset/splidejs--splide:^4.0 npm-asset/jquery-colorbox:^1.6.4`

5. To update:
   `composer update --with-dependencies`

### Warning!
To avoid potential security issues, please only install the `dist` directory, if
any, or only the required files, and not any other files from the archive. Check
out the relevant module project requirements for the exact needed files.
