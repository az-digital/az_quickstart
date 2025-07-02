<?php

namespace Drupal\Tests\slick\Traits;

/**
 * A Trait common for Slick tests.
 */
trait SlickTestTrait {

  /**
   * Verifies the logged in user has access to the various pages.
   *
   * @param array $pages
   *   The array of pages we want to test.
   * @param int $response
   *   (optional) An HTTP response code. Defaults to 200.
   */
  protected function verifyPages(array $pages = [], $response = 200) {
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->statusCodeEquals($response);
    }
  }

  /**
   * Wraps the submit form.
   *
   * @param string $page
   *   The page we want to test.
   * @param array $content
   *   The content to submit.
   * @param string $submit
   *   The submit text.
   * @param int $response
   *   (optional) An HTTP response code. Defaults to 200.
   * @param string $message
   *   The message text.
   */
  protected function verifySubmitForm($page = '', array $content = [], $submit = 'Save', $response = 200, $message = '') {
    $this->drupalGet($page);
    $this->submitForm($content, $submit);
    $this->assertResponse($response, $message);
  }

}
