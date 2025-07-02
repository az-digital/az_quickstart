<?php

namespace Drupal\flag\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Flash a message as an action link is updated.
 *
 * The client side code can be found in js/flag-action_link_flash.js.
 *
 * @ingroup flag
 */
class ActionLinkFlashCommand implements CommandInterface {

  /**
   * Identifies the action link to be flashed.
   *
   * @var string
   */
  protected $selector;

  /**
   * The message to be flashed under the link.
   *
   * @var string
   */
  protected $message;

  /**
   * Construct a message Flasher.
   *
   * @param string $selector
   *   Identifies the action link to be flashed.
   * @param string $message
   *   The message to be displayed.
   */
  public function __construct($selector, $message) {
    $this->selector = $selector;
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'actionLinkFlash',
      'selector' => $this->selector,
      'message' => $this->message,
    ];
  }

}
