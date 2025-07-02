<?php

namespace Drupal\cas;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;

/**
 * Provides a non-cacheable redirect response to an outside server.
 *
 * When returning a redirect response to a non-local URL (external server),
 * you must use a response class that inherits from SecuredRedirectResponse.
 *
 * Core provides one, called TrustedRedirectResponse, but it is cacheable,
 * meaning Drupal's cache modules will attempt to cache the response.
 *
 * We need a redirect response that is NOT cacheable, since when we redirect
 * the user for gateway mode, there are far too many complexities involved
 * construct the proper cache contexts and tags.
 *
 * This response simply allows us to redirect a user to another site without
 * worrying about Drupal caching that redirect response.
 */
class CasRedirectResponse extends SecuredRedirectResponse {

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url) {
    return TRUE;
  }

}
