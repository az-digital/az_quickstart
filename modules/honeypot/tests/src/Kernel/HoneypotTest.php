<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests honeypot functionality.
 *
 * @group honeypot
 */
class HoneypotTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['honeypot'];

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Honeypot service.
   *
   * @var \Drupal\honeypot\HoneypotServiceInterface
   */
  protected $honeypot;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('honeypot', []);
    $this->installConfig(['honeypot']);
    $this->configFactory = $this->container->get('config.factory');
    $this->honeypot = $this->container->get('honeypot');
  }

  /**
   * Tests the Honeypot protected forms method.
   *
   * @covers \Drupal\honeypot\HoneypotService::getProtectedForms
   */
  public function testGetProtectedForms(): void {
    $config = $this->configFactory->getEditable('honeypot.settings');

    // Initial state: we have a protected form.
    $config->set('form_settings', ['user_register_form' => TRUE])->save();
    $this->assertEquals(['user_register_form'], $this->honeypot->getProtectedForms());

    // Empty form_settings, expect no protected forms.
    $config->set('form_settings', [])->save();
    $this->assertEquals([], $this->honeypot->getProtectedForms());
  }

}
