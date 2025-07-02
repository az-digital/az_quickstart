<?php

namespace Drupal\crop\Events;

/**
 * Contains all events thrown by crop API.
 */
final class Events {

  /**
   * The event to subscribe to add provider as manual crop fallback provider.
   *
   * @var string
   */
  const AUTOMATIC_CROP_PROVIDERS = 'crop.automatic_crop_providers';

  /**
   * The event to subscribe to automatic crop generate for crop API.
   *
   * @var string
   */
  const AUTOMATIC_CROP = 'crop.automatic_crop';

}
