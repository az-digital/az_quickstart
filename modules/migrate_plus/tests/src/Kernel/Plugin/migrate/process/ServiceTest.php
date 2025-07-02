<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Row;

/**
 * Tests the service plugin.
 *
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\Service
 * @group migrate_plus
 */
final class ServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_plus',
    'system',
  ];

  /**
   * The process plugin manager.
   */
  protected ?MigratePluginManagerInterface $pluginManager = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->container->get('plugin.manager.migrate.process');
  }

  /**
   * Tests using a service.
   *
   * @covers ::create
   */
  public function testValidConfig(): void {
    /** @var \Drupal\migrate\MigrateExecutableInterface $executable */
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row([], []);
    $configuration = [
      'service' => 'email.validator',
      'method' => 'isValid',
    ];
    /** @var \Drupal\migrate_plus\Plugin\migrate\process\Service $service */
    $service = $this->pluginManager->createInstance('service', $configuration);

    // Test a valid email address.
    $value = 'drupal@example.com';
    $bool = $service->transform($value, $executable, $row, 'destination_property');
    $this->assertEquals(TRUE, $bool);

    // Test an invalid email address.
    $value = 'drupal_example.com';
    $bool = $service->transform($value, $executable, $row, 'destination_property');
    $this->assertEquals(FALSE, $bool);
  }

  /**
   * Tests configuration validation.
   *
   * @param string[] $configuration
   *   The configuration for the service plugin. The expected keys are 'service'
   *   and 'method'.
   * @param string $message
   *   The expected exception message.
   *
   * @covers ::create
   *
   * @dataProvider providerConfig
   */
  public function testInvalidConfig(array $configuration, string $message): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $this->pluginManager->createInstance('service', $configuration);
  }

  /**
   * Data provider for testInvalidConfig.
   */
  public static function providerConfig(): array {
    return [
      'missing service name' => [
        'configuration' => [],
        'message' => 'The "service" must be set.',
      ],
      'missing method name' => [
        'configuration' => ['service' => 'email.validator'],
        'message' => 'The "method" must be set.',
      ],
      'invalid service name' => [
        'configuration' => [
          'service' => 'no shirt no shoes no service',
          'method' => 'isValid',
        ],
        'message' => 'You have requested the non-existent service "no shirt no shoes no service".',
      ],
      'invalid method name' => [
        'configuration' => [
          'service' => 'email.validator',
          'method' => 'noSuchMethod',
        ],
        'message' => 'The "email.validator" service has no method "noSuchMethod".',
      ],
    ];
  }

}
