<?php

namespace Drupal\devel_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for devel module routes.
 */
class DevelTestController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * Returns a simple page output.
   *
   * @return array
   *   A render array.
   */
  public function simplePage(): array {
    return [
      '#markup' => $this->t('Simple page'),
    ];
  }

}
