<?php

declare(strict_types=1);

namespace Drupal\google_tag\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command for sending events to GA for ajax responses.
 */
final class GoogleTagEventCommand implements CommandInterface {

  /**
   * The Event Name.
   *
   * @var string
   */
  private string $eventName;

  /**
   * Event Data.
   *
   * @var array
   */
  private array $data;

  /**
   * GoogleTagEventCommand constructor.
   *
   * @param string $event_name
   *   Event name.
   * @param array $data
   *   Event data.
   */
  public function __construct(string $event_name, array $data) {
    $this->eventName = $event_name;
    $this->data = $data;
  }

  /**
   * {@inheritDoc}
   */
  public function render() {
    return [
      'command' => 'gtagEvent',
      'event_name' => $this->eventName,
      'data' => $this->data,
    ];
  }

}
