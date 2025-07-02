<?php

namespace Drupal\devel_generate;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the filter module.
 */
class DevelGeneratePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The plugin manager.
   */
  protected DevelGeneratePluginManager $develGeneratePluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->develGeneratePluginManager = $container->get('plugin.manager.develgenerate');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * A permissions' callback.
   *
   * @see devel_generate.permissions.yml
   *
   * @return array
   *   An array of permissions for all plugins.
   */
  public function permissions(): array {
    $permissions = [];
    $devel_generate_plugins = $this->develGeneratePluginManager->getDefinitions();
    foreach ($devel_generate_plugins as $plugin) {
      $permission = $plugin['permission'];
      $permissions[$permission] = [
        'title' => $this->t('@permission', ['@permission' => $permission]),
      ];
    }

    return $permissions;
  }

}
