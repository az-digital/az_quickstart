<?php

namespace Drupal\slick_ui\Controller;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\blazy\Controller\BlazyListBuilderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Slick optionsets.
 */
abstract class SlickListBuilderBase extends BlazyListBuilderBase {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );

    $instance->manager = $container->get('slick.manager');
    return $instance;
  }

}
