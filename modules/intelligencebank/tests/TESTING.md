# Notes about testing in Gitpod using the DrupalPod browser plugin.

## Using DrupalPod
[How to install DrupalPod](https://github.com/shaal/DrupalPod).
You may need to manually run a site installation here is the command:

```
 ddev drush si --account-mail=noreply@example.com --account-name=intelligencebank --account-pass=intelligencebank --db-url=mysql://db:db@db:3306/db -y --verbose
 ```

 Then install the required modules:

 ```
 ddev drush -y pm:install ib_dam_media ib_dam ib_dam_wysiwyg
 ```

## Testing
You can test using a PHPUnit group
`ddev phpunit --group intelligencebank`

The test module relies heavily on the standard drupal install profile for the default media types.
https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/profiles/standard/config/optional/media.type.audio.yml
https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/profiles/standard/config/optional/media.type.document.yml
https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/profiles/standard/config/optional/media.type.image.yml
https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/profiles/standard/config/optional/media.type.video.yml


To enable the test module intelligencebank_test manually, you can add
`$settings['extension_discovery_scan_tests'] = TRUE;` to your settings.php file.
Then, enable the module as you normally would.
`ddev drush en -y intelligencebank_test`
