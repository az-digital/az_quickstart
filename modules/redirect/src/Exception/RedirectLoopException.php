<?php

namespace Drupal\redirect\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Exception for when a redirect loop is detected.
 */
class RedirectLoopException extends \RuntimeException {

  /**
   * The looping path.
   *
   * @var string
   */
  protected $path;

  /**
   * The redirect ID.
   *
   * @var int
   */
  protected $rid;

  /**
   * Formats a redirect loop exception message.
   *
   * @param string $path
   *   The path that results in a redirect loop.
   * @param int $rid
   *   The redirect ID that is involved in a loop.
   */
  public function __construct($path, $rid) {
    $message = new FormattableMarkup('Redirect loop identified at %path for redirect %rid', ['%path' => $path, '%rid' => $rid]);
    $this->path = $path;
    $this->rid = $rid;
    parent::__construct($message);
  }

  /**
   * Returns the looping path.
   *
   * @return string
   *   The path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Returns the redirect ID.
   *
   * @return int
   *   The redirect ID.
   */
  public function getRedirectId() {
    return $this->rid;
  }

}
