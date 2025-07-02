<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests for element ignored properties.
 *
 * @group webform
 */
class WebformElementIgnoredPropertiesTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_ignored_properties'];

  /**
   * Test element ignored properties.
   */
  public function testIgnoredProperties() {
    $webform_ignored_properties = Webform::load('test_element_ignored_properties');
    $elements = $webform_ignored_properties->getElementsInitialized();
    $this->assertArrayHasKey('textfield', $elements);
    foreach (WebformElementHelper::$ignoredProperties as $ignored_property) {
      $this->assertArrayNotHasKey($ignored_property, $elements['textfield']);
    }
  }

}
