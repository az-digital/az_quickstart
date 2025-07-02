<?php

namespace Drupal\ib_dam\Exceptions;

/**
 * Class AssetDownloaderBadRequest.
 *
 * @package Drupal\ib_dam\Exceptions
 */
class AssetDownloaderBadRequest extends IbDamException {

  /**
   * AssetDownloaderBadRequest constructor.
   *
   * @param string $original_message
   *   The message from wrapped exception.
   * @param array $original_message_params
   *   The message params from wrapped exception.
   */
  public function __construct($original_message, array $original_message_params = []) {
    $log_message = 'Bad IntelligenceBank API request detected: @message';
    $log_message_args = [
      '@message' => $original_message,
      '@params' => $original_message_params,
    ];

    $message = $this->t(
      'Unable to make request to the IntelligenceBank API. Please see the logs for more information.'
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
