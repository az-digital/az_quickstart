<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

/**
 * Tests the Blazy bLazy JavaScript using PhantomJS, or Chromedriver.
 *
 * @group blazy
 */
class BlazyBlazyJavaScriptTest extends BlazyJavaScriptTestBase {

  /**
   * Test the Blazy element from loading to loaded states.
   */
  public function testFormatterDisplay() {
    $settings['blazy'] = TRUE;
    $settings['ratio'] = '1:1';
    $settings['image_style'] = '';

    $data['settings'] = $settings;

    $this->setUpContentTypeTest($this->bundle);
    $this->setUpFormatterDisplay($this->bundle, $data);
    $this->setUpContentWithItems($this->bundle);

    $this->drupalGet('node/' . $this->entity->id());

    // Ensures Blazy is not loaded on page load.
    // @todo with Native lazyload, b-loaded is enforced on page load. And
    // since the testing browser Chrome support it, it is irrelevant.
    // @todo $this->assertSession()->elementNotExists('css', '.b-loaded');
    $this->doTestFormatterDisplay();
  }

}
