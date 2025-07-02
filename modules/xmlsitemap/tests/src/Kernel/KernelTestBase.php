<?php

namespace Drupal\Tests\xmlsitemap\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase as CoreKernelTestBase;

/**
 * Base class for xmlsitemap kernel tests.
 */
abstract class KernelTestBase extends CoreKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'xmlsitemap',
  ];

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    // This is required to not fail the @covers for global functions.
    // @todo Once xmlsitemap_clear_directory() is refactored to auto-loadable code, remove this require statement.
    require_once __DIR__ . '/../../../xmlsitemap.module';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('xmlsitemap', ['xmlsitemap']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig('xmlsitemap');

    // Install hooks are not run with kernel tests.
    xmlsitemap_install();
    $this->assertDirectoryExists('public://xmlsitemap');

    $this->linkStorage = $this->container->get('xmlsitemap.link_storage');
  }

  /**
   * Asserts that an entity will be visible in the sitemap.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  protected function assertEntityVisibleInSitemap(EntityInterface $entity): void {
    $link = $this->linkStorage->load($entity->getEntityTypeId(), $entity->id());
    $this->assertTrue($link['access'] && $link['status']);
  }

  /**
   * Asserts that an entity will not be visible in the sitemap.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  protected function assertEntityNotVisibleInSitemap(EntityInterface $entity): void {
    $link = $this->linkStorage->load($entity->getEntityTypeId(), $entity->id());
    $this->assertFalse($link['access'] && $link['status']);
  }

}
