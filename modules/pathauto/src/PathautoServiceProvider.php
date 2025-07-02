<?php

namespace Drupal\pathauto;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Remove the drush commands until path_alias module is enabled.
 */
class PathautoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $definitions = array_keys($container->getDefinitions());
    if (!in_array('path_alias.repository', $definitions)) {
      $container->removeDefinition('pathauto.commands');
    }
  }

}
