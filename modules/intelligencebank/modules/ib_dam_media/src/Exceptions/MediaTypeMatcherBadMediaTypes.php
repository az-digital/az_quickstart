<?php

namespace Drupal\ib_dam_media\Exceptions;

use Drupal\ib_dam\Exceptions\IbDamException;

/**
 * Class MediaTypeMatcherBadMediaTypes.
 *
 * @package Drupal\ib_dam_media\Exceptions
 */
class MediaTypeMatcherBadMediaTypes extends IbDamException {

  /**
   * MediaTypeMatcherBadMediaTypes constructor.
   *
   * @param string $original_message
   *   The message from wrapped exception.
   */
  public function __construct($original_message) {
    $log_message = "Can't load media types to get media source field types: @message";
    $log_message_args = ['@message' => $original_message];
    $message = $this->t(
      'Unable to load media types. Please see the logs for more information.'
    );
    $admin_message = $message;

    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
