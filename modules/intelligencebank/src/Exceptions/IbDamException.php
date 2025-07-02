<?php

namespace Drupal\ib_dam\Exceptions;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class IbDamException.
 *
 * Base class for IntelligenceBank Exceptions.
 *
 * @package Drupal\ib_dam\Exceptions
 */
abstract class IbDamException extends \Exception {

  use StringTranslationTrait;
  use MessengerTrait;


  /**
   * Admin permission related to this exception.
   *
   * @var string
   */
  protected $adminPermission = 'administer intelligencebank configuration';

  /**
   * Message level to be used when displaying the message to the user.
   *
   * @var string
   */
  protected $messageLevel = 'error';

  /**
   * User-facing for admin users.
   *
   * @var string
   */
  protected $adminMessage;

  /**
   * Message to be logged in the Drupal's log.
   *
   * @var string
   */
  protected $logMessage;

  /**
   * Arguments for the log message.
   *
   * @var array
   */
  protected $logMessageArgs;

  /**
   * Constructs BundleNotExistException.
   */
  public function __construct(
    $message,
    $admin_message = NULL,
    $log_message = NULL,
    $log_message_args = []
  ) {
    $this->adminMessage = $admin_message ?: $message;
    $this->logMessage = $log_message ?: $this->adminMessage;
    $this->logMessageArgs = $log_message_args;
    parent::__construct($message);
  }

  /**
   * Displays message to the user.
   */
  public function displayMessage() {
    if (\Drupal::currentUser()->hasPermission($this->adminPermission)) {
      $this->messenger()->addMessage($this->adminMessage, $this->messageLevel);
    }
    else {
      $this->messenger()->addMessage($this->getMessage(), $this->messageLevel);
    }
  }

  /**
   * Logs exception into Drupal's log.
   *
   * @return \Drupal\ib_dam\Exceptions\IbDamException
   *   This exception.
   */
  public function logException() {
    \Drupal::logger('ib_dam')->error($this->logMessage, $this->logMessageArgs);
    return $this;
  }

}
