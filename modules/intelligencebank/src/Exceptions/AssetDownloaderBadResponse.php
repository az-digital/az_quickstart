<?php

namespace Drupal\ib_dam\Exceptions;

/**
 * Class AssetDownloaderBadResponse.
 *
 * @package Drupal\ib_dam\Exceptions
 */
class AssetDownloaderBadResponse extends IbDamException {

  /**
   * AssetDownloaderBadResponse constructor.
   *
   * @param null|string $original_message
   *   The message from wrapped exception.
   */
  public function __construct($original_message = NULL) {
    $log_message = 'Unable to fetch data from IntelligenceBank API, bad response';
    $log_message_args = [];

    if ($original_message) {
      $log_message .= ' @message';
      $log_message_args = ['@message' => $original_message];
    }

    $message = $this->t(
      'Unable to fetch data from IntelligenceBank API. Please see the logs for more information.'
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
