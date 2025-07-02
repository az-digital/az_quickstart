<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The iOS App link alternative for Apple mobile metatag.
 *
 * @MetatagTag(
 *   id = "ios_app_link_alternative",
 *   label = @Translation("iOS app link alternative"),
 *   description = @Translation("A custom string for deeplinking to an iOS mobile app. Should be in the format 'itunes_id/scheme/host_path', e.g. 123456/example/hello-screen'. The 'ios-app://' prefix will be included automatically."),
 *   name = "alternate",
 *   group = "apple_mobile",
 *   weight = 91,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IosAppLinkAlternative extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output(): array {
    $element = parent::output();

    // Add the "ios-app://" prefix on the href value.
    if (isset($element['#attributes']['href']) && $element['#attributes']['href'] != '') {
      // Don't add the prefix if it's already present.
      if (strpos($element['#attributes']['href'], 'ios-app://') === FALSE) {
        $element['#attributes']['href'] = 'ios-app://' . (string) $element['#attributes']['href'];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputExistsXpath(): array {
    return ["//link[@rel='alternate' and starts-with(@href, 'ios-app:')]"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    $xpath_strings = [];
    foreach ($values as $value) {
      $xpath_strings[] = "//link[@rel='alternate' and @href='ios-app://{$value}']";
    }
    return $xpath_strings;
  }

}
