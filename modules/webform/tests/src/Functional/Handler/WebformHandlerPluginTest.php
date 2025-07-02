<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform handler plugin.
 *
 * @group webform
 */
class WebformHandlerPluginTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_handler'];

  /**
   * Tests webform handler plugin dependencies.
   *
   * @see \Drupal\webform\Entity\Webform::onDependencyRemoval
   */
  public function testWebformHandlerDependencies() {
    $webform = Webform::load('contact');

    // Check initial dependencies.
    $this->assertEquals($webform->getDependencies(), ['module' => ['webform']]);

    /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
    $handler_manager = $this->container->get('plugin.manager.webform.handler');

    // Add 'test' handler provided by the webform_test.module.
    $webform_handler_configuration = [
      'id' => 'test',
      'label' => 'test',
      'handler_id' => 'test',
      'status' => 1,
      'weight' => 2,
      'settings' => [],
    ];
    $webform_handler = $handler_manager->createInstance('test', $webform_handler_configuration);
    $webform->addWebformHandler($webform_handler);
    $webform->save();

    // Check that handler has been added to the dependencies.
    $this->assertEquals($webform->getDependencies(), ['module' => ['webform_test_handler', 'webform']]);

    // Uninstall the webform_test_handler.module which will also remove the
    // test handler.
    $this->container->get('module_installer')->uninstall(['webform_test_handler']);
    $webform = Webform::load('contact');

    // Check that handler was removed from the dependencies.
    $this->assertNotEquals($webform->getDependencies(), ['module' => ['webform_test_handler', 'webform']]);
    $this->assertEquals($webform->getDependencies(), ['module' => ['webform']]);
  }

}
