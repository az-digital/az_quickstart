## Media

## Roadmap
after release
- @todo: prefix file name with our prefix to track later this files
- @todo: refactor assets, models to the typed data.
- @todo: add custom entity type to use inside custom field based formatter
- @todo: add extensions/alterations points: like events, hooks, etc
- @q: suggest to implement oEmbed support on api side
- @q: how about provide some file ids to allow later sync with drupal?
  Same as acquia
- @q: how about to provide file id to dissallow create more than one
  local copy
  add fileuuid into textarea and where to store it in media?
- @q: do we need "back", "forward" functionality.
- @todo: add support for "url only" option for embeded file type within WYSIWYG integration
- @todo: WYSIWYG, remove "preview_uri" parameter on other then image type assets.

## Issues
Related issue to the error "not allowed media type"
https://www.drupal.org/project/entity_browser/issues/2822354

## Property testing
https://github.com/steos/php-quickcheck
https://gist.github.com/amitsaha/8a8c5e3540f81dd22c80
https://stackoverflow.com/questions/6028249/property-based-testing-in-php
https://github.com/JetBrains/intellij-community/blob/282e4ff321a0d0014e977c5602f1baee80a148f1/java/java-tests/testSrc/com/intellij/java/propertyBased/UnivocityTest.java
http://www.giorgiosironi.com/2015/06/property-based-testing-primer.html
https://eax.me/tag/testirovanie/


## Integrations
WP http://plugins.svn.wordpress.org/intelligencebank-connector/trunk/inc/app.php
https://wordpress.org/plugins/intelligencebank-connector/

## Resources
https://intelligencebank.atlassian.net/wiki/spaces/APIDOC/overview
https://www.drupal.org/node/2444549
https://www.drupal.org/project/entity_browser_enhanced
https://dri.es/an-update-on-the-media-initiative-for-drupal-8-4-8-5

## CS
`vendor/bin/phpcs  --exclude=Drupal.Formatting.MultipleStatementAlignment  --standard=Drupal ib_dam`
`vendor/bin/phpcs  --standard=Security ib_dam`
`vendor/bin/phpcs  --standard=DrupalPractice ib_dam`
`CMD + K`
