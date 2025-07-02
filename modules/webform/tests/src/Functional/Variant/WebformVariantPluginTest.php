<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant plugin.
 *
 * @group webform
 */
class WebformVariantPluginTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_variant'];

  /**
   * Tests webform variant plugin dependencies.
   *
   * @see \Drupal\webform\Entity\Webform::onDependencyRemoval
   */
  public function testWebformVariantDependencies() {
    $webform = Webform::load('contact');

    // Check initial dependencies.
    $this->assertEquals($webform->getDependencies(), ['module' => ['webform']]);

    /** @var \Drupal\webform\Plugin\WebformVariantManagerInterface $variant_manager */
    $variant_manager = $this->container->get('plugin.manager.webform.variant');

    // Add 'test' variant provided by the webform_test.module.
    $webform_variant_configuration = [
      'id' => 'test',
      'label' => 'test',
      'variant_id' => 'test',
      'status' => 1,
      'weight' => 2,
      'debug' => TRUE,
    ];
    $webform_variant = $variant_manager->createInstance('test', $webform_variant_configuration);
    $webform->addWebformVariant($webform_variant);
    $webform->save();

    // Check that variant has been added to the dependencies.
    $this->assertEquals($webform->getDependencies(), ['module' => ['webform_test_variant', 'webform']]);

    // Uninstall the webform_test_variant.module which will also remove the
    // test variant.
    $this->container->get('module_installer')->uninstall(['webform_test_variant']);
    $webform = Webform::load('contact');

    // Check that variant was removed from the dependencies.
    $this->assertNotEquals($webform->getDependencies(), ['module' => ['webform_test_variant', 'webform']]);
    $this->assertEquals($webform->getDependencies(), ['module' => ['webform']]);
  }

}
