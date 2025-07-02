<?php

namespace Drupal\flag\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\flag\ActionLink\ActionLinkTypeBase;
use Drupal\flag\FlagInterface;

/**
 * Provides the Normal Link (Reload) link type.
 *
 * @ActionLinkType(
 *   id = "reload",
 *   label = @Translation("Normal link"),
 *   description = @Translation("A normal non-JavaScript request will be made and the current page will be reloaded.")
 * )
 */
class Reload extends ActionLinkTypeBase {

  /**
   * Get URL.
   */
  public function getUrl($action, FlagInterface $flag, EntityInterface $entity) {
    switch ($action) {
      case 'flag':
        return Url::fromRoute('flag.action_link_flag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);

      default:
        return Url::fromRoute('flag.action_link_unflag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);
    }
  }

}
