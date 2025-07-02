<?php

declare(strict_types=1);

namespace Drupal\sophron\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the MapEvent.
 */
class MapEvent extends Event {

  /**
   * Fires when a MimeMap map is initialized in Sophron.
   *
   * @Event
   *
   * @var string
   */
  const INIT = 'sophron.map.initialize';

  /**
   * An array of errors collected by the event.
   *
   * @var array
   */
  protected array $errors = [];

  /**
   * Constructs the object.
   *
   * @param string $mapClass
   *   The MimeMap class being processed.
   */
  public function __construct(
    protected string $mapClass,
  ) {
  }

  /**
   * Returns the MimeMap class being processed.
   *
   * @return string
   *   The MimeMap class being processed.
   */
  public function getMapClass(): string {
    return $this->mapClass;
  }

  /**
   * Adds an error.
   *
   * @param string $method
   *   An identifier of the method where the error occurred.
   * @param array $args
   *   An array of arguments passed to the method.
   * @param string $type
   *   An identifier of the type of the error.
   * @param string $message
   *   A messagge detailing the error.
   */
  public function addError(string $method, array $args, string $type, string $message): static {
    $this->errors[] = [
      'method' => $method,
      'args' => $args,
      'type' => $type,
      'message' => $message,
    ];
    return $this;
  }

  /**
   * Returns the errors collected during the processing of the event.
   *
   * @return array
   *   The array of errors collected.
   */
  public function getErrors(): array {
    return $this->errors;
  }

}
