<?php

namespace Drupal\ib_dam\Exceptions;

/**
 * Class AssetUnableCreateStorageHandler.
 *
 * @package Drupal\ib_dam\Exceptions
 */
class AssetUnableCreateStorageHandler extends IbDamException {

  /**
   * AssetUnableCreateStorageHandler constructor.
   *
   * @param string $original_message
   *   The message from wrapped exception.
   */
  public function __construct($original_message) {
    $log_message = 'Unable create storage handler: @message';
    $log_message_args = ['@message' => $original_message];
    $admin_message = $this->t('Unable create storage handler: @message', ['@message' => $original_message]);
    $message = $this->t('Unable to create an asset storage handler. Please see the logs for more information.');
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
