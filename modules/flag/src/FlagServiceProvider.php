<?php

namespace Drupal\flag;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides a service modifier to support anonymous flaggings.
 */
class FlagServiceProvider implements ServiceModifierInterface {

  /**
   * Modifies existing service definitions.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    // Get the CSRF service ID even if it has been aliased.
    for ($id = 'access_check.csrf'; $container->hasAlias($id); $id = (string) $container->getAlias($id));

    // Hide the original service definition.
    $original_definition = $container->getDefinition($id)->setPublic(FALSE);

    // Replace it with our definition.
    $container->setDefinition("flag.$id", $original_definition);
    $new_definition = new Definition('Drupal\flag\Access\CsrfAccessCheck', [
      new Reference("flag.$id"),
      new Reference('current_user'),
    ]);
    $new_definition->setTags($original_definition->getTags());
    $original_definition->setTags([]);
    $new_definition->setPublic(TRUE);
    $container->setDefinition($id, $new_definition);
  }

}
