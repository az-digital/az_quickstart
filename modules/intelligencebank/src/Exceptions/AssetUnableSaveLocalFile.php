<?php

namespace Drupal\ib_dam\Exceptions;

/**
 * Class AssetUnableSaveLocalFile.
 *
 * @package Drupal\ib_dam\Exceptions
 */
class AssetUnableSaveLocalFile extends IbDamException {

  /**
   * AssetUnableSaveLocalFile constructor.
   *
   * @param array|string $original_messages
   *   The array of messages from wrapped exception.
   */
  public function __construct($original_messages) {
    $log_message = 'Unable to save local file: @message';
    $log_message_args = ['@message' => $original_messages];
    $message = $this->t(
      'Unable to save local file in drupal. Please see the logs for more information.'
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
