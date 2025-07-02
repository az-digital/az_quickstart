<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

/**
 * Tests the Blazy IO JavaScript using PhantomJS, or Chromedriver.
 *
 * @group blazy
 */
class BlazyIoJavaScriptTest extends BlazyJavaScriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->scriptLoader = 'io';
  }

  /**
   * Test the Blazy element from loading to loaded states.
   */
  public function testFormatterDisplay() {
    $settings['blazy'] = TRUE;
    $settings['ratio'] = 'fluid';
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
    // @phpstan-ignore-next-line
    $result = $this->assertSession()->waitForElement('css', '.b-lazy');
    $this->assertNotEmpty($result);
    $this->doTestFormatterDisplay();
  }

}
