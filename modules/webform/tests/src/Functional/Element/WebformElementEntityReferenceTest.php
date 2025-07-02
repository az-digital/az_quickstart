<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for entity reference elements.
 *
 * @group webform
 */
class WebformElementEntityReferenceTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_entity_reference'];

  /**
   * Test entity reference elements.
   */
  public function testEntityReferenceTest() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_entity_reference');

    // Check process entity references.
    $this->postSubmission($webform);
    $assert_session->responseContains("webform_entity_select_user_default: '1'
webform_entity_select_user_multiple:
  - '1'
webform_entity_radios_user_default: '1'
webform_entity_checkboxes_user_default:
  - '1'");

  }

}
