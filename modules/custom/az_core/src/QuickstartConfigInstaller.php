<?php

namespace Drupal\az_core;

use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class for customizing the test for pre existing configuration.
 *
 * Decorates the ConfigInstaller with findPreExistingConfiguration() modified.
 */
class QuickstartConfigInstaller extends ConfigInstaller {

  /**
   * The configuration installer.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * {@inheritdoc}
   */
  protected function findPreExistingConfiguration(StorageInterface $storage) {
    // Override
    // Drupal\Core\Config\ConfigInstaller::findPreExistingConfiguration().
    // Allow config that already exists.

    $existing_configuration = [];
    return $existing_configuration;
  }

}
