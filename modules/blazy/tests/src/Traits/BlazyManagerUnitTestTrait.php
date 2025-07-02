<?php

namespace Drupal\Tests\blazy\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * A Trait common for Blazy related service managers.
 */
trait BlazyManagerUnitTestTrait {

  /**
   * Setup the unit manager.
   */
  protected function setUpUnitServices() {
    $this->entityStorage      = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $this->entityViewBuilder  = $this->createMock('\Drupal\Core\Entity\EntityViewBuilderInterface');
    $this->entityTypeMock     = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityFieldManager = $this->createMock('\Drupal\Core\Entity\EntityFieldManagerInterface');
    $this->entityRepository   = $this->createMock('\Drupal\Core\Entity\EntityRepositoryInterface');
    $this->entityTypeManager  = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->renderer           = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->cache              = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $this->languageManager    = $this->createMock('\Drupal\Core\Language\LanguageManager');
    $this->moduleHandler      = $this->createMock('\Drupal\Core\Extension\ModuleHandler');
    $this->token              = $this->createMock('\Drupal\Core\Utility\Token');
    $this->libraries          = $this->createMock('\Drupal\blazy\Asset\LibrariesInterface');

    /* @phpstan-ignore-next-line */
    $this->token->expects($this->any())
      ->method('replace')
      ->willReturnArgument(0);

    $this->configFactory = $this->getConfigFactoryStub([
      'blazy.settings' => [
        'admin_css' => TRUE,
        'noscript' => TRUE,
        'one_pixel' => TRUE,
        'blazy' => ['loadInvisible' => FALSE, 'offset' => 100],
      ],
    ]);

    // Since 2.16.
    $this->blazyManager = $this->createMock('\Drupal\blazy\BlazyManagerInterface');

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('libraries')
      ->willReturn($this->libraries);

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('moduleHandler')
      ->willReturn($this->moduleHandler);

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('entityTypeManager')
      ->willReturn($this->entityTypeManager);

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('renderer')
      ->willReturn($this->renderer);

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('configFactory')
      ->willReturn($this->configFactory);

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('cache')
      ->willReturn($this->cache);

    /* @phpstan-ignore-next-line */
    $this->blazyManager->expects($this->any())
      ->method('languageManager')
      ->willReturn($this->languageManager);
  }

  /**
   * Setup the unit manager.
   */
  protected function setUpUnitContainer() {
    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('entity.repository', $this->entityRepository);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('module_handler', $this->moduleHandler);
    $container->set('renderer', $this->renderer);
    $container->set('config.factory', $this->configFactory);
    $container->set('cache.default', $this->cache);
    $container->set('language_manager', $this->languageManager);
    $container->set('token', $this->token);
    $container->set('blazy.manager', $this->blazyManager);

    \Drupal::setContainer($container);
  }

  /**
   * Prepare image styles.
   */
  protected function setUpImageStyle() {
    $styles = [];

    $dummies = ['blazy_crop', 'large', 'medium', 'small'];
    foreach ($dummies as $style) {
      $mock = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityInterface');

      /* @phpstan-ignore-next-line */
      $mock->expects($this->any())
        ->method('getCacheTags')
        ->willReturn([]);

      $styles[$style] = $mock;
    }

    $ids = array_keys($styles);
    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');

    /* @phpstan-ignore-next-line */
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->with($ids)
      ->willReturn($styles);

    $style = 'large';

    /* @phpstan-ignore-next-line */
    $storage->expects($this->any())
      ->method('load')
      ->with($style)
      ->will($this->returnValue($styles[$style]));

    /* @phpstan-ignore-next-line */
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('image_style')
      ->willReturn($storage);

    return $styles;
  }

  /**
   * Prepare Responsive image styles.
   */
  protected function setUpResponsiveImageStyle() {
    $styles = $image_styles = [];
    foreach (['fallback', 'small', 'medium', 'large'] as $style) {
      $mock = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityInterface');

      /* @phpstan-ignore-next-line */
      $mock->expects($this->any())
        ->method('getConfigDependencyName')
        ->willReturn('image.style.' . $style);

      /* @phpstan-ignore-next-line */
      $mock->expects($this->any())
        ->method('getCacheTags')
        ->willReturn([]);

      $image_styles[$style] = $mock;
    }

    foreach (['blazy_picture_test', 'blazy_responsive_test'] as $style) {
      $mock = $this->createMock('\Drupal\responsive_image\ResponsiveImageStyleInterface');

      /* @phpstan-ignore-next-line */
      $mock->expects($this->any())
        ->method('getImageStyleIds')
        ->willReturn(array_keys($image_styles));

      /* @phpstan-ignore-next-line */
      $mock->expects($this->any())
        ->method('getCacheTags')
        ->willReturn([]);

      $styles[$style] = $mock;
    }

    $ids = array_keys($styles);
    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');

    /* @phpstan-ignore-next-line */
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->with($ids)
      ->willReturn($styles);

    $style = 'blazy_picture_test';

    /* @phpstan-ignore-next-line */
    $storage->expects($this->any())
      ->method('load')
      ->with($style)
      ->willReturn($styles[$style]);

    /* @phpstan-ignore-next-line */
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('responsive_image_style')
      ->willReturn($storage);

    /* @phpstan-ignore-next-line */
    $this->entityTypeManager->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->with('Drupal\image\Entity\ImageStyle')
      ->willReturn('image_style');

    return $styles;
  }

}
