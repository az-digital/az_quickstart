<?php

namespace Drupal\ib_dam\Exceptions;

/**
 * Class AssetDownloaderBadDestination.
 *
 * @package Drupal\ib_dam\Exceptions
 */
class AssetDownloaderBadDestination extends IbDamException {

  /**
   * AssetDownloaderBadDestination constructor.
   *
   * @param null|string $destination
   *   The asset destination.
   * @param null|string $filename
   *   The asset local filename.
   */
  public function __construct($destination = NULL, $filename = NULL) {
    $log_message = 'The %filename could not be uploaded to the destination %destination.';
    $log_message_args = [
      '%destination' => $destination,
      '%filename' => $filename,
    ];
    $message = $admin_message = $this->t('The %filename could not be uploaded to the destination %destination.', $log_message_args);;

    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
