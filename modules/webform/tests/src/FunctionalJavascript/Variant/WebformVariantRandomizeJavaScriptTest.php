<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Variant;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant randomize.
 *
 * @group webform
 */
class WebformVariantRandomizeJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_randomize'];

  /**
   * Gets the persisted variant from the current browsing session.
   *
   * @return string|null
   *   The persisted variant id or NULL if one is not persisted.
   */
  protected function getSavedVariantId() {
    $script = <<<JS
(function() {
  var key = 'Drupal.webform.test_variant_randomize.variant.letter';
  return window.sessionStorage.getItem(key);
})();
JS;
    return $this->getSession()->evaluateScript($script);
  }

  /**
   * Test variant randomize.
   */
  public function testVariantRandomize() {
    $webform = Webform::load('test_variant_randomize');

    $this->drupalGet('/webform/test_variant_randomize');

    // Check that either 'a' or 'b' is persisted.
    $saved_variant = $this->getSavedVariantId();
    $this->assertContains($saved_variant, ['a', 'b']);

    // Disable the variant that is saved in the client session.
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $variant_plugin */
    $variant_plugin = $webform->getVariant($saved_variant);
    $variant_plugin->disable();
    $webform->save();

    // Check that the disabled variant is no longer persisted, but rather
    // that the remaining variant is instead.
    $this->drupalGet('/webform/test_variant_randomize');
    $new_saved_variant = $this->getSavedVariantId();
    $this->assertContains($new_saved_variant, ['a', 'b']);
    $this->assertNotEquals($saved_variant, $new_saved_variant);

    // Disable the other variant.
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $variant_plugin */
    $variant_plugin = $webform->getVariant($new_saved_variant);
    $variant_plugin->disable();
    $webform->save();

    // Check that no variant is now persisted.
    $this->drupalGet('/webform/test_variant_randomize');
    $null_variant = $this->getSavedVariantId();
    $this->assertNull($null_variant);
  }

}
