<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for element allowed tags.
 *
 * @group webform
 */
class WebformElementAllowsTagsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_allowed_tags'];

  /**
   * Test element allowed tags.
   */
  public function testAllowsTags() {
    $assert_session = $this->assertSession();

    // Check <b> tags is allowed.
    $this->drupalGet('/webform/test_element_allowed_tags');
    $assert_session->responseContains('Hello <b>…Goodbye</b>');

    // Check custom <ignored> <tag> is allowed and <b> tag removed.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.allowed_tags', 'ignored tag')
      ->save();
    $this->drupalGet('/webform/test_element_allowed_tags');
    $assert_session->responseContains('Hello <ignored></tag>…Goodbye');

    // Restore admin tags.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.allowed_tags', 'admin')
      ->save();
  }

}
