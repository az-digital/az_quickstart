<?php

namespace Drupal\ib_dam_media\Exceptions;

use Drupal\ib_dam\Exceptions\IbDamException;

/**
 * Class MediaStorageUnableSaveMediaItem.
 *
 * @package Drupal\ib_dam_media\Exceptions
 */
class MediaStorageUnableSaveMediaItem extends IbDamException {

  /**
   * MediaStorageUnableSaveMediaItem constructor.
   *
   * @param string $original_message
   *   The message from wrapped exception.
   */
  public function __construct($original_message) {
    $log_message = 'Unable to save media item: @message';
    $log_message_args = ['@message' => $original_message];
    $message = $this->t(
      'Unable to save media item. Please see the logs for more information.'
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
