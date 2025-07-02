<?php

namespace Drupal\Tests\config_normalizer\Kernel;

use Drupal\config_normalizer\Config\NormalizedReadOnlyStorageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests normalizing configuration.
 *
 * @group config_normalizer
 */
class ConfigNormalizerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'config_normalizer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
  }

  /**
   * Tests that basic config normalization is working.
   */
  public function testNormalization() {

    $data = [
      'langcode' => 'en',
      'uuid' => '',
      'name' => 'My site',
      'mail' => 'noreply@example.com',
      'slogan' => 'My incredible slogan',
      'page' => [
        '403' => '',
        '404' => '',
        'front' => '/user/login',
      ],
      'admin_compact_mode' => FALSE,
      'weight_select_max' => 100,
      'default_langcode' => 'en',
    ];

    $context = [
      'normalization_mode' => NormalizedReadOnlyStorageInterface::NORMALIZATION_MODE_COMPARE,
      'reference_storage_service' => $this->container->get('config.storage'),
    ];

    $this->container->get('plugin.manager.config_normalizer')
      ->createInstance('active')
      ->normalize('system.site', $data, $context);

    $this->assertArrayHasKey('_core', $data, 'Config normalization failed.');
  }

}
