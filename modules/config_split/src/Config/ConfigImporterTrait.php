<?php

namespace Drupal\config_split\Config;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageComparerInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * The ConfigImporterTrait helps us to create a ConfigImporter.
 *
 * This uses \Drupal::getContainer() so that we don't have to inject all the
 * services necessary to instantiate the config importer. This means we can not
 * unit test the class, but honestly when a config importer is needed a
 * functional test is necessary anyway.
 *
 * @internal This is not an API, copy this code if you want to re-use it.
 */
trait ConfigImporterTrait {

  /**
   * Get a config importer from a storage comparer.
   *
   * @param \Drupal\Core\Config\StorageComparerInterface $storageComparer
   *   A storage comparer to pass to the config importer.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The config importer.
   */
  protected function getConfigImporterFromComparer(StorageComparerInterface $storageComparer): ConfigImporter {
    $container = \Drupal::getContainer();
    return new ConfigImporter(
      $storageComparer,
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('lock.persistent'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('string_translation'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme')
    );
  }

  /**
   * Get a config importer from a storage to sync import.
   *
   * @param \Drupal\Core\Config\StorageInterface $toImport
   *   The config storage to import from.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The config importer.
   */
  protected function getConfigImporterFromStorage(StorageInterface $toImport): ConfigImporter {
    $active = \Drupal::getContainer()->get('config.storage');
    return $this->getConfigImporterFromComparer(new StorageComparer($toImport, $active));
  }

}
