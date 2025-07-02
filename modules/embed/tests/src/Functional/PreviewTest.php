<?php

declare(strict_types=1);

namespace Drupal\Tests\embed\Functional;

/**
 * Tests the preview controller and route.
 *
 * @group embed
 */
class PreviewTest extends EmbedTestBase {

  const SUCCESS = 'Success!';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the route used for generating preview of embedding entities.
   */
  public function testPreviewRoute() {
    // Ensure the default filter can be previewed by the anonymous user.
    $this->getRoute('plain_text');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(static::SUCCESS);

    // The anonymous user should not have permission to use embed_test format.
    $this->getRoute('embed_test');
    $this->assertSession()->statusCodeEquals(403);

    // Now login a user that can use the embed_test format.
    $this->drupalLogin($this->webUser);

    $this->getRoute('plain_text');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(static::SUCCESS);

    $this->getRoute('embed_test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(static::SUCCESS);

    // Test preview route with an empty request.
    $this->getRoute('embed_test', '');
    $this->assertSession()->statusCodeEquals(404);

    // Test preview route with an invalid text format.
    $this->getRoute('invalid_format');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Performs a request to the embed.preview route.
   *
   * @param string $filter_format_id
   *   ID of the filter format.
   * @param string $value
   *   The query string value to include.
   *
   * @return string
   *   The retrieved HTML string.
   */
  public function getRoute($filter_format_id, $value = NULL) {
    $url = 'embed/preview/' . $filter_format_id;
    if (!isset($value)) {
      $value = static::SUCCESS;
    }
    if ($this->drupalUserIsLoggedIn($this->webUser)) {
      $this->drupalGet('embed-test/get_csrf_token');
      $token = json_decode($this->getSession()->getPage()->getContent());
    }
    else {
      $token = 'Any value will do for Anonymous';
    }
    $headers = ['X-Drupal-EmbedPreview-CSRF-Token' => $token];
    return $this->drupalGet($url, ['query' => ['text' => $value]], $headers);
  }

}
