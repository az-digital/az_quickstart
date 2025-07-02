<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Web Manifest for Progressive Web Apps.
 *
 * @MetatagTag(
 *   id = "web_manifest",
 *   label = @Translation("Web Manifest"),
 *   description = @Translation("A URL to a manifest.json file that describes the application. The <a href='https://developer.mozilla.org/en-US/docs/Web/Manifest'>JSON-based manifest</a> provides developers with a centralized place to put metadata associated with a web application."),
 *   name = "manifest",
 *   group = "mobile",
 *   weight = 92,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class WebManifest extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output(): array {
    // Get the standard LinkRelBase output.
    $element = parent::output();

    // This attribute is required on the tag to avoid errors in Chrome.
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/crossorigin
    // @see https://developer.chrome.com/docs/extensions/mv2/xhr/
    if (!empty($element) && is_array($element)) {
      $element['#attributes']['crossorigin'] = 'use-credentials';
    }

    return $element;
  }

}
