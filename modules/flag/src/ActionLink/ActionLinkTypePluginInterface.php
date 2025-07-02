<?php

namespace Drupal\flag\ActionLink;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\flag\FlagInterface;

/**
 * Provides an interface for link type plugins.
 */
interface ActionLinkTypePluginInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Get the action link formatted for use in entity links.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param string|null $view_mode
   *   The flaggable entity view mode.
   *
   * @return array
   *   The render array.
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL): array;

  /**
   * Get the action link as a Link object.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param string|null $view_mode
   *   The flaggable entity view mode.
   *
   * @return \Drupal\Core\Link
   *   The action Link.
   */
  public function getAsLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL);

}
