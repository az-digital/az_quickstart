<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform variant excluded.
 *
 * @group webform
 */
class WebformVariantExcludedTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui', 'webform_test_variant'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_multiple'];

  /**
   * Test variant element.
   */
  public function testVariantExcluded() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check that the test variant plugin is available to 'test_variant_*'
    // webforms.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/element/add/webform_variant');
    $assert_session->responseContains('<option value="override">Override</option>');
    $assert_session->responseContains('<option value="test">Test</option>');

    // Exclude the test variant plugin.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('variant.excluded_variants', ['test' => 'test'])
      ->save();

    // Check that the test variant plugin is now excluded.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/element/add/webform_variant');
    $assert_session->responseContains('<option value="override">Override</option>');
    $assert_session->responseNotContains('<option value="test">Test</option>');
  }

}
