<?php

namespace Drupal\config_distro\Controller;

use Drupal\config\Controller\ConfigController;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for config module routes.
 */
class ConfigDistroController extends ConfigController implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $class = parent::create($container);
    // Substitute our storage for the default one.
    $class->syncStorage = $container->get('config_distro.storage.distro');
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
