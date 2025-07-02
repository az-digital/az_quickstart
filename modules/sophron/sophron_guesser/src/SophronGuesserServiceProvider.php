<?php

declare(strict_types=1);

namespace Drupal\sophron_guesser;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\sophron\MimeMapManagerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the "file.mime_type.guesser.extension" service.
 */
class SophronGuesserServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides "file.mime_type.guesser.extension" to use Sophron.
    $definition = $container->getDefinition('file.mime_type.guesser.extension');
    $definition->setClass(SophronMimeTypeGuesser::class)
      ->setArguments([
        new Reference(MimeMapManagerInterface::class),
        new Reference('file_system'),
      ]);
  }

}
