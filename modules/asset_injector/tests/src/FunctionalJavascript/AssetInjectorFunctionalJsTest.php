<?php

declare(strict_types=1);

namespace Drupal\Tests\asset_injector\FunctionalJavascript;

use Drupal\asset_injector\Entity\AssetInjectorCss;
use Drupal\asset_injector\Entity\AssetInjectorJs;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use WebDriver\Exception\UnexpectedAlertOpen;

/**
 * Tests the general Asset Injector functionality.
 *
 * @group asset_injector
 */
final class AssetInjectorFunctionalJsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['asset_injector', 'test_page_test'];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->adminUser = $this->drupalCreateUser([
      'administer css assets injector',
      'administer js assets injector',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether injected JS works correctly.
   */
  public function testInjectedJs() {
    $driver = $this->getSession()->getDriver();
    // Create a new JS injector and set an alert for the front page.
    AssetInjectorJs::create([
      'type' => 'asset_injector_js',
      'id' => 'test_js',
      'label' => 'test_js',
      'status' => TRUE,
      'code' => 'alert("Test");',
      'conditions' => [
        'request_path' => [
          'id' => 'request_path',
          'negate' => FALSE,
          'pages' => '<front>',
        ],
      ],
    ])->save();
    // Go to a page that should not display the alert.
    // This call would throw the error from below if the alert was still there.
    $this->drupalGet('/admin');
    // Go to the front page and see if the alert is popping up.
    try {
      $this->drupalGet('<front>');
    }
    catch (UnexpectedAlertOpen $e) {
      $this->assertTrue(TRUE);
    }
    $message = $driver->getWebDriverSession()->getAlert_text();
    $driver->getWebDriverSession()->accept_alert();
    $this->assertEquals('Test', $message);
    // Disable the injector and check that the alert is gone.
    AssetInjectorJs::load('test_js')->disable()->save();
    // This call would throw the error from above if the alert was still there.
    $this->drupalGet('<front>');
  }

  /**
   * Tests wether injected CSS works correctly.
   */
  public function testInjectedCss() {
    // Create a new CSS injector and set an alert for the front page.
    AssetInjectorCss::create([
      'type' => 'asset_injector_css',
      'id' => 'test_css',
      'label' => 'test_css',
      'status' => TRUE,
      'code' => 'body {background-color: #000000 !important;}',
      'conditions' => [
        'request_path' => [
          'id' => 'request_path',
          'negate' => FALSE,
          'pages' => '<front>',
        ],
      ],
    ])->save();
    $script = "(function(){
      return window.getComputedStyle(document.body, null).getPropertyValue('background-color') === 'rgb(0, 0, 0)';
  })();";
    // Go to a page that should not have been modified and check the CSS.
    $this->drupalGet('/admin');
    $this->assertFalse($this->getSession()->evaluateScript($script));
    // Go to the front page and see if the CSS is modified.
    $this->drupalGet('<front>');
    $this->assertTrue($this->getSession()->evaluateScript($script));
    // Disable the injector and check that the CSS is gone.
    AssetInjectorCss::load('test_css')->disable()->save();
    $this->drupalGet('<front>');
    $this->assertFalse($this->getSession()->evaluateScript($script));
  }

}
