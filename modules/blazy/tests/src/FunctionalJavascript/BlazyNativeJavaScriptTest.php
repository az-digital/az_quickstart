<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

/**
 * Tests the Blazy without lazyloader script using PhantomJS, or Chromedriver.
 *
 * @group blazy
 */
class BlazyNativeJavaScriptTest extends BlazyJavaScriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->scriptLoader = 'native';

    // Enable `No JavaScript` lazy option to enact Native markup.
    $this->container->get('config.factory')->getEditable('blazy.settings')->set('nojs.lazy', 'lazy')->save();
    $this->container->get('config.factory')->clearStaticCache();
  }

  /**
   * Test the Blazy element from loading to loaded states.
   */
  public function testFormatterDisplay() {
    $settings['ratio'] = '';
    $settings['image_style'] = '';

    $data['settings'] = $settings;

    $this->setUpContentTypeTest($this->bundle);
    $this->setUpFormatterDisplay($this->bundle, $data);
    $this->setUpContentWithItems($this->bundle);

    $this->drupalGet('node/' . $this->entity->id());

    // Ensures no data-src is printed. Except for Blur, BG, Video.
    // @phpstan-ignore-next-line
    $result = $this->assertSession()->waitForElement('css', '[data-src]');
    $this->assertEmpty($result);
  }

}
