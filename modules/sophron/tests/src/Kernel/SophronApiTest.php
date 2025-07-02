<?php

declare(strict_types=1);

namespace Drupal\Tests\sophron\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sophron\Map\DrupalMap;
use Drupal\sophron\MimeMapManager;
use Drupal\sophron\MimeMapManagerInterface;
use FileEye\MimeMap\MalformedTypeException;
use FileEye\MimeMap\Map\DefaultMap;
use FileEye\MimeMap\MappingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for Sophron API.
 *
 * @coversDefaultClass \Drupal\sophron\MimeMapManager
 *
 * @group sophron
 */
#[CoversClass(MimeMapManager::class)]
#[Group('sophron')]
class SophronApiTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sophron'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['sophron']);
  }

  /**
   * Tests get Extension.
   *
   * @legacy-covers ::getMapClass
   * @legacy-covers ::setMapClass
   * @legacy-covers ::listExtensions
   * @legacy-covers ::getExtension
   */
  public function testGetExtension(): void {
    $manager = \Drupal::service(MimeMapManagerInterface::class);
    $this->assertEquals(DrupalMap::class, $manager->getMapClass());
    $this->assertContains('atomsrv', $manager->listExtensions());
    $this->assertEquals('application/atomserv+xml', $manager->getExtension('atomsrv')->getDefaultType());
    // No type for extension.
    $manager->setMapClass(DefaultMap::class);
    $this->expectException(MappingException::class);
    $manager->getExtension('atomsrv')->getDefaultType();
  }

  /**
   * Tests get Type.
   *
   * @legacy-covers ::listTypes
   * @legacy-covers ::getType
   */
  public function testGetType(): void {
    $manager = \Drupal::service(MimeMapManagerInterface::class);
    $this->assertContains('application/atomserv+xml', $manager->listTypes());
    $this->assertEquals(['atomsrv'], $manager->getType('application/atomserv+xml')->getExtensions());
  }

  /**
   * Tests get missing Type.
   *
   * @legacy-covers ::getType
   */
  public function testGetMissingType(): void {
    $manager = \Drupal::service(MimeMapManagerInterface::class);
    // No extensions for type.
    $this->expectException(MappingException::class);
    $manager->getType('a/b')->getExtensions();
  }

  /**
   * Tests get malformed Type.
   *
   * @legacy-covers ::getType
   */
  public function testGetMalformedType(): void {
    $manager = \Drupal::service(MimeMapManagerInterface::class);
    // Malformed MIME type.
    $this->expectException(MalformedTypeException::class);
    $manager->getType('application/');
  }

  /**
   * Tests get mapping errors.
   *
   * @legacy-covers ::getMapClass
   * @legacy-covers ::getMappingErrors
   */
  public function testGetMappingErrors(): void {
    $config = \Drupal::configFactory()->getEditable('sophron.settings');
    $config
      ->set('map_option', MimeMapManagerInterface::DEFAULT_MAP)
      ->set('map_commands', [
        [
          'method' => 'aaa',
          'arguments' => ['paramA', 'paramB'],
        ],
        [
          'method' => 'bbb',
          'arguments' => ['paramC', 'paramD'],
        ],
        [
          'method' => 'ccc',
          'arguments' => ['paramE'],
        ],
        [
          'method' => 'ddd',
          'arguments' => [],
        ],
      ])
      ->save();
    $manager = \Drupal::service(MimeMapManagerInterface::class);
    $this->assertSame(DefaultMap::class, $manager->getMapClass());
    $this->assertCount(4, $manager->getMappingErrors(DefaultMap::class));
  }

  /**
   * Tests no mapping errors for DrupalMap vs core.
   */
  public function testZeroMappingErrorsForDrupalMap(): void {
    $this->assertSame([], \Drupal::service(MimeMapManagerInterface::class)->determineMapGaps(DrupalMap::class));
  }

}
