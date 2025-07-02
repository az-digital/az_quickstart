<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged 'stream_wrapper' to the stream_wrapper_manager service.
 */
class RegisterStreamWrappersPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('stream_wrapper_manager')) {
      return;
    }

    $stream_wrapper_manager = $container->getDefinition('stream_wrapper_manager');

    $services = [];
    foreach ($container->findTaggedServiceIds('stream_wrapper') as $id => $tags) {
      $class = $container->getDefinition($id)->getClass();
      // Loop through all the tags for this stream wrapper as we may have
      // multiple schemes.
      foreach ($tags as $attributes) {
        $stream_wrapper_manager->addMethodCall('addStreamWrapper', [
          $id,
          $class,
          $attributes['scheme'],
        ]);
      }
      $services[$id] = new Reference($id);
    }
    $stream_wrapper_manager->addArgument(ServiceLocatorTagPass::register($container, $services));
  }

}
