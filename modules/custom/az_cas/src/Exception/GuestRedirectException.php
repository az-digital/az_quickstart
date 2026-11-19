<?php

namespace Drupal\az_cas\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception for guest authentication redirects.
 */
class GuestRedirectException extends HttpException {

  /**
   * The URL to redirect to.
   *
   * @var string
   */
  protected $redirectUrl;

  /**
   * Constructs a GuestRedirectException.
   *
   * @param string $redirect_url
   *   The URL to redirect to.
   */
  public function __construct($redirect_url) {
    parent::__construct(302, 'Guest authentication redirect');
    $this->redirectUrl = $redirect_url;
  }

  /**
   * Gets the redirect URL.
   *
   * @return string
   *   The redirect URL.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl;
  }

}
