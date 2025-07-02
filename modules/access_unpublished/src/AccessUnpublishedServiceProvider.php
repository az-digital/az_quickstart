<?php

namespace Drupal\access_unpublished;

use Drupal\access_unpublished\Access\LatestRevisionCheck;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorates the access_check.latest_revision service.
 */
class AccessUnpublishedServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('access_check.latest_revision')) {
      $container->register('access_unpublished.access_check.latest_revision', LatestRevisionCheck::class)
        ->setDecoratedService('access_check.latest_revision')
        ->addArgument(new Reference('access_unpublished.access_check.latest_revision.inner'))
        ->addTag('access_check', ['applies_to' => '_content_moderation_latest_version']);
    }
  }

}
