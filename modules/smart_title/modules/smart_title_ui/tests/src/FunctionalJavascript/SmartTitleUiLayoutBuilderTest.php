<?php

namespace Drupal\Tests\smart_title_ui\FunctionalJavascript;

use Drupal\Tests\layout_builder\FunctionalJavascript\LayoutBuilderOptInTest;

/**
 * Tests the module's compatibility with Layout builder.
 *
 * @group smart_title
 * @group smart_title_ui
 */
class SmartTitleUiLayoutBuilderTest extends LayoutBuilderOptInTest {

  /**
   * The modules to be loaded for this test.
   *
   * @var array
   */
  protected static $modules = [
    'smart_title_ui',
  ];

  /**
   * Layout Builder UI works properly with enabled Smart Title component.
   */
  public function testCheckboxLogicWithEnabledSmartTitle() {
    // Add Smart Title for test content types.
    $this->config('smart_title.settings')
      ->set('smart_title', ['node:before', 'node:after'])
      ->save();
    $this->rebuildAll();

    $this->testCheckboxLogic();
  }

  /**
   * Default Layout Builder config is the expected with Smart Title component.
   */
  public function testDefaultValuesWithEnabledSmartTitle() {
    // Add Smart Title for test content types.
    $this->config('smart_title.settings')
      ->set('smart_title', ['node:after'])
      ->save();
    $this->rebuildAll();

    $this->testDefaultValues();
  }

}
