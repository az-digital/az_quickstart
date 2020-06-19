<?php

namespace Drupal\az_core\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\config_distro\Controller\ConfigDistroController;

/**
 * Returns responses for config module routes.
 */
class QuickstartDistroController extends ConfigDistroController implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $class = parent::create($container);
    // Substitute our storage for the default one.
    $class->sourceStorage = $container->get('config_distro.storage.distro');
    $class->syncStorage = $container->get('config_provider.storage');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function diff($source_name, $target_name = NULL, $collection = NULL) {
    $build = parent::diff($source_name, $target_name, $collection);
    $build['back']['#url'] = Url::fromRoute('config_distro.import');
    return $build;
  }

}
