<?php

namespace Drupal\ib_dam\Exceptions;

/**
 * Class AssetValidationBadPluginId.
 *
 * @package Drupal\ib_dam\Exceptions
 */
class AssetValidationBadPluginId extends IbDamException {

  /**
   * AssetValidationBadPluginId constructor.
   *
   * @param string $original_message
   *   The message from wrapped exception.
   */
  public function __construct($original_message) {
    $log_message = "Can't get instance of asset validation plugin id: @message";
    $log_message_args = ['@message' => $original_message];
    $message = $admin_message = $this->t("Can't get instance of asset validation plugin id: @message", $log_message_args);;

    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
