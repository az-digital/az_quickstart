<?php

namespace Drupal\Tests\devel_generate\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drupal\devel_generate_example\Plugin\DevelGenerate\ExampleDevelGenerate;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\devel_generate\DevelGeneratePluginManager
 * @group devel_generate
 */
class DevelGenerateManagerTest extends UnitTestCase {

  /**
   * The plugin discovery.
   */
  protected MockObject|DiscoveryInterface $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock the plugin discovery.
    $this->discovery = $this->createMock(DiscoveryInterface::class);
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->willReturnCallback(function (): array {
        return $this->getMockDefinitions();
      });
  }

  /**
   * Test creating an instance of the DevelGenerateManager.
   */
  public function testCreateInstance(): void {
    $namespaces = new \ArrayObject(['Drupal\devel_generate_example' => realpath(__DIR__ . '/../../../modules/devel_generate_example/lib')]);
    $cache_backend = $this->createMock(CacheBackendInterface::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $messenger = $this->createMock(MessengerInterface::class);
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $string_translation = $this->createMock(TranslationInterface::class);
    $entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $manager = new DevelGeneratePluginManager(
      $namespaces,
      $cache_backend,
      $module_handler,
      $entity_type_manager,
      $messenger,
      $language_manager,
      $string_translation,
      $entityFieldManager,
    );

    // Use reflection to set the protected discovery property.
    $reflection = new \ReflectionClass($manager);
    $property = $reflection->getProperty('discovery');
    $property->setValue($manager, $this->discovery);

    $container = new ContainerBuilder();
    $time = $this->createMock(TimeInterface::class);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('messenger', $messenger);
    $container->set('language_manager', $language_manager);
    $container->set('module_handler', $module_handler);
    $container->set('string_translation', $string_translation);
    $container->set('entity_field.manager', $entityFieldManager);
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    $example_instance = $manager->createInstance('devel_generate_example');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf(ExampleDevelGenerate::class, $example_instance);
    $this->assertArrayHasKey('url', $plugin_def);
    $this->assertTrue($plugin_def['url'] == 'devel_generate_example');
  }

  /**
   * Callback function to return mock definitions.
   *
   * @return array
   *   The mock of devel generate plugin definitions.
   */
  public function getMockDefinitions(): array {
    return [
      'devel_generate_example' => [
        'id' => 'devel_generate_example',
        'class' => ExampleDevelGenerate::class,
        'url' => 'devel_generate_example',
        'dependencies' => [],
      ],
    ];
  }

}
