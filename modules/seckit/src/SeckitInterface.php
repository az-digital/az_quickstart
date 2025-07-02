<?php

namespace Drupal\seckit;

/**
 * Provides an interface Seckit module.
 */
interface SeckitInterface {

  /**
   * Disable X-XSS-Protection HTTP header.
   */
  const X_XSS_DISABLE = 0;

  /**
   * Set X-XSS-Protection HTTP header to 0.
   */
  const X_XSS_0 = 1;

  /**
  * Set X-XSS-Protection HTTP header to 1; mode=block.
  */
  const X_XSS_1_BLOCK = 2;

  /**
  * Set X-XSS-Protection HTTP header to 1.
  */
  const X_XSS_1 = 3;

  /**
  * Disable X-Frame-Options HTTP header.
  */
  const X_FRAME_DISABLE = 0;

  /**
  * Set X-Frame-Options HTTP header to SAMEORIGIN.
  */
  const X_FRAME_SAMEORIGIN = 1;

  /**
  * Set X-Frame-Options HTTP header to DENY.
  */
  const X_FRAME_DENY = 2;

  /**
  * Set X-Frame-Options HTTP header to ALLOW-FROM.
  */
  const X_FRAME_ALLOW_FROM = 3;

  /**
  * Set the URI to POST to for the CSP report-uri directive.
  */
  const CSP_REPORT_URL = '/report-csp-violation';

}
