<?php

namespace Drupal\az_news_export;

/**
 * Stub class for serialization of empty object values.
 *
 * Drupal's serializers do not support stdClass. This placeholder object
 * Is for serializing empty values as objects because the API gaurantees
 * an object response.
 */
final class AZNewsDataEmpty {

  /**
   * Constructs an AZNewsDataEmpty object.
   */
  public function __construct() {
  }

}
