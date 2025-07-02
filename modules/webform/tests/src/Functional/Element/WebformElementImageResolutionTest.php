<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform image resolution element.
 *
 * @group webform
 */
class WebformElementImageResolutionTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_image_resolution'];

  /**
   * Tests image resolution element.
   */
  public function testImageResolution() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_image_resolution');

    // Check rendering.
    $assert_session->responseContains('<label>webform_image_resolution_advanced</label>');
    $assert_session->responseContains('<label for="edit-webform-image-resolution-advanced-x" class="visually-hidden">{width_title}</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-image-resolution-advanced-x" type="number" id="edit-webform-image-resolution-advanced-x" name="webform_image_resolution_advanced[x]" value="300" step="1" min="1" class="form-number" />');
    $assert_session->responseContains('<label for="edit-webform-image-resolution-advanced-y" class="visually-hidden">{height_title}</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-image-resolution-advanced-y" type="number" id="edit-webform-image-resolution-advanced-y" name="webform_image_resolution_advanced[y]" value="400" step="1" min="1" class="form-number" />');
    $assert_session->responseContains('{description}');

    // Check validation.
    $this->drupalGet('/webform/test_element_image_resolution');
    $edit = ['webform_image_resolution[x]' => '100'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Both a height and width value must be specified in the webform_image_resolution field.');

    // Check processing.
    $this->drupalGet('/webform/test_element_image_resolution');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_image_resolution: ''
webform_image_resolution_advanced: 300x400");
  }

}
