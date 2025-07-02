<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\blazy\Traits\BlazyCreationTestTrait;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\blazy\BlazyDefault;

/**
 * Tests the Blazy JavaScript using PhantomJS, or Chromedriver.
 *
 * @group blazy
 */
abstract class BlazyJavaScriptTestBase extends WebDriverTestBase {

  use BlazyUnitTestTrait;
  use BlazyCreationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'image',
    'node',
    'text',
    'blazy',
    'blazy_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpVariables();

    $this->root                   = $this->container->getParameter('app.root');
    $this->fileSystem             = $this->container->get('file_system');
    $this->entityFieldManager     = $this->container->get('entity_field.manager');
    $this->formatterPluginManager = $this->container->get('plugin.manager.field.formatter');
    $this->blazyAdmin             = $this->container->get('blazy.admin');
    $this->blazyManager           = $this->container->get('blazy.manager');
    $this->scriptLoader           = 'blazy';
    $this->maxParagraphs          = 180;

    // Disable `No JavaScript` options by default till required.
    $config = $this->container->get('config.factory');
    $settings = $config->getEditable('blazy.settings');
    foreach (BlazyDefault::nojs() as $key) {
      $settings->set('nojs.' . $key, '0');
    }
    $settings->save(TRUE);
    $config->clearStaticCache();
  }

  /**
   * Test the Blazy element from loading to loaded states.
   */
  public function doTestFormatterDisplay() {
    $image_path = $this->getImagePath(TRUE);

    // Capture the initial page load moment.
    $this->createScreenshot($image_path . '/' . $this->scriptLoader . '_1_initial.png');
    $this->assertSession()->elementExists('css', '.b-lazy');

    // Trigger Blazy to load images by scrolling down window.
    $this->getSession()->executeScript('window.scrollTo(0, document.body.scrollHeight);');

    // Capture the loading moment after scrolling down the window.
    $this->createScreenshot($image_path . '/' . $this->scriptLoader . '_2_loading.png');

    // Wait a moment.
    $this->getSession()->wait(3000);

    // Verifies that one of the images is there once loaded.
    // @phpstan-ignore-next-line
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', '.b-loaded'));

    // Capture the loaded moment.
    // The screenshots are at sites/default/files/simpletest/blazy.
    $this->createScreenshot($image_path . '/' . $this->scriptLoader . '_3_loaded.png');
  }

}
